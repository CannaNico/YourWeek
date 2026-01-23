<?php
// includes/config.php
// File di configurazione globale

// =========================================
// CONFIGURAZIONE AMBIENTE
// =========================================

// Ambiente: 'development' o 'production'
define('APP_ENV', 'development');

// =========================================
// CONFIGURAZIONE DATABASE
// =========================================

// In development usa valori hardcoded
// In production USA VARIABILI D'AMBIENTE!
if (APP_ENV === 'development') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'yourweek_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');  // Lascia vuoto se non hai password
    define('DB_PORT', '3306');
} else {
    // Production - usa variabili d'ambiente
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
}

define('DB_CHARSET', 'utf8mb4');

// =========================================
// CONFIGURAZIONE URL
// =========================================

define('BASE_URL', 'http://localhost/yourweek');
define('API_URL', BASE_URL . '/api');

// =========================================
// CONFIGURAZIONE FILE UPLOAD
// =========================================

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// =========================================
// CONFIGURAZIONE SESSIONE
// =========================================

define('SESSION_LIFETIME', 7200); // 2 ore in secondi
define('SESSION_NAME', 'YOURWEEK_SESSION');

// =========================================
// CONFIGURAZIONE EMAIL (TODO)
// =========================================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-password');
define('FROM_EMAIL', 'noreply@yourweek.com');
define('FROM_NAME', 'YourWeek');

// =========================================
// CONFIGURAZIONE SECURITY
// =========================================

define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minuti

// =========================================
// ERRORI E DEBUG
// =========================================

if (APP_ENV === 'development') {
    // Development: mostra tutti gli errori
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Production: nascondi errori, solo log
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// =========================================
// TIMEZONE
// =========================================

date_default_timezone_set('Europe/Rome');

// =========================================
// FUNZIONI HELPER CONFIGURAZIONE
// =========================================

/**
 * Controlla se siamo in development
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

/**
 * Controlla se siamo in production
 */
function isProduction() {
    return APP_ENV === 'production';
}

/**
 * Ottieni URL base
 */
function getBaseUrl() {
    return BASE_URL;
}

/**
 * Ottieni URL API
 */
function getApiUrl() {
    return API_URL;
}

/**
 * Crea directory se non esiste
 */
function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Assicurati che directory uploads esista
ensureDirectoryExists(UPLOAD_DIR);

// =========================================
// AUTOLOAD (opzionale per classi custom)
// =========================================

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>