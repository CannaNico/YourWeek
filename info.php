<?php
/**
 * info.php – DIAGNOSTICA SISTEMA
 * ---------------------------------------------------------------
 * SECURITY: phpinfo() rivela versioni, percorsi, estensioni e
 * variabili d'ambiente — è uno dei primi target degli scanner.
 *
 * Questo file è disponibile SOLO in development e SOLO se chi
 * accede ha effettuato il login.  In production, restituisce 404
 * per non leakare la sua esistenza.
 * ---------------------------------------------------------------
 *
 * Bug-fix: rimossi gli escape Windows-CMD (`^<h1^>`) che venivano
 * stampati letteralmente dal browser.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connection.php';

if (!isDevelopment()) {
    http_response_code(404);
    exit('Not Found');
}
requireAuth();

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="it">
<head><meta charset="utf-8"><title>YourWeek – Diagnostica</title></head>
<body>
    <h1>✅ PHP Funziona Correttamente</h1>
    <p><strong>Versione PHP:</strong> <?= htmlspecialchars(PHP_VERSION) ?></p>
    <p><strong>Ambiente:</strong> <?= htmlspecialchars(APP_ENV) ?></p>
    <hr>
    <?php phpinfo(); ?>
</body>
</html>
