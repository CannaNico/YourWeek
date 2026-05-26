<?php
/**
 * debug_users.php – DIAGNOSTICA UTENTI
 * ---------------------------------------------------------------
 * SECURITY: lo script originale esponeva email, nomi, ruoli e
 * provincia di TUTTI gli utenti a chiunque sapesse l'URL.
 *
 * Versione blindata:
 *   - Solo in APP_ENV=development.
 *   - Richiede login.
 *   - Tutte le stringhe stampate sono passate da htmlspecialchars
 *     per prevenire XSS riflesso (un email malevola con tag HTML
 *     veniva renderizzata).
 *   - In produzione restituisce 404.
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
$db = getDB();

try {
    $users      = $db->query("SELECT id, email, first_name, last_name, role, provincia, nutritionist_id FROM Users ORDER BY role, id")->fetchAll();
    $byProvRole = $db->query("SELECT provincia, role, COUNT(*) AS cnt FROM Users GROUP BY provincia, role")->fetchAll();
    $unassigned = $db->query("SELECT id, email, first_name, last_name, provincia FROM Users WHERE role = 'paziente' AND (nutritionist_id IS NULL OR nutritionist_id = 0)")->fetchAll();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<p>Errore: ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}
?>
<!doctype html>
<html lang="it">
<head><meta charset="utf-8"><title>YourWeek – Debug Users</title>
<style>body{font-family:system-ui,sans-serif;padding:20px}table{border-collapse:collapse}th,td{padding:6px 10px;border:1px solid #ccc}th{background:#f3f4f6;text-align:left}</style>
</head>
<body>
    <h1>Debug Users</h1>

    <h2>Utenti (<?= count($users) ?>)</h2>
    <table>
        <thead><tr><th>ID</th><th>Email</th><th>Nome</th><th>Ruolo</th><th>Prov.</th><th>Nutr. ID</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['provincia'] ?? 'NULL') ?></td>
                <td><?= htmlspecialchars((string) ($u['nutritionist_id'] ?? 'NULL')) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <h2>Conteggio per provincia/ruolo</h2>
    <table>
        <thead><tr><th>Prov.</th><th>Ruolo</th><th>N</th></tr></thead>
        <tbody>
        <?php foreach ($byProvRole as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['provincia'] ?? 'NULL') ?></td>
                <td><?= htmlspecialchars($r['role']) ?></td>
                <td><?= (int) $r['cnt'] ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <h2>Pazienti senza nutrizionista (<?= count($unassigned) ?>)</h2>
    <?php if (!$unassigned): ?>
        <p><em>Nessun paziente senza nutrizionista.</em></p>
    <?php else: ?>
        <table>
            <thead><tr><th>ID</th><th>Email</th><th>Nome</th><th>Prov.</th></tr></thead>
            <tbody>
            <?php foreach ($unassigned as $p): ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                    <td><?= htmlspecialchars($p['provincia'] ?? 'NULL') ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</body>
</html>
