<?php
// api/auth.php
// Sistema di autenticazione sicuro
require_once '../includes/db_connection.php';

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

function handleLogin() {
    $db = getDB();
    
    // Supporta sia POST tradizionale che JSON
    if (isset($_POST['email'])) {
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'] ?? '';
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = sanitizeInput($data['email'] ?? '');
        $password = $data['password'] ?? '';
    }
    
    // DEBUG
    error_log("Login attempt - Email: $email, Password: $password");
    
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
            FROM Users 
            WHERE email = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Verifica esistenza utente
        if (!$user) {
            // Usa messaggio generico per sicurezza (non rivelare se l'email esiste)
            sleep(1); // Previene timing attacks
            sendJSON(['success' => false, 'error' => 'Credenziali non valide'], 401);
        }
        
        // Verifica password
        if (!password_verify($password, $user['password_hash'])) {
            // Log tentativo fallito (opzionale)
            logFailedLogin($db, $email);
            sleep(1); // Previene timing attacks
            sendJSON(['success' => false, 'error' => 'Credenziali non valide'], 401);
        }
        
        // Verifica email (opzionale, commentato per ora)
        /*
        if (!$user['email_verified']) {
            sendJSON(['success' => false, 'error' => 'Email non verificata'], 403);
        }
        */
        
        // Rigenera session ID per prevenire session fixation
        session_regenerate_id(true);
        
        // Salva dati in sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Aggiorna ultimo accesso
        updateLastLogin($db, $user['id']);
        
        // Log accesso riuscito
        logActivity($db, $user['id'], 'login', 'Accesso effettuato');
        
        // Risposta
        sendJSON([
            'success' => true,
            'message' => 'Login effettuato con successo',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role']
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore login: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il login'], 500);
    }
}

function handleLogout() {
    if (isset($_SESSION['user_id'])) {
        $db = getDB();
        logActivity($db, $_SESSION['user_id'], 'logout', 'Logout effettuato');
    }
    
    // Distruggi sessione
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    sendJSON(['success' => true, 'message' => 'Logout effettuato']);
}

function checkAuth() {
    if (isset($_SESSION['user_id'])) {
        // Verifica timeout sessione (opzionale - 2 ore)
        $sessionTimeout = 7200; // 2 ore in secondi
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $sessionTimeout)) {
            handleLogout();
        }
        
        // Verifica IP address (opzionale - security extra)
        /*
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            handleLogout();
        }
        */
        
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
        sendJSON([
            'success' => true,
            'authenticated' => false
        ]);
    }
}

function handleRegister() {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validazione input
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $firstName = sanitizeInput($data['first_name'] ?? '');
    $lastName = sanitizeInput($data['last_name'] ?? '');
    $role = sanitizeInput($data['role'] ?? 'paziente');
    $birthDate = $data['birth_date'] ?? null;
    
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
    
    // Verifica password complexity (opzionale)
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        sendJSON(['success' => false, 'error' => 'La password deve contenere maiuscole, minuscole e numeri'], 400);
    }
    
    if (!in_array($role, ['paziente', 'nutrizionista'])) {
        sendJSON(['success' => false, 'error' => 'Ruolo non valido'], 400);
    }
    
    try {
        // Verifica se email esiste già
        $stmt = $db->prepare("SELECT id FROM Users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Email già registrata'], 409);
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Inserisci nuovo utente
        $stmt = $db->prepare("
            INSERT INTO Users (
                email, 
                password_hash, 
                first_name, 
                last_name, 
                role,
                birth_date,
                registration_date,
                is_active,
                email_verified
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, 0)
        ");
        
        $stmt->execute([
            $email,
            $passwordHash,
            $firstName,
            $lastName,
            $role,
            $birthDate
        ]);
        
        $userId = $db->lastInsertId();
        
        // Log registrazione
        logActivity($db, $userId, 'register', 'Nuovo account creato');
        
        // TODO: Invia email di verifica
        // sendVerificationEmail($email, $userId);
        
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

function updateLastLogin($db, $userId) {
    try {
        $stmt = $db->prepare("UPDATE Users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        error_log("Errore aggiornamento ultimo accesso: " . $e->getMessage());
    }
}

function logFailedLogin($db, $email) {
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
?>