<?php
/**
 * includes/db_connection.php
 * ---------------------------------------------------------------
 * - Singleton PDO (con flag persistenti opzionali via env).
 * - Helper di risposta JSON, sanitizzazione e auth.
 * - Headers di sicurezza (CORS allow-list, X-Frame, ecc.).
 * - Avvio sessione sicuro (HTTPS-aware).
 *
 * NB: include sempre PRIMA config.php per evitare le doppie
 *     definizioni di costanti del file legacy.
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// =========================================
// SESSIONE  (HTTPS-aware, lockdown cookie)
// =========================================
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// =========================================
// SINGLETON DB
// =========================================
final class Database
{
    private static ?Database $instance = null;
    private PDO $conn;
    private int $transactionCount = 0;

    private function __construct()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );

            $this->conn = new PDO(DB_USER ? $dsn : $dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,          // prepared statements reali
                PDO::ATTR_TIMEOUT            => 5,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            ]);

            if (isDevelopment()) {
                error_log('DB connection established.');
            }
        } catch (PDOException $e) {
            error_log('DB connection failure: ' . $e->getMessage());

            $payload = ['success' => false, 'error' => 'Servizio temporaneamente non disponibile'];
            if (isDevelopment()) {
                $payload['details'] = $e->getMessage();
            }
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload);
            exit;
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }

    /** Inizio transazione nidificabile. */
    public function beginTransaction(): bool
    {
        if ($this->transactionCount === 0) {
            $this->conn->beginTransaction();
        }
        $this->transactionCount++;
        return true;
    }

    public function commit(): bool
    {
        if ($this->transactionCount <= 0) return false;

        if ($this->transactionCount === 1) {
            $this->conn->commit();
        }
        $this->transactionCount--;
        return true;
    }

    public function rollback(): bool
    {
        if ($this->transactionCount <= 0) return false;

        // Un solo rollback porta lo stack a zero (semantica corretta).
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }
        $this->transactionCount = 0;
        return true;
    }

    public function inTransaction(): bool
    {
        return $this->transactionCount > 0;
    }

    private function __clone() {}
    public function __wakeup() { throw new RuntimeException('Cannot unserialize singleton'); }
}

// =========================================
// HELPER DB
// =========================================
function getDB(): PDO          { return Database::getInstance()->getConnection(); }
function beginTransaction(): bool { return Database::getInstance()->beginTransaction(); }
function commit(): bool        { return Database::getInstance()->commit(); }
function rollback(): bool      { return Database::getInstance()->rollback(); }

// =========================================
// HELPER RISPOSTA JSON
// =========================================
function sendJSON(array $data, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Legge e decodifica il body JSON UNA SOLA volta (caching).
 * Compatibile con la coesistenza di POST tradizionale (mantiene $_POST come priorità).
 */
function getJsonInput(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return $cache = [];

    $decoded = json_decode($raw, true);
    return $cache = (is_array($decoded) ? $decoded : []);
}

/**
 * Estrae un parametro accettando sia $_POST sia JSON body.
 */
function input(string $key, $default = null)
{
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    }
    $json = getJsonInput();
    return $json[$key] ?? $default;
}

// =========================================
// HELPER SICUREZZA
// =========================================

/**
 * Sanitizza testo per OUTPUT HTML (mai per il DB!).
 * Per il DB usa SEMPRE prepared statements.
 */
function escapeHtml(?string $data): string
{
    return $data === null ? '' : htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Pulizia leggera per input lato server: trim + strip control char.
 * Non altera caratteri visibili (es. & in &amp;) → sicuro per email/nomi.
 */
function cleanInput(?string $data): ?string
{
    if ($data === null) return null;
    $data = trim($data);
    // Rimuove caratteri di controllo invisibili (tranne tab/newline)
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
}

function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateSecureToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function needsPasswordRehash(string $hash): bool
{
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Hash di un token (es. remember-me) per lo storage in DB.
 * Salviamo solo l'hash: in caso di leak il token in chiaro non basta.
 */
function hashToken(string $token): string
{
    return hash('sha256', $token);
}

function logActivity(PDO $db, int $userId, string $action, string $description): void
{
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
            substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
        ]);
    } catch (PDOException $e) {
        error_log('logActivity: ' . $e->getMessage());
    }
}

// =========================================
// HELPER AUTH
// =========================================
function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']);
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        sendJSON(['success' => false, 'error' => 'Autenticazione richiesta'], 401);
    }
    // Verifica timeout sessione
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        sendJSON(['success' => false, 'error' => 'Sessione scaduta'], 401);
    }
}

function hasRole(string $role): bool
{
    return ($_SESSION['user_role'] ?? null) === $role;
}

function requireRole(string $role): void
{
    requireAuth();
    if (!hasRole($role)) {
        sendJSON(['success' => false, 'error' => 'Permessi insufficienti'], 403);
    }
}

/**
 * CSRF: genera/recupera token salvato in sessione.
 * Il client deve inviarlo nell'header X-CSRF-Token (o campo csrf_token).
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken(32);
    }
    return $_SESSION['csrf_token'];
}

function requireCsrf(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
        return;
    }
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? input('csrf_token', '');
    if (!is_string($sent) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        sendJSON(['success' => false, 'error' => 'CSRF token non valido'], 419);
    }
}

// =========================================
// HEADERS SICUREZZA + CORS allow-list
// =========================================
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, ALLOWED_ORIGINS, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Access-Control-Max-Age: 86400');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Preflight CORS
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Metodo HTTP supportato
$allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', $allowedMethods, true)) {
    sendJSON(['success' => false, 'error' => 'Metodo HTTP non supportato'], 405);
}
