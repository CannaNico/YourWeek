<?php
/**
 * add_test_patient.php – SEEDER PAZIENTE DI TEST
 * ---------------------------------------------------------------
 * SECURITY: lo script originale permetteva a chiunque di creare
 * un utente con password nota, semplicemente visitando l'URL.
 *
 * Versione blindata:
 *   - Eseguibile solo in development.
 *   - Eseguibile SOLO da CLI (php add_test_patient.php) oppure
 *     da un nutrizionista loggato.
 *   - In produzione: 404.
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/db_connection.php';

$isCli = (PHP_SAPI === 'cli');

if (!isDevelopment() || (!$isCli && (!isAuthenticated() || !hasRole('nutrizionista')))) {
    http_response_code(404);
    exit('Not Found');
}

$db = getDB();

try {
    $check = $db->prepare('SELECT id FROM Users WHERE email = ?');
    $check->execute(['test.paziente@example.com']);

    if ($check->fetch()) {
        $upd = $db->prepare("UPDATE Users SET nutritionist_id = NULL, provincia = 'BG' WHERE email = ?");
        $upd->execute(['test.paziente@example.com']);
        $message = 'Paziente di test già esistente: aggiornato (rimosso nutrizionista, provincia BG).';
    } else {
        $ins = $db->prepare("
            INSERT INTO Users
                (email, password_hash, first_name, last_name, role, provincia,
                 nutritionist_id, is_active, email_verified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            'test.paziente@example.com',
            hashPassword('password123'),
            'Test',
            'Paziente',
            'paziente',
            'BG',
            null,
            1,
            1,
        ]);
        $message = 'Paziente di test creato (email: test.paziente@example.com / password: password123).';
    }

    if ($isCli) {
        fwrite(STDOUT, $message . PHP_EOL);
        exit(0);
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<p>' . htmlspecialchars($message) . '</p>';

} catch (Throwable $e) {
    error_log('add_test_patient: ' . $e->getMessage());
    if ($isCli) {
        fwrite(STDERR, 'Errore: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    echo '<p>Errore interno.</p>';
}
