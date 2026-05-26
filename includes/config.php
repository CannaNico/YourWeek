<?php
/**
 * includes/config.php
 * ---------------------------------------------------------------
 * Configurazione globale dell'applicazione.
 *
 * Linee guida:
 *   - Le costanti vengono definite UNA SOLA volta (idempotente).
 *   - Le credenziali di produzione DEVONO provenire da variabili
 *     d'ambiente (mai hardcoded).
 *   - Questo file viene incluso da db_connection.php, quindi è
 *     l'unico punto in cui definire DB_HOST/USER/ecc.
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

// =========================================
// AMBIENTE
// =========================================
//
// Priorità: variabile d'ambiente APP_ENV → fallback 'production'.
// In produzione DEVE essere impostato APP_ENV=production sul server.
//
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

// =========================================
// DATABASE  (idempotente)
// =========================================
$dbDefaults = [
    'DB_HOST'    => 'localhost',
    'DB_NAME'    => 'yourweek_db',
    'DB_USER'    => 'root',
    'DB_PASS'    => '',
    'DB_PORT'    => '3306',
    'DB_CHARSET' => 'utf8mb4',
];

foreach ($dbDefaults as $name => $default) {
    if (!defined($name)) {
        // In produzione obblighiamo le env vars: niente fallback insicuri
        if (APP_ENV === 'production') {
            $value = getenv($name);
            if ($value === false || $value === '') {
                // Log e blocca: meglio "down" che "compromesso"
                error_log("CONFIG ERROR: variabile d'ambiente $name mancante in produzione.");
                http_response_code(500);
                exit('Configurazione server non valida.');
            }
            define($name, $value);
        } else {
            define($name, getenv($name) !== false && getenv($name) !== ''
                ? getenv($name)
                : $default);
        }
    }
}

// =========================================
// URL
// =========================================
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/yourweek');
}
if (!defined('API_URL')) {
    define('API_URL', BASE_URL . '/api');
}

// CORS: lista esplicita di origini consentite (mai "*" con credentials).
if (!defined('ALLOWED_ORIGINS')) {
    $envOrigins = getenv('ALLOWED_ORIGINS');
    define('ALLOWED_ORIGINS', $envOrigins
        ? array_map('trim', explode(',', $envOrigins))
        : ['http://localhost', 'http://localhost:80', 'http://127.0.0.1']);
}

// =========================================
// UPLOAD
// =========================================
if (!defined('UPLOAD_DIR'))         define('UPLOAD_DIR', __DIR__ . '/../uploads/');
if (!defined('MAX_FILE_SIZE'))      define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
if (!defined('ALLOWED_FILE_TYPES')) define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
]);

// =========================================
// SESSIONE
// =========================================
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 7200); // 2 ore
if (!defined('SESSION_NAME'))     define('SESSION_NAME', 'YOURWEEK_SESSION');

// =========================================
// EMAIL (TODO – non usare credenziali demo)
// =========================================
if (!defined('SMTP_HOST')) define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
if (!defined('SMTP_USER')) define('SMTP_USER', getenv('SMTP_USER') ?: '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
if (!defined('FROM_EMAIL')) define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@yourweek.com');
if (!defined('FROM_NAME'))  define('FROM_NAME', getenv('FROM_NAME') ?: 'YourWeek');

// =========================================
// SICUREZZA
// =========================================
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', 8);
if (!defined('MAX_LOGIN_ATTEMPTS'))  define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('LOGIN_TIMEOUT'))       define('LOGIN_TIMEOUT', 900); // 15 min

// =========================================
// ERRORI E LOG
// =========================================
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }
    ini_set('error_log', $logDir . '/php_errors.log');
}

// =========================================
// TIMEZONE
// =========================================
date_default_timezone_set('Europe/Rome');

// =========================================
// HELPER
// =========================================

function isDevelopment(): bool { return APP_ENV === 'development'; }
function isProduction(): bool  { return APP_ENV === 'production'; }
function getBaseUrl(): string  { return BASE_URL; }
function getApiUrl(): string   { return API_URL; }

function ensureDirectoryExists(string $path): void {
    if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
        throw new RuntimeException("Impossibile creare la directory: $path");
    }
}

// Assicura che uploads esista (senza far crashare l'app se permessi mancanti)
try {
    ensureDirectoryExists(UPLOAD_DIR);
} catch (Throwable $e) {
    error_log('UPLOAD_DIR non disponibile: ' . $e->getMessage());
}

// Autoload classi custom (opzionale)
spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/classes/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
