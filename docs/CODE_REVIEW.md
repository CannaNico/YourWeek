# Code Review – YourWeek

Versione del report: 26 maggio 2026
Ambito: backend PHP (`includes/`, `api/`, script "sciolti") + frontend JS condiviso (`src/shared-utils.js`, `src/toast-notifications.js`, `src/cookie-banner.js`).

Tutto il codice riscritto si trova in `refactored/` con la stessa gerarchia dei file originali, per permettere un diff diretto. Nessun file dell'app originale è stato modificato.

## 1. Sommario esecutivo

Sono stati individuati **5 problemi critici di sicurezza**, **3 bug logici certi** e numerose opportunità di refactoring. I tre problemi più urgenti su cui intervenire prima di un deploy in produzione sono:

1. **`info.php`, `debug_users.php`, `add_test_patient.php`, `test_connection.php`** sono accessibili pubblicamente senza autenticazione. `info.php` espone `phpinfo()` (information disclosure massiva); `add_test_patient.php` consente a chiunque di creare un utente con password nota.
2. **`includes/config.php` e `includes/db_connection.php` definiscono le stesse costanti DB** (`DB_HOST`, `DB_USER`, ecc.) due volte. PHP emette notice di "Constant already defined" e il valore effettivo dipende dall'ordine di include. Inoltre `config.php` non viene mai incluso dagli endpoint, quindi tutte le sue `define` (timezone, log path, sicurezza) **non sono in vigore**.
3. **`api/auth.php`** logga email, ruoli e dati di registrazione via `error_log()` in chiaro (problema GDPR), salva il remember-me token **non hashato** in DB, alterna `Users`/`users` come nome tabella (rompe su Linux case-sensitive) e contiene un bug logico in `handleRegister` (doppia lettura di `php://input` e di `getDB()`).

Tutti questi punti sono risolti nella versione refactorata.

## 2. Dettaglio dei problemi e relativi fix

### 2.1 `includes/config.php` e `includes/db_connection.php`

| Problema | Severità | Fix |
|---|---|---|
| Doppia `define()` di `DB_HOST/USER/...` su entrambi i file → notice e valori inconsistenti | Alta | `db_connection.php` ora include `config.php`; tutte le `define` sono guardate da `if (!defined(...))`. |
| `config.php` mai incluso dagli endpoint → timezone, log path, costanti sicurezza ignorati | Alta | `db_connection.php` ora fa `require_once 'config.php'` come prima riga. |
| `session.cookie_secure = 0` hardcoded → cookie inviati in chiaro anche dietro HTTPS | Alta | Rilevamento HTTPS automatico (`$_SERVER['HTTPS']` + `X-Forwarded-Proto`) usato in `session_set_cookie_params`. |
| CORS riflette **qualsiasi** `Origin` con `Allow-Credentials: true` → CSRF + furto credenziali | Alta | Allow-list di origini in `ALLOWED_ORIGINS` (env-driven). Solo se l'origin è nella lista viene riflesso. |
| `sanitizeInput()` applica `htmlspecialchars` PRIMA del salvataggio in DB → dati corrotti (& → `&amp;`), e codifica una sola volta in input/output (XSS in output può comunque sfuggire) | Media | Sostituita con `cleanInput()` (solo trim + strip control char) per input e `escapeHtml()` separata per output. |
| `transactionCount >= 0` ritorna sempre `true` → return value inutile / fuorviante | Bassa | Restituisce ora `true/false` significativi e `rollback()` riporta sempre lo stack a 0 (semantica corretta). |
| Credenziali SMTP demo hardcoded (`your-email@gmail.com` / `your-password`) | Media | Tutte le credenziali SMTP ora vengono da env var. |
| Header `X-XSS-Protection` (deprecato, può aprire vulnerabilità) | Bassa | Rimosso. Aggiunti `Strict-Transport-Security` (in HTTPS) e `Permissions-Policy`. |
| Nessun supporto CSRF | Media | Aggiunto `getCsrfToken()` + `requireCsrf()` (header `X-CSRF-Token`). Token disponibile via `GET /api/auth.php?action=csrf` e nel payload di login/check. |
| Lettura input duplicata in ogni handler (`isset($_POST[...])` else `json_decode(file_get_contents(...))`) | Media | Nuovo helper `input($key, $default)` + `getJsonInput()` con caching: legge `php://input` una sola volta. |

### 2.2 `api/auth.php`

| Problema | Severità | Fix |
|---|---|---|
| **GDPR**: 10+ chiamate a `error_log()` con email, ruolo, "password verified for X", session ID dell'utente | Alta | Rimossi tutti i log di PII; restano solo log di errore tecnico. |
| **Inconsistenza case-sensitive**: alterna `Users` (INSERT/UPDATE) e `users` (SELECT, `getNutritionistCode`) → fallisce su MySQL Linux con `lower_case_table_names=0` | Alta | Normalizzato a `Users` ovunque. |
| Bug `handleRegister`: legge `php://input` e chiama `getDB()` due volte (`$db = getDB(); $data = ...; $db = getDB();`); il secondo `json_decode(file_get_contents('php://input'))` può restituire stringa vuota se Apache ha già consumato lo stream | Alta | Riscritto usando l'helper `input()` (lettura body singola con caching). |
| **Remember-me token salvato in chiaro** nel DB → un leak del DB compromette ogni sessione persistente | Alta | Token generato lato server, **solo SHA-256 hash** salvato; il client conserva il token in chiaro. |
| `sleep(1)` per timing attack: blocca un worker PHP per 1s → DoS facile (50 worker = 50 req/sec max) | Media | Sostituito con `usleep(random_int(200_000, 500_000))` (200–500 ms randomico). |
| `logFailedLogin` registra ma **nessun rate limiting effettivo** | Alta | Aggiunto `isLoginRateLimited()` che blocca dopo `MAX_LOGIN_ATTEMPTS` falliti in `LOGIN_TIMEOUT` secondi (chiave: email **o** IP). Restituisce 429. |
| `sanitizeInput()` applicato all'email → trasforma `nome+tag@dom.com` in modo non semantico (oggi non rompe ma è fragile) | Media | Email gestita con `strtolower(trim(...))` + `validateEmail()`. |
| `ensureNutritionistCode` chiamata al login → race condition possibile + lavoro inutile a ogni accesso | Media | Il codice viene generato in `handleRegister` (transazione DB). `ensureNutritionistCode` resta come safety net per utenti legacy. |
| `handleLogout` chiamato dentro `checkAuth` con poi `sendJSON` "morto" sotto | Bassa | `handleLogout` fa `exit` via `sendJSON`; il codice morto è stato rimosso. |
| `birth_date` passato come stringa vuota invece di `null` quando assente | Bassa | `$birthDate ?: null` + validazione regex `YYYY-MM-DD`. |
| Niente `try/catch` globale: una `Throwable` non gestita rivela stack trace nei messaggi 500 | Media | Wrapper `try/catch` attorno allo switch principale. |

### 2.3 `api/patients.php`

| Problema | Severità | Fix |
|---|---|---|
| `getNutritionistCode` usa tabella `users` (minuscolo) → fail su Linux | Alta | Normalizzato a `Users`. |
| Pattern di auth replicato 8 volte (`if (!isset($_SESSION['user_id']) || ...role !== 'nutrizionista')`) | Media | Helper `requireNutritionist()` + `assertPatientOwnedBy()` (DRY). |
| Lettura input duplicata per ogni handler | Media | Helper `input()` di `db_connection.php`. |
| Subquery in `getMyPatients` esegue una query separata per ogni paziente (loop N+1) per `next_appointment` | Media | Subquery scalare unica nella SELECT principale → 1 sola query DB. |
| Niente rollback automatico in caso di eccezione fuori da `try/catch` | Media | `try/catch` globale + rollback se `inTransaction()`. |
| Validazione `measurement_date` mancante in `addPatientProgress` → SQL date error opaco | Bassa | Regex `^\d{4}-\d{2}-\d{2}$`. |

### 2.4 `api/search_users.php`

| Problema | Severità | Fix |
|---|---|---|
| **Doppio `session_start()`**: lo fa qui + lo fa `db_connection.php` → warning `Session already started` | Media | Rimosso. |
| **Header `Content-Type` duplicato**: settato qui + da `sendJSON` | Bassa | Rimosso. |
| `error_reporting(E_ALL); ini_set('display_errors', 1)` hardcoded → in produzione mostra stack trace | Alta | Rimosso (gestito da `config.php`). |
| **Info leak**: risposta di errore include `$e->getMessage()` (es: rivela colonne SQL) | Alta | Messaggi di errore generici lato client; dettaglio solo in `error_log`. |
| `error_log` della query SQL ogni richiesta → log saturati | Bassa | Rimosso. |
| Lista province include `OG` (Ogliastra) e `OT` (Olbia-Tempio) **soppresse nel 2016** | Bassa | Sostituite con `SU` (Sud Sardegna). |
| Loop manuale `while ($row = $stmt->fetch())` invece di `fetchAll()` | Bassa | `fetchAll()`. |
| `nutritionist_code` esposto come campo separato per ogni nutrizionista → facilita enumerazione codici | Media | Codice incluso solo nel campo `description` quando il ruolo è `nutrizionista`; il campo viene rimosso dai pazienti. |

### 2.5 Script "sciolti" alla root (`info.php`, `debug_users.php`, `add_test_patient.php`, `test_connection.php`)

| Problema | Severità | Fix |
|---|---|---|
| **`info.php`** stampa `phpinfo()` a chiunque → versione PHP, estensioni, env vars (incluso DB password se in env), path completi | **Critica** | Restituisce 404 in produzione; richiede `requireAuth()` in development. Corretto anche bug degli escape Windows-CMD (`^<h1^>` letterale). |
| **`debug_users.php`** espone email, nomi, ruoli, provincia di tutti gli utenti senza auth | **Critica** | 404 in produzione + `requireAuth()` in dev + `htmlspecialchars` ovunque. |
| **`add_test_patient.php`** permette a chiunque di creare un account con password nota (`password123`) | **Critica** | 404 in produzione + solo CLI o utente nutrizionista loggato in dev. |
| **`test_connection.php`** stampa tabelle DB, versione MySQL, path uploads, credenziali demo | Alta | 404 in produzione + `requireAuth()` in dev. Rimosse le credenziali di esempio dalla schermata. |

> Raccomandazione aggiuntiva: questi file dovrebbero stare in una cartella `tools/` esclusa da `.htaccess`/nginx config in produzione (deny all), oltre alla protezione applicativa.

### 2.6 `src/toast-notifications.js`

| Problema | Severità | Fix |
|---|---|---|
| **XSS**: `${message}` e `${options.title}` interpolati in `innerHTML` senza escape → se il messaggio proviene da un campo utente o da un errore API contiene HTML, viene eseguito | Alta | Riscritto interamente con creazione DOM + `textContent` per ogni stringa dinamica. |
| Inline `onclick`/`onmouseover` (incompatibile con CSP `unsafe-inline`) | Media | Sostituiti con `addEventListener`. |
| `Math.random().toString(36).substr(2, 9)` (substr deprecato) | Bassa | `crypto.randomUUID()` con fallback. |
| `dataset.timeoutId` salva l'ID setTimeout come stringa, poi `parseInt` → in alcuni browser il riferimento si perde | Media | `WeakMap<HTMLElement, number>` per timeout. |
| Manca limite di toast in pila → flood possibile | Bassa | `MAX_VISIBLE = 5`; la più vecchia viene dismissed. |
| Manca accessibilità (no `role`/`aria-live`) | Media | `role="alert"` per errori, `role="status"` per il resto; container `aria-live="polite"`; pulsante chiudi con `aria-label`. |

### 2.7 `src/shared-utils.js`

| Problema | Severità | Fix |
|---|---|---|
| `detectColorScheme` aggiunge un listener ogni volta che viene chiamata → memory leak | Media | Tracciato un singolo MQL/listener; remove + add. |
| `formatRelativeTime` usa `Math.ceil(diffTime / 1h)` → "1h 5min" diventa "2 ore fa" | Bassa | `Math.floor` + soglia minuti separata per messaggi più precisi. |
| `generateId` usa `substr(2, 9)` deprecato | Bassa | `crypto.randomUUID()` con fallback `slice`. |
| `showFullScreenLoader` inserisce `message` con `innerHTML` → XSS se il messaggio è user-controlled | Media | DOM API + `textContent`. |
| `disableForm` non distingue input "originalmente disabilitati" da quelli abilitati → al riabilita li sblocca tutti | Bassa | `dataset.wasDisabled` per ricordare lo stato. |
| `saveToStorage` non distingue `QuotaExceededError` | Bassa | Branch dedicato che usa `console.warn` invece di `console.error`. |
| `isMobile` UA-sniffing only | Bassa | Priorità a `matchMedia('(pointer: coarse)')`. |
| `addRippleEffect` può bindare lo stesso bottone più volte | Bassa | `dataset.rippleBound` guard. |
| Inquinamento namespace globale (solo `window.Utils`) | Bassa | Aggiunto `window.YW.utils` (namespace); `window.Utils` resta per retrocompatibilità. |

### 2.8 `src/cookie-banner.js`

| Problema | Severità | Fix |
|---|---|---|
| `document.currentScript` può essere `null` se lo script è caricato in modo asincrono o come `type=module` → `base = ''` quindi il path al CSS punta alla pagina corrente | Media | Fallback su `querySelector('script[src*="cookie-banner.js"]')`. |
| Cookie set senza flag `Secure` anche in HTTPS | Media | Aggiunto `Secure` se `location.protocol === 'https:'`. |
| Nessuna possibilità di revocare il consenso | Bassa | Esposto `revokeConsent()` (utile per pagina "Modifica preferenze"). |
| Nessun focus management / supporto tastiera | Bassa | Focus automatico sul bottone "Accetta", `ESC` = "Solo necessari". |
| `innerHTML` per i contenuti statici del banner (basso rischio ma cattiva prassi) | Bassa | Costruzione tramite `createElement`/`textContent`. |
| Banner re-iniettabile più volte se chiamato due volte | Bassa | Guard `getElementById(BANNER_ID)`. |

## 3. Altre raccomandazioni (non implementate, ma consigliate)

- **Spostare gli script di debug in `tools/`** e bloccarli a livello server (Apache `Require all denied` o nginx `deny all;`).
- **`.env` + libreria `vlucas/phpdotenv`** per gestire le variabili d'ambiente in development, evitando di toccare l'ambiente del server.
- **Aggiungere un layer "Repository"** (es. `UserRepository`, `PatientRepository`) per isolare le query SQL dagli handler HTTP — il codice attuale ha gli endpoint che mescolano routing, validazione, business logic e accesso DB.
- **Test automatici**: anche solo PHPUnit smoke test su `auth.php` (login OK / KO / rate limited) eviterebbero regressioni future. Sul frontend, Vitest sui due moduli `Utils` e `Toast`.
- **Composer + PSR-4**: l'autoloader manuale di `config.php` ricerca classi in `includes/classes/` ma non c'è nessuna classe lì. Conviene introdurre `composer.json` con autoload PSR-4.
- **Content-Security-Policy** completa (commento presente in `db_connection.php`). Senza CSP, l'esposizione XSS dei toast (ora chiusa) sarebbe stata uno SOP bypass diretto.
- **Trasformare `nutritionist_code` in un unique index a livello DB** se non lo è già (`UNIQUE KEY (nutritionist_code)`) — al momento `generateUniqueNutritionistCode` previene collisioni client-side ma non c'è una garanzia DB.
- **Migrare alle prepared statements named parameters** (più leggibili) — già fatto nei file refactorati dove la query aveva 3+ parametri.
- **Eliminare i file di backup `-- database_schema.sql`** dalla root (il nome con `--` iniziale è un trucco git ma è ambiguo).

## 4. Modifiche principali — riepilogo schematico

Backend:

- `includes/config.php`: idempotente, env-driven, **non duplica più** le costanti DB.
- `includes/db_connection.php`: include `config.php`, sessione HTTPS-aware, CORS allow-list, CSRF, helper `input()`/`cleanInput()`/`escapeHtml()`/`hashToken()`/`requireAuth()`/`requireRole()`.
- `api/auth.php`: rate limiting reale, remember-me hashato, nessun log PII, niente bug `handleRegister`, codice nutrizionista generato in registrazione, normalizzazione case tabelle.
- `api/patients.php`: helper DRY, query N+1 eliminata, validazione date, rollback automatico.
- `api/search_users.php`: niente più doppia `session_start`, niente info leak, lista province aggiornata, codice nutrizionista non più esposto come campo separato.
- Script di debug: 404 in produzione, auth richiesta in development, fix bug escape Windows-CMD in `info.php`.

Frontend:

- `src/toast-notifications.js`: XSS chiuso (DOM API), CSP-friendly (no inline handler), accessibilità, limite toast, WeakMap timeout.
- `src/shared-utils.js`: modernizzazione ES2020+, fix memory leak `detectColorScheme`, fix `formatRelativeTime`, `crypto.randomUUID`, namespace `YW.utils`, `disableForm` ora rispetta lo stato originale.
- `src/cookie-banner.js`: fallback path CSS robusto, flag `Secure` in HTTPS, `revokeConsent()`, focus + supporto tastiera, no `innerHTML`.

## 5. File creati

```
refactored/
├── CODE_REVIEW.md                  ← questo report
├── includes/
│   ├── config.php
│   └── db_connection.php
├── api/
│   ├── auth.php
│   ├── patients.php
│   └── search_users.php
├── src/
│   ├── cookie-banner.js
│   ├── shared-utils.js
│   └── toast-notifications.js
├── info.php
├── debug_users.php
├── add_test_patient.php
└── test_connection.php
```

Tutti i file refactorati sono **sintatticamente bilanciati** (verifica brace/parens su PHP, `node --check` sui JS). Una validazione `php -l` definitiva richiede un'installazione PHP locale: si raccomanda di eseguire `for f in $(find refactored -name '*.php'); do php -l "$f"; done` prima del merge.

## 6. Suggerimenti di workflow

1. Fai diff visivo tra `<file>` originale e `refactored/<file>` (es. `code --diff includes/config.php refactored/includes/config.php`).
2. Sposta i file refactorati nelle posizioni originali UNO per volta, partendo da `includes/` (la base), poi `api/`, infine gli script di debug.
3. Aggiorna le variabili d'ambiente in development: imposta almeno `APP_ENV=development` (ora viene letta da `getenv`).
4. Prima del deploy in produzione: imposta `APP_ENV=production`, `DB_*`, `SMTP_*`, `ALLOWED_ORIGINS`, e blocca via web server gli script di debug.
5. Esegui un giro di smoke test manuali su: login, logout, registrazione (paziente con/senza codice nutrizionista, nutrizionista), check sessione, remember-me, search per provincia, assegnazione paziente, aggiunta progresso.
