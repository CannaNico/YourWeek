<?php
/**
 * test_connection.php – TEST CONNESSIONE DB
 * ---------------------------------------------------------------
 * SECURITY: lo script originale stampava credenziali di esempio,
 * lista delle tabelle, versione MySQL e path uploads pubblicamente.
 *
 * Versione blindata:
 *   - Solo in development e con login attivo.
 *   - Niente più output di credenziali di prova ("dott.rossi@…").
 *   - In produzione: 404.
 * ---------------------------------------------------------------
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
<html lang="it"><head><meta charset="utf-8"><title>YourWeek – Test Connessione</title>
<style>
    body{font-family:system-ui,sans-serif;background:#f5f6fa;padding:24px}
    .card{background:#fff;border-radius:12px;padding:20px;margin-bottom:16px;box-shadow:0 4px 12px rgba(0,0,0,.06)}
    .ok{border-left:4px solid #16a34a}
    .ko{border-left:4px solid #dc2626}
    .warn{border-left:4px solid #f59e0b}
    table{border-collapse:collapse;width:100%;margin-top:10px}
    th,td{padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:left}
    th{background:#f3f4f6}
    code{background:#f3f4f6;padding:2px 6px;border-radius:4px}
</style></head><body>

<h1>Test Connessione Database</h1>

<div class="card ok">
    <h3>Configurazione</h3>
    <div>Ambiente: <code><?= htmlspecialchars(APP_ENV) ?></code></div>
    <div>Host: <code><?= htmlspecialchars(DB_HOST) ?>:<?= htmlspecialchars(DB_PORT) ?></code></div>
    <div>Database: <code><?= htmlspecialchars(DB_NAME) ?></code></div>
</div>

<?php
try {
    $db = getDB();

    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $userCount = (int) $db->query('SELECT COUNT(*) FROM Users')->fetchColumn();
    $version = $db->query('SELECT VERSION()')->fetchColumn();
    $uploadsWritable = is_writable(UPLOAD_DIR);
    ?>
    <div class="card ok">
        <h3>Connessione DB</h3>
        <div>Connessione PDO OK. Versione MySQL: <code><?= htmlspecialchars((string) $version) ?></code></div>
    </div>

    <div class="card ok">
        <h3>Tabelle (<?= count($tables) ?>)</h3>
        <table><thead><tr><th>Nome</th></tr></thead><tbody>
        <?php foreach ($tables as $t): ?>
            <tr><td><?= htmlspecialchars((string) $t) ?></td></tr>
        <?php endforeach ?>
        </tbody></table>
    </div>

    <div class="card <?= $userCount > 0 ? 'ok' : 'warn' ?>">
        <h3>Utenti</h3>
        Totale: <strong><?= $userCount ?></strong>
    </div>

    <div class="card <?= $uploadsWritable ? 'ok' : 'ko' ?>">
        <h3>Directory uploads</h3>
        <code><?= htmlspecialchars(UPLOAD_DIR) ?></code> –
        <?= $uploadsWritable ? 'scrivibile' : '<strong>NON scrivibile</strong>' ?>
    </div>
    <?php
} catch (Throwable $e) {
    ?>
    <div class="card ko">
        <h3>Errore connessione</h3>
        <pre><?= htmlspecialchars($e->getMessage()) ?></pre>
    </div>
    <?php
}
?>
</body></html>
