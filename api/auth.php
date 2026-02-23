<?php
// api/auth.php
// Sistema di autenticazione sicuro - VERSIONE CORRETTA
require_once __DIR__ . '/../includes/db_connection.php';

// Impedisci accesso diretto al file senza azione
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleLogin();
        }
        break;

    case 'logout':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
            handleLogout();
        }
        break;

    case 'check':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            checkAuth();
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handleRegister();
        }
        break;

    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

// === FUNZIONI AUTENTICAZIONE ===

function handleLogin()
{
    $db = getDB();

    // Supporta sia POST tradizionale che JSON
    if (isset($_POST['email'])) {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'] ?? '';
        $selectedRole = sanitizeInput($_POST['selected_role'] ?? 'paziente');
        $rememberMe = !empty($_POST['remember_me']);
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = sanitizeInput($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $selectedRole = sanitizeInput($data['selected_role'] ?? 'paziente');
        $rememberMe = !empty($data['remember_me']);
    }

    // DEBUG - Rimuovi in produzione
    error_log("Login attempt - Email: $email, Selected Role: $selectedRole");

    // Validazione input
    if (empty($email) || empty($password)) {
        sendJSON(['success' => false, 'error' => 'Email e password richiesti'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJSON(['success' => false, 'error' => 'Email non valida'], 400);
    }

    try {
        // Recupera utente dal database
        $stmt = $db->prepare("
            SELECT 
                id, 
                email, 
                password_hash, 
                first_name, 
                last_name, 
                role,
                is_active,
                email_verified
            FROM users 
            WHERE email = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // DEBUG - Rimuovi in produzione
        error_log("User found: " . ($user ? "YES" : "NO"));

        // Verifica esistenza utente
        if (!$user) {
            sleep(1); // Previene timing attacks
            sendJSON(['success' => false, 'error' => 'Credenziali non valide'], 401);
        }

        // Verifica password
        if (!password_verify($password, $user['password_hash'])) {
            // Log tentativo fallito
            error_log("Password verify failed for: $email");
            logFailedLogin($db, $email);
            sleep(1); // Previene timing attacks
            sendJSON(['success' => false, 'error' => 'Credenziali non valide'], 401);
        }

        // DEBUG - Password verificata
        error_log("Password verified successfully for: $email");

        // NUOVA VERIFICA: Controlla che il ruolo selezionato corrisponda al ruolo dell'utente
        // Normalizza i ruoli: 'cliente' e 'paziente' sono equivalenti
        $userRole = $user['role'];
        $normalizedSelectedRole = ($selectedRole === 'cliente') ? 'paziente' : $selectedRole;
        $normalizedUserRole = ($userRole === 'cliente') ? 'paziente' : $userRole;

        if ($normalizedSelectedRole !== $normalizedUserRole) {
            error_log("Role mismatch - Selected: $selectedRole, Actual: $userRole");
            sendJSON([
                'success' => false,
                'error' => 'Ruolo errato',
                'error_code' => 'ROLE_MISMATCH'
            ], 403);
        }

        // Rigenera session ID per prevenire session fixation
        session_regenerate_id(true);

        // DEBUG - Session rigenerato
        error_log("Session regenerated for user: " . $user['id']);

        // Salva dati in sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // DEBUG - Session salvata
        error_log("Session data saved for user: " . $user['id']);

        // Aggiorna ultimo accesso
        updateLastLogin($db, $user['id']);

        // Se è un nutrizionista, verifica/genera codice univoco
        $nutritionistCode = null;
        if ($user['role'] === 'nutrizionista') {
            $nutritionistCode = ensureNutritionistCode($db, $user['id']);
        }

        // Cookie "Ricordami" – solo se l'utente ha accettato i cookie E ha spuntato "Ricordami"
        $cookieConsent = $_COOKIE['yw_cookie_consent'] ?? '';
        if ($rememberMe && $cookieConsent === 'accepted') {
            $rememberToken = bin2hex(random_bytes(32));
            // Salva il token nel DB (colonna remember_token nella tabella users)
            try {
                $stmt = $db->prepare("UPDATE users SET remember_token = ?, remember_token_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?");
                $stmt->execute([$rememberToken, $user['id']]);
                // Imposta cookie persistente (30 giorni)
                setcookie(
                    'yw_remember',
                    $rememberToken,
                    [
                        'expires' => time() + (30 * 24 * 60 * 60),
                        'path' => '/',
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]
                );
            } catch (PDOException $e) {
                // Non blocca il login se fallisce il remember
                error_log("Errore salvataggio remember token: " . $e->getMessage());
            }
        }

        // Log accesso riuscito
        logActivity($db, $user['id'], 'login', 'Accesso effettuato');

        // DEBUG - Login completato
        error_log("Login successful for user: " . $user['id']);

        // Risposta
        $response = [
            'success' => true,
            'message' => 'Login effettuato con successo',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role']
            ]
        ];

        // Aggiungi codice nutrizionista se presente
        if ($nutritionistCode) {
            $response['user']['nutritionist_code'] = $nutritionistCode;
        }

        sendJSON($response);

    } catch (PDOException $e) {
        error_log("Errore login: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il login'], 500);
    }
}

function handleLogout()
{
    if (isset($_SESSION['user_id'])) {
        $db = getDB();
        logActivity($db, $_SESSION['user_id'], 'logout', 'Logout effettuato');
        // Invalida il remember token nel DB
        try {
            $stmt = $db->prepare("UPDATE users SET remember_token = NULL, remember_token_expires = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log("Errore pulizia remember token: " . $e->getMessage());
        }
    }

    // Distruggi sessione
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    // Cancella anche il cookie "Ricordami"
    setcookie('yw_remember', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    sendJSON(['success' => true, 'message' => 'Logout effettuato']);
}

function checkAuth()
{
    if (isset($_SESSION['user_id'])) {
        // Verifica timeout sessione (2 ore)
        $sessionTimeout = 7200;
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $sessionTimeout)) {
            handleLogout();
            sendJSON([
                'success' => true,
                'authenticated' => false
            ]);
            return;
        }

        sendJSON([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role'],
                'name' => $_SESSION['user_name']
            ]
        ]);
    } else {
        // Tenta auto-login tramite cookie "Ricordami" se presente
        $rememberToken = $_COOKIE['yw_remember'] ?? '';
        if (!empty($rememberToken)) {
            $db = getDB();
            try {
                $stmt = $db->prepare("
                    SELECT id, email, first_name, last_name, role
                    FROM users
                    WHERE remember_token = ?
                      AND remember_token_expires > NOW()
                      AND is_active = 1
                    LIMIT 1
                ");
                $stmt->execute([$rememberToken]);
                $user = $stmt->fetch();
                if ($user) {
                    // Ripristina sessione
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['login_time'] = time();
                    updateLastLogin($db, $user['id']);
                    sendJSON([
                        'success' => true,
                        'authenticated' => true,
                        'auto_login' => true,
                        'user' => [
                            'id' => $user['id'],
                            'email' => $user['email'],
                            'role' => $user['role'],
                            'name' => $user['first_name'] . ' ' . $user['last_name']
                        ]
                    ]);
                    return;
                }
            } catch (PDOException $e) {
                error_log("Errore auto-login remember: " . $e->getMessage());
            }
        }
        sendJSON([
            'success' => true,
            'authenticated' => false
        ]);
    }
}

function handleRegister()
{
    error_log("=== INIZIO REGISTRAZIONE ==="); // DEBUG

    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);

    error_log("Dati ricevuti: " . print_r($data, true)); // DEBUG

    $db = getDB();

    // Supporta sia POST tradizionale che JSON
    if (isset($_POST['email'])) {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'] ?? '';
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'paziente');
        $birthDate = $_POST['birth_date'] ?? null;
        $nutritionistCode = sanitizeInput($_POST['nutritionist_code'] ?? '');
        $provincia = strtoupper(trim($_POST['provincia'] ?? ''));
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = sanitizeInput($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $firstName = sanitizeInput($data['first_name'] ?? '');
        $lastName = sanitizeInput($data['last_name'] ?? '');
        $role = sanitizeInput($data['role'] ?? 'paziente');
        $birthDate = $data['birth_date'] ?? null;
        $nutritionistCode = sanitizeInput($data['nutritionist_code'] ?? '');
        $provincia = strtoupper(trim($data['provincia'] ?? ''));
    }

    // Controlli validazione
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
        sendJSON(['success' => false, 'error' => 'Tutti i campi obbligatori devono essere compilati'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJSON(['success' => false, 'error' => 'Email non valida'], 400);
    }

    if (strlen($password) < 8) {
        sendJSON(['success' => false, 'error' => 'La password deve essere di almeno 8 caratteri'], 400);
    }

    if (!in_array($role, ['paziente', 'nutrizionista'])) {
        sendJSON(['success' => false, 'error' => 'Ruolo non valido'], 400);
    }

    // Validazione provincia (deve essere un codice di 2 lettere come in search_users)
    if (empty($provincia) || strlen($provincia) !== 2) {
        sendJSON(['success' => false, 'error' => 'Provincia non valida'], 400);
    }

    try {
        // Verifica se email esiste già
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Email già registrata'], 409);
        }

        // Se è un paziente e ha fornito un codice nutrizionista, verifica che esista
        $nutritionistId = null;
        if ($role === 'paziente' && !empty($nutritionistCode)) {
            $stmt = $db->prepare("
                SELECT id FROM users 
                WHERE nutritionist_code = ? AND role = 'nutrizionista' AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$nutritionistCode]);
            $nutritionist = $stmt->fetch();

            if (!$nutritionist) {
                sendJSON(['success' => false, 'error' => 'Codice nutrizionista non valido'], 400);
            }

            $nutritionistId = $nutritionist['id'];
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Inserisci nuovo utente (includendo la provincia)
        $stmt = $db->prepare("
            INSERT INTO Users (
                email, 
                password_hash, 
                first_name, 
                last_name,
                provincia,
                role,
                birth_date,
                nutritionist_id,
                registration_date,
                is_active,
                email_verified
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, 0)
        ");

        $stmt->execute([
            $email,
            $passwordHash,
            $firstName,
            $lastName,
            $provincia,
            $role,
            $birthDate,
            $nutritionistId
        ]);

        $userId = $db->lastInsertId();

        // Log registrazione
        logActivity($db, $userId, 'register', 'Nuovo account creato');

        sendJSON([
            'success' => true,
            'message' => 'Registrazione completata con successo',
            'user_id' => $userId
        ], 201);

    } catch (PDOException $e) {
        error_log("Errore registrazione: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante la registrazione'], 500);
    }
}

// === FUNZIONI HELPER ===

function updateLastLogin($db, $userId)
{
    try {
        $stmt = $db->prepare("UPDATE Users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Errore aggiornamento ultimo accesso: " . $e->getMessage());
    }
}

function logFailedLogin($db, $email)
{
    try {
        $stmt = $db->prepare("
            INSERT INTO LoginAttempts (email, ip_address, attempt_time, success)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([
            $email,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Errore log tentativo fallito: " . $e->getMessage());
    }
}

/**
 * Genera un codice univoco per nutrizionista
 */
function generateNutritionistCode($db)
{
    $maxAttempts = 10;
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        // Genera codice nel formato NUT-XXXXX (5 cifre)
        $code = 'NUT-' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);

        // Verifica se il codice esiste già
        $stmt = $db->prepare("SELECT id FROM users WHERE nutritionist_code = ? LIMIT 1");
        $stmt->execute([$code]);

        if (!$stmt->fetch()) {
            return $code; // Codice univoco trovato
        }

        $attempt++;
    }

    // Fallback: usa timestamp se non riesce a generare un codice univoco
    return 'NUT-' . substr(time(), -5);
}

/**
 * Assicura che un nutrizionista abbia un codice univoco
 * Se non ce l'ha, ne genera uno
 */
function ensureNutritionistCode($db, $userId)
{
    try {
        // Verifica se ha già un codice
        $stmt = $db->prepare("SELECT nutritionist_code FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && !empty($user['nutritionist_code'])) {
            return $user['nutritionist_code'];
        }

        // Genera nuovo codice
        $code = generateNutritionistCode($db);

        // Aggiorna il database
        $stmt = $db->prepare("UPDATE users SET nutritionist_code = ? WHERE id = ?");
        $stmt->execute([$code, $userId]);

        error_log("Generated nutritionist code: $code for user: $userId");

        return $code;

    } catch (PDOException $e) {
        error_log("Errore generazione codice nutrizionista: " . $e->getMessage());
        return null;
    }
}
?>