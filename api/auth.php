<?php
/**
 * api/auth.php
 * ---------------------------------------------------------------
 * Sistema di autenticazione.
 *
 * Fix principali rispetto alla versione originale:
 *   - Nessun error_log() di credenziali o PII (GDPR).
 *   - Nome tabella SEMPRE 'Users' (consistente con lo schema; era
 *     mescolato 'users'/'Users' → fail su Linux case-sensitive).
 *   - Rimossa la doppia lettura di php://input in handleRegister
 *     (era un bug logico: $data sovrascritto).
 *   - Rate limiting effettivo: blocco dopo MAX_LOGIN_ATTEMPTS in
 *     LOGIN_TIMEOUT secondi (per IP+email).
 *   - Remember-me token HASHATO nel DB (se leak, non utilizzabile).
 *   - sanitizeInput → cleanInput (no htmlspecialchars su input DB).
 *   - Codice nutrizionista generato in registrazione, non al login.
 *   - try/catch globale per evitare info leak.
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_connection.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($action) {
        case 'login':
            if ($method !== 'POST') methodNotAllowed();
            handleLogin();
            break;

        case 'logout':
            // Logout consentito anche via GET per link diretti
            handleLogout();
            break;

        case 'check':
            if ($method !== 'GET') methodNotAllowed();
            checkAuth();
            break;

        case 'register':
            if ($method !== 'POST') methodNotAllowed();
            handleRegister();
            break;

        case 'csrf':
            if ($method !== 'GET') methodNotAllowed();
            sendJSON(['success' => true, 'csrf_token' => getCsrfToken()]);
            break;

        default:
            sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
    }
} catch (Throwable $e) {
    error_log('auth.php uncaught: ' . $e->getMessage());
    sendJSON(['success' => false, 'error' => 'Errore interno del server'], 500);
}

// =========================================
// HANDLERS
// =========================================

function handleLogin(): void
{
    $db = getDB();

    $email          = strtolower(trim((string) input('email', '')));
    $password       = (string) input('password', '');
    $selectedRole   = strtolower((string) input('selected_role', 'paziente'));
    $rememberMe     = !empty(input('remember_me'));
    $clientIp       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if ($email === '' || $password === '') {
        sendJSON(['success' => false, 'error' => 'Email e password richiesti'], 400);
    }
    if (!validateEmail($email)) {
        sendJSON(['success' => false, 'error' => 'Email non valida'], 400);
    }

    // Rate limiting
    if (isLoginRateLimited($db, $email, $clientIp)) {
        sendJSON([
            'success' => false,
            'error'   => 'Troppi tentativi falliti. Riprova tra qualche minuto.',
        ], 429);
    }

    $stmt = $db->prepare("
        SELECT id, email, password_hash, first_name, last_name, role, is_active, email_verified
        FROM Users
        WHERE email = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        logFailedLogin($db, $email, $clientIp);
        // Costant-time response: random sleep 200–500ms (non blocca quanto sleep(1))
        usleep(random_int(200_000, 500_000));
        sendJSON(['success' => false, 'error' => 'Credenziali non valide'], 401);
    }

    // Verifica corrispondenza ruolo (paziente == cliente)
    $normalize = static fn (string $r): string => ($r === 'cliente' ? 'paziente' : $r);
    if ($normalize($selectedRole) !== $normalize((string) $user['role'])) {
        sendJSON([
            'success'    => false,
            'error'      => 'Ruolo errato',
            'error_code' => 'ROLE_MISMATCH',
        ], 403);
    }

    // Rehash password se cost obsoleto
    if (needsPasswordRehash($user['password_hash'])) {
        $newHash = hashPassword($password);
        $upd = $db->prepare('UPDATE Users SET password_hash = ? WHERE id = ?');
        $upd->execute([$newHash, $user['id']]);
    }

    // Anti session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = trim($user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $clientIp;

    updateLastLogin($db, (int) $user['id']);

    // Codice nutrizionista (se mancante, lo generiamo qui)
    $nutritionistCode = null;
    if ($user['role'] === 'nutrizionista') {
        $nutritionistCode = ensureNutritionistCode($db, (int) $user['id']);
    }

    // Cookie "Ricordami" (solo se cookie accettati)
    $cookieConsent = $_COOKIE['yw_cookie_consent'] ?? '';
    if ($rememberMe && $cookieConsent === 'accepted') {
        setRememberMeCookie($db, (int) $user['id']);
    }

    logActivity($db, (int) $user['id'], 'login', 'Accesso effettuato');

    $response = [
        'success'   => true,
        'message'   => 'Login effettuato con successo',
        'csrf_token'=> getCsrfToken(),
        'user'      => [
            'id'    => (int) $user['id'],
            'email' => $user['email'],
            'name'  => trim($user['first_name'] . ' ' . $user['last_name']),
            'role'  => $user['role'],
        ],
    ];
    if ($nutritionistCode !== null) {
        $response['user']['nutritionist_code'] = $nutritionistCode;
    }
    sendJSON($response);
}

function handleLogout(): void
{
    if (isset($_SESSION['user_id'])) {
        $db = getDB();
        logActivity($db, (int) $_SESSION['user_id'], 'logout', 'Logout effettuato');

        // Invalida remember token
        try {
            $stmt = $db->prepare("
                UPDATE Users SET remember_token = NULL, remember_token_expires = NULL WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log('handleLogout: ' . $e->getMessage());
        }
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires'  => time() - 42_000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Strict',
            ]
        );
    }
    session_destroy();

    setcookie('yw_remember', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);

    sendJSON(['success' => true, 'message' => 'Logout effettuato']);
}

function checkAuth(): void
{
    if (isset($_SESSION['user_id'])) {
        // Verifica timeout sessione
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            handleLogout(); // exit
        }

        sendJSON([
            'success'       => true,
            'authenticated' => true,
            'csrf_token'    => getCsrfToken(),
            'user'          => [
                'id'    => (int) $_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? '',
                'role'  => $_SESSION['user_role'] ?? '',
                'name'  => $_SESSION['user_name'] ?? '',
            ],
        ]);
    }

    // Tenta auto-login via remember token
    $rememberToken = (string) ($_COOKIE['yw_remember'] ?? '');
    if ($rememberToken !== '') {
        $db = getDB();
        try {
            $stmt = $db->prepare("
                SELECT id, email, first_name, last_name, role
                FROM Users
                WHERE remember_token = ?
                  AND remember_token_expires > NOW()
                  AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([hashToken($rememberToken)]);
            $user = $stmt->fetch();
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = (int) $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['user_name']  = trim($user['first_name'] . ' ' . $user['last_name']);
                $_SESSION['login_time'] = time();
                updateLastLogin($db, (int) $user['id']);

                // Rotate remember token (best practice)
                setRememberMeCookie($db, (int) $user['id']);

                sendJSON([
                    'success'       => true,
                    'authenticated' => true,
                    'auto_login'    => true,
                    'csrf_token'    => getCsrfToken(),
                    'user'          => [
                        'id'    => (int) $user['id'],
                        'email' => $user['email'],
                        'role'  => $user['role'],
                        'name'  => trim($user['first_name'] . ' ' . $user['last_name']),
                    ],
                ]);
            }
        } catch (PDOException $e) {
            error_log('checkAuth auto-login: ' . $e->getMessage());
        }
    }

    sendJSON(['success' => true, 'authenticated' => false]);
}

function handleRegister(): void
{
    $db = getDB();

    $email             = strtolower(trim((string) input('email', '')));
    $password          = (string) input('password', '');
    $firstName         = cleanInput((string) input('first_name', ''));
    $lastName          = cleanInput((string) input('last_name', ''));
    $role              = strtolower((string) input('role', 'paziente'));
    $birthDate         = input('birth_date'); // può essere null
    $nutritionistCode  = strtoupper(trim((string) input('nutritionist_code', '')));
    $provincia         = strtoupper(trim((string) input('provincia', '')));

    if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
        sendJSON(['success' => false, 'error' => 'Tutti i campi obbligatori devono essere compilati'], 400);
    }
    if (!validateEmail($email)) {
        sendJSON(['success' => false, 'error' => 'Email non valida'], 400);
    }
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        sendJSON(['success' => false, 'error' => 'Password troppo corta (min ' . PASSWORD_MIN_LENGTH . ' caratteri)'], 400);
    }
    if (!in_array($role, ['paziente', 'nutrizionista'], true)) {
        sendJSON(['success' => false, 'error' => 'Ruolo non valido'], 400);
    }
    if (strlen($provincia) !== 2 || !ctype_alpha($provincia)) {
        sendJSON(['success' => false, 'error' => 'Provincia non valida'], 400);
    }
    if ($birthDate !== null && $birthDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $birthDate)) {
        sendJSON(['success' => false, 'error' => 'Data di nascita non valida'], 400);
    }

    try {
        // Email univoca
        $stmt = $db->prepare('SELECT id FROM Users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Email già registrata'], 409);
        }

        // Codice nutrizionista per paziente (opzionale)
        $nutritionistId = null;
        if ($role === 'paziente' && $nutritionistCode !== '') {
            $stmt = $db->prepare("
                SELECT id FROM Users
                WHERE nutritionist_code = ? AND role = 'nutrizionista' AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$nutritionistCode]);
            $nutritionist = $stmt->fetch();
            if (!$nutritionist) {
                sendJSON(['success' => false, 'error' => 'Codice nutrizionista non valido'], 400);
            }
            $nutritionistId = (int) $nutritionist['id'];
        }

        // Codice nutrizionista per nuovo nutrizionista (generato qui, non al login)
        $newNutritionistCode = null;
        if ($role === 'nutrizionista') {
            $newNutritionistCode = generateUniqueNutritionistCode($db);
        }

        $passwordHash = hashPassword($password);

        $stmt = $db->prepare("
            INSERT INTO Users (
                email, password_hash, first_name, last_name,
                provincia, role, birth_date, nutritionist_id,
                nutritionist_code, registration_date, is_active, email_verified
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, 0)
        ");
        $stmt->execute([
            $email,
            $passwordHash,
            $firstName,
            $lastName,
            $provincia,
            $role,
            ($birthDate ?: null),
            $nutritionistId,
            $newNutritionistCode,
        ]);

        $userId = (int) $db->lastInsertId();
        logActivity($db, $userId, 'register', 'Nuovo account creato');

        $response = [
            'success' => true,
            'message' => 'Registrazione completata con successo',
            'user_id' => $userId,
        ];
        if ($newNutritionistCode) {
            $response['nutritionist_code'] = $newNutritionistCode;
        }
        sendJSON($response, 201);

    } catch (PDOException $e) {
        error_log('handleRegister: ' . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante la registrazione'], 500);
    }
}

// =========================================
// HELPERS
// =========================================

function methodNotAllowed(): void
{
    sendJSON(['success' => false, 'error' => 'Metodo non consentito'], 405);
}

function updateLastLogin(PDO $db, int $userId): void
{
    try {
        $stmt = $db->prepare('UPDATE Users SET last_login = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log('updateLastLogin: ' . $e->getMessage());
    }
}

function logFailedLogin(PDO $db, string $email, string $ip): void
{
    try {
        $stmt = $db->prepare("
            INSERT INTO LoginAttempts (email, ip_address, attempt_time, success)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([$email, $ip]);
    } catch (PDOException $e) {
        error_log('logFailedLogin: ' . $e->getMessage());
    }
}

/**
 * Restituisce true se l'utente/IP ha superato MAX_LOGIN_ATTEMPTS
 * tentativi falliti negli ultimi LOGIN_TIMEOUT secondi.
 */
function isLoginRateLimited(PDO $db, string $email, string $ip): bool
{
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM LoginAttempts
            WHERE success = 0
              AND attempt_time > (NOW() - INTERVAL :seconds SECOND)
              AND (email = :email OR ip_address = :ip)
        ");
        $stmt->execute([
            ':seconds' => LOGIN_TIMEOUT,
            ':email'   => $email,
            ':ip'      => $ip,
        ]);
        return ((int) $stmt->fetchColumn()) >= MAX_LOGIN_ATTEMPTS;
    } catch (PDOException $e) {
        error_log('isLoginRateLimited: ' . $e->getMessage());
        return false; // Fail-open: meglio non bloccare per errore DB del log
    }
}

/**
 * Imposta cookie remember-me: salva nel DB SOLO l'hash del token.
 */
function setRememberMeCookie(PDO $db, int $userId): void
{
    try {
        $token = generateSecureToken(32);
        $stmt  = $db->prepare("
            UPDATE Users
            SET remember_token = ?, remember_token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY)
            WHERE id = ?
        ");
        $stmt->execute([hashToken($token), $userId]);

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('yw_remember', $token, [
            'expires'  => time() + 30 * 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => $secure,
        ]);
    } catch (PDOException $e) {
        error_log('setRememberMeCookie: ' . $e->getMessage());
    }
}

function generateUniqueNutritionistCode(PDO $db): string
{
    $stmt = $db->prepare('SELECT 1 FROM Users WHERE nutritionist_code = ? LIMIT 1');
    for ($i = 0; $i < 10; $i++) {
        $code = 'NUT-' . str_pad((string) random_int(10_000, 99_999), 5, '0', STR_PAD_LEFT);
        $stmt->execute([$code]);
        if (!$stmt->fetch()) return $code;
    }
    // Fallback estremamente improbabile
    return 'NUT-' . substr((string) time(), -5);
}

function ensureNutritionistCode(PDO $db, int $userId): ?string
{
    try {
        $stmt = $db->prepare('SELECT nutritionist_code FROM Users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if ($user && !empty($user['nutritionist_code'])) {
            return (string) $user['nutritionist_code'];
        }
        $code = generateUniqueNutritionistCode($db);
        $upd  = $db->prepare('UPDATE Users SET nutritionist_code = ? WHERE id = ?');
        $upd->execute([$code, $userId]);
        return $code;
    } catch (PDOException $e) {
        error_log('ensureNutritionistCode: ' . $e->getMessage());
        return null;
    }
}
