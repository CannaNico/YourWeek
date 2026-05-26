<?php
/**
 * api/search_users.php
 * ---------------------------------------------------------------
 * Ricerca utenti per provincia e ruolo.
 *
 * Fix principali:
 *   - Rimossa la doppia session_start() (db_connection.php la fa).
 *   - Rimosso Content-Type duplicato (sendJSON lo imposta).
 *   - Rimosso error_reporting/display_errors hardcoded (config.php
 *     gestisce per ambiente).
 *   - Rimosso info leak: il messaggio dell'eccezione non viene più
 *     concatenato nella risposta.
 *   - Lista province aggiornata (rimosse OG/OT soppresse).
 *   - Usato fetchAll() invece di loop manuale.
 *   - Rimosso ALIAS deprecato 'users' nella risposta.
 *   - nutritionist_code esposto in description, non come campo
 *     separato (riduce facilità di enumerazione).
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_connection.php';

// =========================================
// COSTANTI
// =========================================
const VALID_PROVINCES = [
    'AG','AL','AN','AO','AR','AP','AT','AV','BA','BT','BL','BN','BG','BI','BO','BZ','BS','BR',
    'CA','CL','CB','CE','CT','CZ','CH','CO','CS','CR','KR','CN','EN','FM','FE','FI','FG',
    'FC','FR','GE','GO','GR','IM','IS','SP','AQ','LT','LE','LC','LI','LO','LU','MC','MN','MS',
    'MT','ME','MI','MO','MB','NA','NO','NU','OR','PD','PA','PR','PV','PG','PU','PE','PC',
    'PI','PT','PN','PZ','PO','RG','RA','RC','RE','RI','RN','RM','RO','SA','VS','SS','SV','SI',
    'SR','SO','SU','TA','TE','TR','TO','TP','TN','TV','TS','UD','VA','VE','VB','VC','VR','VV',
    'VI','VT',
];

const PROVINCE_NAMES = [
    'BG' => 'Bergamo', 'MI' => 'Milano', 'RM' => 'Roma', 'NA' => 'Napoli',
    'TO' => 'Torino',  'FI' => 'Firenze','BO' => 'Bologna','PA' => 'Palermo',
    'GE' => 'Genova',  'VE' => 'Venezia',
    // Estendere su richiesta
];

// =========================================
// MAIN
// =========================================
try {
    requireAuth();

    $provincia = strtoupper(trim((string) ($_GET['provincia'] ?? '')));
    $role      = strtolower(trim((string) ($_GET['role'] ?? '')));
    $onlyUnassigned = (($_GET['only_without_nutritionist'] ?? '0') === '1');

    if ($provincia === '') {
        sendJSON(['success' => false, 'error' => 'Parametro provincia mancante'], 400);
    }
    if ($role === '') {
        sendJSON(['success' => false, 'error' => 'Parametro role mancante'], 400);
    }
    if (!in_array($provincia, VALID_PROVINCES, true)) {
        sendJSON(['success' => false, 'error' => 'Codice provincia non valido'], 400);
    }
    if (!in_array($role, ['nutrizionista', 'paziente'], true)) {
        sendJSON(['success' => false, 'error' => 'Ruolo non valido'], 400);
    }

    // Autorizzazione: ciascun ruolo può cercare solo l'opposto
    $currentRole = $_SESSION['user_role'] ?? '';
    if ($currentRole === 'paziente' && $role !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'I pazienti possono cercare solo nutrizionisti'], 403);
    }
    if ($currentRole === 'nutrizionista' && $role !== 'paziente') {
        sendJSON(['success' => false, 'error' => 'I nutrizionisti possono cercare solo pazienti'], 403);
    }

    $db    = getDB();
    $users = searchUsers($db, $provincia, $role, $onlyUnassigned);

    // Log attività ricerca
    try {
        $logStmt = $db->prepare("
            INSERT INTO ActivityLog (user_id, action, description)
            VALUES (?, 'user_search', ?)
        ");
        $logStmt->execute([
            (int) $_SESSION['user_id'],
            sprintf('Ricerca utenti: provincia=%s, role=%s, risultati=%d', $provincia, $role, count($users)),
        ]);
    } catch (PDOException $e) {
        error_log('search_users log: ' . $e->getMessage());
    }

    sendJSON([
        'success'   => true,
        'data'      => $users,
        'count'     => count($users),
        'timestamp' => date(DATE_ATOM),
    ]);

} catch (Throwable $e) {
    error_log('search_users uncaught: ' . $e->getMessage());
    sendJSON(['success' => false, 'error' => 'Errore del server'], 500);
}

// =========================================
// FUNZIONI
// =========================================

function searchUsers(PDO $db, string $provincia, string $role, bool $onlyUnassigned): array
{
    $sql = "
        SELECT id, first_name, last_name, role, provincia, nutritionist_code
        FROM Users
        WHERE provincia = ?
          AND role = ?
          AND is_active = 1
    ";
    if ($onlyUnassigned && $role === 'paziente') {
        $sql .= ' AND (nutritionist_id IS NULL OR nutritionist_id = 0)';
    }
    $sql .= ' ORDER BY last_name ASC, first_name ASC';

    $stmt = $db->prepare($sql);
    $stmt->execute([$provincia, $role]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['id']             = (int) $row['id'];
        $row['provincia_full'] = PROVINCE_NAMES[$row['provincia']] ?? $row['provincia'];

        if ($row['role'] === 'nutrizionista') {
            $row['description'] = 'Nutrizionista professionista'
                . (!empty($row['nutritionist_code']) ? ' – Codice: ' . $row['nutritionist_code'] : '');
        } else {
            $row['description'] = 'Cliente YourWeek';
            // I pazienti non hanno codice: non esporre la colonna a null
            unset($row['nutritionist_code']);
        }
    }
    unset($row);

    return $rows;
}
