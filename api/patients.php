<?php
/**
 * api/patients.php
 * ---------------------------------------------------------------
 * API gestione pazienti.
 *
 * Fix principali:
 *   - DRY: helper requireNutritionist() + assertPatientOwnedBy().
 *   - Nome tabella consistente: SEMPRE 'Users' (era 'users' in
 *     get_nutritionist_code → fail su filesystem case-sensitive).
 *   - Lettura input unificata via input() (no più if/else duplicato).
 *   - try/catch globale, niente info leak.
 *   - Rollback in caso di eccezione gestito anche se beginTransaction
 *     non era stato chiamato.
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db_connection.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($action) {
        case 'get_unassigned':
            requireMethod('GET');
            getUnassignedPatients();
            break;

        case 'get_my_patients':
            requireMethod('GET');
            getMyPatients();
            break;

        case 'assign_patient':
            requireMethod('POST');
            assignPatient();
            break;

        case 'update_patient':
            requireMethod('POST');
            updatePatientData();
            break;

        case 'get_patient_details':
            requireMethod('GET');
            getPatientDetails();
            break;

        case 'add_progress':
            requireMethod('POST');
            addPatientProgress();
            break;

        case 'remove_patient':
            requireMethod('POST');
            removePatient();
            break;

        case 'get_nutritionist_code':
            requireMethod('GET');
            getNutritionistCode();
            break;

        default:
            sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
    }
} catch (Throwable $e) {
    error_log('patients.php uncaught: ' . $e->getMessage());
    // Se siamo in mezzo a una transazione, rollback
    try { if (Database::getInstance()->inTransaction()) rollback(); } catch (Throwable $ignore) {}
    sendJSON(['success' => false, 'error' => 'Errore interno del server'], 500);
}

// =========================================
// HELPERS LOCALI
// =========================================

function requireMethod(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        sendJSON(['success' => false, 'error' => 'Metodo non consentito'], 405);
    }
}

function requireNutritionist(): int
{
    requireRole('nutrizionista');
    return (int) $_SESSION['user_id'];
}

/**
 * Verifica che il paziente esista e sia assegnato al nutrizionista corrente.
 * Termina la richiesta con 404 se non lo è.
 */
function assertPatientOwnedBy(PDO $db, int $patientId, int $nutritionistId): void
{
    $stmt = $db->prepare("
        SELECT 1 FROM Users
        WHERE id = ? AND nutritionist_id = ? AND role = 'paziente'
        LIMIT 1
    ");
    $stmt->execute([$patientId, $nutritionistId]);
    if (!$stmt->fetchColumn()) {
        sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
    }
}

/** Float positivo o null se ≤ 0 / non numerico */
function positiveFloatOrNull($value): ?float
{
    $v = (float) $value;
    return $v > 0 ? $v : null;
}

// =========================================
// HANDLERS
// =========================================

function getUnassignedPatients(): void
{
    requireNutritionist();
    $db = getDB();

    $stmt = $db->prepare("
        SELECT id, email, first_name, last_name, birth_date, registration_date
        FROM Users
        WHERE role = 'paziente'
          AND is_active = 1
          AND (nutritionist_id IS NULL OR nutritionist_id = 0)
        ORDER BY registration_date DESC
    ");
    $stmt->execute();
    sendJSON(['success' => true, 'patients' => $stmt->fetchAll()]);
}

function getMyPatients(): void
{
    $nutritionistId = requireNutritionist();
    $db = getDB();

    // Subquery ottimizzata: usa una window-function equivalent.
    // Manteniamo MAX() per compatibilità MySQL 5.7+.
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.first_name, u.last_name, u.birth_date,
               u.height_cm, u.initial_weight, u.initial_body_fat, u.initial_muscle_mass,
               u.registration_date,
               pp.weight_kg            AS current_weight,
               pp.body_fat_percent     AS current_body_fat,
               pp.muscle_mass_percent  AS current_muscle_mass,
               pp.bmi                  AS current_bmi,
               pp.measurement_date     AS last_measurement_date,
               (SELECT MIN(appointment_date)
                FROM Appointments
                WHERE patient_id = u.id
                  AND nutritionist_id = :nid
                  AND status = 'scheduled'
                  AND appointment_date >= CURDATE()
               ) AS next_appointment
        FROM Users u
        LEFT JOIN (
            SELECT pp1.patient_id, pp1.weight_kg, pp1.body_fat_percent,
                   pp1.muscle_mass_percent, pp1.bmi, pp1.measurement_date
            FROM PatientProgress pp1
            INNER JOIN (
                SELECT patient_id, MAX(measurement_date) AS md
                FROM PatientProgress
                GROUP BY patient_id
            ) latest ON latest.patient_id = pp1.patient_id
                    AND latest.md = pp1.measurement_date
        ) pp ON pp.patient_id = u.id
        WHERE u.role = 'paziente'
          AND u.is_active = 1
          AND u.nutritionist_id = :nid
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([':nid' => $nutritionistId]);

    sendJSON(['success' => true, 'patients' => $stmt->fetchAll()]);
}

function assignPatient(): void
{
    $nutritionistId = requireNutritionist();
    $db = getDB();

    $patientId  = (int)   input('patient_id', 0);
    $height     = positiveFloatOrNull(input('height_cm', 0));
    $weight     = positiveFloatOrNull(input('initial_weight', 0));
    $bodyFat    = positiveFloatOrNull(input('initial_body_fat', 0));
    $muscleMass = positiveFloatOrNull(input('initial_muscle_mass', 0));
    $goal       = cleanInput((string) input('goal', '')) ?? '';

    if ($patientId <= 0) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }

    beginTransaction();

    $stmt = $db->prepare("
        SELECT nutritionist_id FROM Users
        WHERE id = ? AND role = 'paziente' LIMIT 1
    ");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();

    if (!$patient) {
        rollback();
        sendJSON(['success' => false, 'error' => 'Paziente non trovato'], 404);
    }
    if (!empty($patient['nutritionist_id'])) {
        rollback();
        sendJSON(['success' => false, 'error' => 'Paziente già assegnato a un nutrizionista'], 409);
    }

    $upd = $db->prepare("
        UPDATE Users
        SET nutritionist_id = ?, height_cm = ?, initial_weight = ?,
            initial_body_fat = ?, initial_muscle_mass = ?
        WHERE id = ?
    ");
    $upd->execute([$nutritionistId, $height, $weight, $bodyFat, $muscleMass, $patientId]);

    if ($weight !== null) {
        $ins = $db->prepare("
            INSERT INTO PatientProgress
                (patient_id, measurement_date, weight_kg, body_fat_percent, muscle_mass_percent, notes)
            VALUES (?, CURDATE(), ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                weight_kg = VALUES(weight_kg),
                body_fat_percent = VALUES(body_fat_percent),
                muscle_mass_percent = VALUES(muscle_mass_percent),
                notes = VALUES(notes)
        ");
        $ins->execute([
            $patientId, $weight, $bodyFat, $muscleMass,
            $goal !== '' ? 'Obiettivo: ' . $goal : 'Prima misurazione',
        ]);
    }

    logActivity($db, $nutritionistId, 'assign_patient', "Paziente ID $patientId assegnato");
    commit();

    sendJSON(['success' => true, 'message' => 'Paziente assegnato con successo']);
}

function getPatientDetails(): void
{
    $nutritionistId = requireNutritionist();
    $patientId      = (int) ($_GET['patient_id'] ?? 0);

    if ($patientId <= 0) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }

    $db = getDB();
    assertPatientOwnedBy($db, $patientId, $nutritionistId);

    $stmt = $db->prepare("
        SELECT id, email, first_name, last_name, birth_date,
               height_cm, initial_weight, initial_body_fat, initial_muscle_mass,
               registration_date
        FROM Users WHERE id = ? AND role = 'paziente'
    ");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();

    $progressStmt = $db->prepare("
        SELECT measurement_date, weight_kg, body_fat_percent, muscle_mass_percent, bmi, notes
        FROM PatientProgress
        WHERE patient_id = ?
        ORDER BY measurement_date DESC
    ");
    $progressStmt->execute([$patientId]);

    $apptStmt = $db->prepare("
        SELECT appointment_date, start_time, appointment_type, status, notes
        FROM Appointments
        WHERE patient_id = ? AND nutritionist_id = ?
          AND appointment_date >= CURDATE()
        ORDER BY appointment_date ASC, start_time ASC
        LIMIT 5
    ");
    $apptStmt->execute([$patientId, $nutritionistId]);

    sendJSON([
        'success'      => true,
        'patient'      => $patient,
        'progress'     => $progressStmt->fetchAll(),
        'appointments' => $apptStmt->fetchAll(),
    ]);
}

function updatePatientData(): void
{
    $nutritionistId = requireNutritionist();
    $db = getDB();

    $patientId = (int) input('patient_id', 0);
    $height    = positiveFloatOrNull(input('height_cm', 0));

    if ($patientId <= 0) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    assertPatientOwnedBy($db, $patientId, $nutritionistId);

    if ($height !== null) {
        $stmt = $db->prepare('UPDATE Users SET height_cm = ? WHERE id = ?');
        $stmt->execute([$height, $patientId]);
    }

    sendJSON(['success' => true, 'message' => 'Dati aggiornati con successo']);
}

function addPatientProgress(): void
{
    $nutritionistId = requireNutritionist();
    $db = getDB();

    $patientId       = (int) input('patient_id', 0);
    $weight          = positiveFloatOrNull(input('weight_kg', 0));
    $bodyFat         = positiveFloatOrNull(input('body_fat_percent', 0));
    $muscleMass      = positiveFloatOrNull(input('muscle_mass_percent', 0));
    $notes           = cleanInput((string) input('notes', '')) ?? '';
    $measurementDate = (string) input('measurement_date', date('Y-m-d'));

    if ($patientId <= 0 || $weight === null) {
        sendJSON(['success' => false, 'error' => 'ID paziente e peso richiesti'], 400);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $measurementDate)) {
        sendJSON(['success' => false, 'error' => 'Data misurazione non valida'], 400);
    }

    assertPatientOwnedBy($db, $patientId, $nutritionistId);

    $stmt = $db->prepare("
        INSERT INTO PatientProgress
            (patient_id, measurement_date, weight_kg, body_fat_percent, muscle_mass_percent, notes)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            weight_kg = VALUES(weight_kg),
            body_fat_percent = VALUES(body_fat_percent),
            muscle_mass_percent = VALUES(muscle_mass_percent),
            notes = VALUES(notes)
    ");
    $stmt->execute([$patientId, $measurementDate, $weight, $bodyFat, $muscleMass, $notes]);

    sendJSON(['success' => true, 'message' => 'Progresso registrato con successo']);
}

function removePatient(): void
{
    $nutritionistId = requireNutritionist();
    $db = getDB();

    $patientId = (int) input('patient_id', 0);
    if ($patientId <= 0) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    assertPatientOwnedBy($db, $patientId, $nutritionistId);

    $stmt = $db->prepare('UPDATE Users SET nutritionist_id = NULL WHERE id = ?');
    $stmt->execute([$patientId]);

    logActivity($db, $nutritionistId, 'remove_patient', "Paziente ID $patientId rimosso");
    sendJSON(['success' => true, 'message' => 'Paziente rimosso con successo']);
}

function getNutritionistCode(): void
{
    $nutritionistId = requireNutritionist();
    $db = getDB();

    $stmt = $db->prepare("
        SELECT nutritionist_code FROM Users WHERE id = ? AND role = 'nutrizionista'
    ");
    $stmt->execute([$nutritionistId]);
    $row = $stmt->fetch();

    if (!$row || empty($row['nutritionist_code'])) {
        sendJSON(['success' => false, 'error' => 'Codice non trovato'], 404);
    }
    sendJSON(['success' => true, 'code' => $row['nutritionist_code']]);
}
