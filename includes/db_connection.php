<?php
// includes/db_connection.php
// File di connessione al database con best practices

// === CONFIGURAZIONE DATABASE ===
// IMPORTANTE: In produzione, usa variabili d'ambiente invece di costanti hardcoded
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'yourweek_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Configurazione sessione sicura (solo se la sessione NON è ancora attiva)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Imposta a 1 se usi HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// === CLASSE DATABASE (SINGLETON PATTERN) ===
class Database {
    private static $instance = null;
    private $conn;
    private $transactionCount = 0;
    
    private function __construct() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                // Modalità errore: lancia eccezioni
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                // Fetch mode di default: array associativo
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                
                // Disabilita prepared statements emulati per maggiore sicurezza
                PDO::ATTR_EMULATE_PREPARES => false,
                
                // Usa buffer per le query
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                
                // Connessioni persistenti (opzionale, commentato per default)
                // PDO::ATTR_PERSISTENT => true,
                
                // Timeout connessione
                PDO::ATTR_TIMEOUT => 5,
                
                // Set charset nella connessione
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Log connessione riuscita (solo in development)
            if (getenv('APP_ENV') === 'development') {
                error_log("Connessione database stabilita con successo");
            }
            
        } catch (PDOException $e) {
            // Log errore (non mostrare dettagli all'utente)
            error_log("Errore connessione database: " . $e->getMessage());
            
            // In produzione, mostra messaggio generico
            if (getenv('APP_ENV') === 'production') {
                die(json_encode([
                    'success' => false,
                    'error' => 'Servizio temporaneamente non disponibile'
                ]));
            } else {
                // In development, mostra dettagli
                die(json_encode([
                    'success' => false,
                    'error' => 'Errore di connessione al database',
                    'details' => $e->getMessage()
                ]));
            }
        }
    }
    
    /**
     * Ottieni istanza singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ottieni connessione PDO
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Inizia transazione (supporta transazioni nidificate)
     */
    public function beginTransaction() {
        if ($this->transactionCount === 0) {
            $this->conn->beginTransaction();
        }
        $this->transactionCount++;
        return $this->transactionCount >= 0;
    }
    
    /**
     * Commit transazione
     */
    public function commit() {
        if ($this->transactionCount === 1) {
            $this->conn->commit();
        }
        $this->transactionCount = max(0, $this->transactionCount - 1);
        return $this->transactionCount >= 0;
    }
    
    /**
     * Rollback transazione
     */
    public function rollback() {
        if ($this->transactionCount === 1) {
            $this->conn->rollBack();
        }
        $this->transactionCount = max(0, $this->transactionCount - 1);
        return $this->transactionCount >= 0;
    }
    
    /**
     * Verifica se in transazione
     */
    public function inTransaction() {
        return $this->transactionCount > 0;
    }
    
    /**
     * Previeni clonazione
     */
    private function __clone() {}
    
    /**
     * Previeni unserialize
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// === FUNZIONI HELPER ===

/**
 * Ottieni connessione database
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Inizia transazione
 */
function beginTransaction() {
    return Database::getInstance()->beginTransaction();
}

/**
 * Commit transazione
 */
function commit() {
    return Database::getInstance()->commit();
}

/**
 * Rollback transazione
 */
function rollback() {
    return Database::getInstance()->rollback();
}

/**
 * Invia risposta JSON e termina esecuzione
 * @param array $data Dati da inviare
 * @param int $statusCode Codice HTTP
 */
function sendJSON($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff'); // Security header
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Sanitizza input per prevenire XSS
 * @param string $data Input da sanitizzare
 * @return string Input sanitizzato
 */
function sanitizeInput($data) {
    if ($data === null) {
        return null;
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

/**
 * Valida email
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Genera token casuale sicuro
 * @param int $length Lunghezza token
 * @return string Token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password in modo sicuro
 * @param string $password
 * @return string Hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica password contro hash
 * @param string $password Password in chiaro
 * @param string $hash Hash da verificare
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Verifica se l'hash della password necessita rehash
 * @param string $hash
 * @return bool
 */
function needsPasswordRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Log attività nel database
 * @param PDO $db Connessione database
 * @param int $userId ID utente
 * @param string $action Azione eseguita
 * @param string $description Descrizione
 */
function logActivity($db, $userId, $action, $description) {
    try {
        $stmt = $db->prepare("
            INSERT INTO ActivityLog (user_id, action, description, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Errore log attività: " . $e->getMessage());
    }
}

/**
 * Verifica se utente è autenticato
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Richiedi autenticazione (invia errore se non autenticato)
 */
function requireAuth() {
    if (!isAuthenticated()) {
        sendJSON(['success' => false, 'error' => 'Autenticazione richiesta'], 401);
    }
}

/**
 * Verifica ruolo utente
 * @param string $role Ruolo richiesto
 * @return bool
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Richiedi ruolo specifico
 * @param string $role Ruolo richiesto
 */
function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        sendJSON(['success' => false, 'error' => 'Permessi insufficienti'], 403);
    }
}

// === HEADERS SICUREZZA ===
// Imposta headers di sicurezza per tutte le risposte

// CORS - Configura secondo le tue esigenze
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Se c'è un origin lo riflettiamo (necessario per credentials: 'include')
if (!empty($origin)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // Cache preflight per 24 ore

// Security headers
header('X-Frame-Options: DENY'); // Previene clickjacking
header('X-Content-Type-Options: nosniff'); // Previene MIME sniffing
header('X-XSS-Protection: 1; mode=block'); // XSS protection (legacy)
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy (personalizza secondo necessità)
// header("Content-Security-Policy: default-src 'self'");

// Gestione richieste OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verifica metodo HTTP supportato
$allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
    sendJSON(['success' => false, 'error' => 'Metodo HTTP non supportato'], 405);
}
?>