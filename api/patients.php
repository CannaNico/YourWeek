<?php
// api/patients.php
// API per la gestione dei pazienti
require_once __DIR__ . '/../includes/db_connection.php';

// Impedisci accesso diretto al file senza azione
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_unassigned':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getUnassignedPatients();
        }
        break;

    case 'get_my_patients':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getMyPatients();
        }
        break;

    case 'assign_patient':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            assignPatient();
        }
        break;

    case 'update_patient':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            updatePatientData();
        }
        break;

    case 'get_patient_details':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getPatientDetails();
        }
        break;

    case 'add_progress':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            addPatientProgress();
        }
        break;

    case 'remove_patient':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            removePatient();
        }
        break;

    case 'get_nutritionist_code':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getNutritionistCode();
        }
        break;

    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

// === FUNZIONI API ===

/**
 * Ottiene la lista dei pazienti non ancora assegnati a nessun nutrizionista
 */
function getUnassignedPatients()
{
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();

    try {
        $stmt = $db->prepare("
            SELECT 
                id,
                email,
                first_name,
                last_name,
                birth_date,
                registration_date
            FROM Users
            WHERE role = 'paziente' 
                AND is_active = 1
                AND (nutritionist_id IS NULL OR nutritionist_id = 0)
            ORDER BY registration_date DESC
        ");
        $stmt->execute();
        $patients = $stmt->fetchAll();

        sendJSON([
            'success' => true,
            'patients' => $patients
        ]);

    } catch (PDOException $e) {
        error_log("Errore get_unassigned_patients: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero dei pazienti'], 500);
    }
}

/**
 * Ottiene la lista dei pazienti assegnati al nutrizionista loggato
 */
function getMyPatients()
{
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];

    try {
        $stmt = $db->prepare("
            SELECT 
                u.id,
                u.email,
                u.first_name,
                u.last_name,
                u.birth_date,
                u.height_cm,
                u.initial_weight,
                u.initial_body_fat,
                u.initial_muscle_mass,
                u.registration_date,
                pp.weight_kg as current_weight,
                pp.body_fat_percent as current_body_fat,
                pp.muscle_mass_percent as current_muscle_mass,
                pp.bmi as current_bmi,
                pp.measurement_date as last_measurement_date
            FROM Users u
            LEFT JOIN (
                SELECT 
                    patient_id,
                    weight_kg,
                    body_fat_percent,
                    muscle_mass_percent,
                    bmi,
                    measurement_date
                FROM PatientProgress pp1
                WHERE measurement_date = (
                    SELECT MAX(measurement_date)
                    FROM PatientProgress pp2
                    WHERE pp2.patient_id = pp1.patient_id
                )
            ) pp ON u.id = pp.patient_id
            WHERE u.role = 'paziente' 
                AND u.is_active = 1
                AND u.nutritionist_id = ?
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute([$nutritionistId]);
        $patients = $stmt->fetchAll();

        // Aggiungi il prossimo appuntamento per ogni paziente
        foreach ($patients as &$patient) {
            $stmtApp = $db->prepare("
                SELECT appointment_date 
                FROM Appointments 
                WHERE patient_id = ? 
                    AND nutritionist_id = ?
                    AND status = 'scheduled'
                    AND appointment_date >= CURDATE()
                ORDER BY appointment_date ASC
                LIMIT 1
            ");
            $stmtApp->execute([$patient['id'], $nutritionistId]);
            $appointment = $stmtApp->fetch();
            $patient['next_appointment'] = $appointment ? $appointment['appointment_date'] : null;
        }
        unset($patient);

        sendJSON([
            'success' => true,
            'patients' => $patients
        ]);

    } catch (PDOException $e) {
        error_log("Errore get_my_patients: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero dei pazienti'], 500);
    }
}

/**
 * Assegna un paziente al nutrizionista e imposta i dati iniziali
 */
function assignPatient()
{
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];

    // Supporta sia POST tradizionale che JSON
    if (isset($_POST['patient_id'])) {
        $patientId = intval($_POST['patient_id']);
        $height = floatval($_POST['height_cm'] ?? 0);
        $weight = floatval($_POST['initial_weight'] ?? 0);
        $bodyFat = floatval($_POST['initial_body_fat'] ?? 0);
        $muscleMass = floatval($_POST['initial_muscle_mass'] ?? 0);
        $goal = sanitizeInput($_POST['goal'] ?? '');
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $patientId = intval($data['patient_id'] ?? 0);
        $height = floatval($data['height_cm'] ?? 0);
        $weight = floatval($data['initial_weight'] ?? 0);
        $bodyFat = floatval($data['initial_body_fat'] ?? 0);
        $muscleMass = floatval($data['initial_muscle_mass'] ?? 0);
        $goal = sanitizeInput($data['goal'] ?? '');
    }

    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }

    try {
        $db->beginTransaction();

        // Verifica che il paziente non sia già assegnato
        $stmt = $db->prepare("
            SELECT nutritionist_id 
            FROM Users 
            WHERE id = ? AND role = 'paziente'
        ");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();

        if (!$patient) {
            $db->rollBack();
            sendJSON(['success' => false, 'error' => 'Paziente non trovato'], 404);
        }

        if ($patient['nutritionist_id'] && $patient['nutritionist_id'] != 0) {
            $db->rollBack();
            sendJSON(['success' => false, 'error' => 'Paziente già assegnato a un nutrizionista'], 409);
        }

        // Assegna il paziente al nutrizionista e aggiorna i dati iniziali
        $stmt = $db->prepare("
            UPDATE Users 
            SET nutritionist_id = ?,
                height_cm = ?,
                initial_weight = ?,
                initial_body_fat = ?,
                initial_muscle_mass = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $nutritionistId,
            $height > 0 ? $height : null,
            $weight > 0 ? $weight : null,
            $bodyFat > 0 ? $bodyFat : null,
            $muscleMass > 0 ? $muscleMass : null,
            $patientId
        ]);

        // Aggiungi il primo record di progresso se abbiamo i dati
        if ($weight > 0) {
            $stmt = $db->prepare("
                INSERT INTO PatientProgress 
                (patient_id, measurement_date, weight_kg, body_fat_percent, muscle_mass_percent, notes)
                VALUES (?, CURDATE(), ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    weight_kg = VALUES(weight_kg),
                    body_fat_percent = VALUES(body_fat_percent),
                    muscle_mass_percent = VALUES(muscle_mass_percent),
                    notes = VALUES(notes)
            ");
            $stmt->execute([
                $patientId,
                $weight,
                $bodyFat > 0 ? $bodyFat : null,
                $muscleMass > 0 ? $muscleMass : null,
                $goal ? "Obiettivo: " . $goal : "Prima misurazione"
            ]);
        }

        // Log attività
        logActivity($db, $nutritionistId, 'assign_patient', "Paziente ID $patientId assegnato");

        $db->commit();

        sendJSON([
            'success' => true,
            'message' => 'Paziente assegnato con successo'
        ]);

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Errore assign_patient: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante l\'assegnazione del paziente'], 500);
    }
}

/**
 * Ottiene i dettagli completi di un paziente
 */
function getPatientDetails()
{
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $patientId = intval($_GET['patient_id'] ?? 0);
    $nutritionistId = $_SESSION['user_id'];

    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }

    $db = getDB();

    try {
        // Ottieni dati paziente
        $stmt = $db->prepare("
            SELECT 
                u.id,
                u.email,
                u.first_name,
                u.last_name,
                u.birth_date,
                u.height_cm,
                u.initial_weight,
                u.initial_body_fat,
                u.initial_muscle_mass,
                u.registration_date
            FROM Users u
            WHERE u.id = ? 
                AND u.role = 'paziente'
                AND u.nutritionist_id = ?
        ");
        $stmt->execute([$patientId, $nutritionistId]);
        $patient = $stmt->fetch();

        if (!$patient) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        // Ottieni storico progressi
        $stmt = $db->prepare("
            SELECT 
                measurement_date,
                weight_kg,
                body_fat_percent,
                muscle_mass_percent,
                bmi,
                notes
            FROM PatientProgress
            WHERE patient_id = ?
            ORDER BY measurement_date DESC
        ");
        $stmt->execute([$patientId]);
        $progress = $stmt->fetchAll();

        // Ottieni prossimi appuntamenti
        $stmt = $db->prepare("
            SELECT 
                appointment_date,
                start_time,
                appointment_type,
                status,
                notes
            FROM Appointments
            WHERE patient_id = ?
                AND nutritionist_id = ?
                AND appointment_date >= CURDATE()
            ORDER BY appointment_date ASC, start_time ASC
            LIMIT 5
        ");
        $stmt->execute([$patientId, $nutritionistId]);
        $appointments = $stmt->fetchAll();

        sendJSON([
            'success' => true,
            'patient' => $patient,
            'progress' => $progress,
            'appointments' => $appointments
        ]);

    } catch (PDOException $e) {
        error_log("Errore get_patient_details: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero dei dettagli'], 500);
    }
}

/**
 * Aggiorna i dati di un paziente
 */
function updatePatientData()
{
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];

    // Supporta sia POST tradizionale che JSON
    if (isset($_POST['patient_id'])) {
        $patientId = intval($_POST['patient_id']);
        $height = floatval($_POST['height_cm'] ?? 0);
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $patientId = intval($data['patient_id'] ?? 0);
        $height = floatval($data['height_cm'] ?? 0);
    }

    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }

    try {
        // Verifica che il paziente appartenga al nutrizionista
        $stmt = $db->prepare("
            SELECT id FROM Users 
            WHERE id = ? AND nutritionist_id = ? AND role = 'paziente'
        ");
        $stmt->execute([$patientId, $nutritionistId]);

        if (!$stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        // Aggiorna altezza
        if ($height > 0) {
            $stmt = $db->prepare("UPDATE Users SET height_cm = ? WHERE id = ?");
            $stmt->execute([$height, $patientId]);
        }

        sendJSON([
            'success' => true,
            'message' => 'Dati aggiornati con successo'
        ]);

    } catch (PDOException $e) {
        error_log("Errore update_patient_data: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante l\'aggiornamento'], 500);
    }
}

/**
 * Aggiunge un nuovo record di progresso per un paziente
 */
function addPatientProgress()
{
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];

    // Supporta sia POST tradizionale che JSON
    if (isset($_POST['patient_id'])) {
        $patientId = intval($_POST['patient_id']);
        $weight = floatval($_POST['weight_kg'] ?? 0);
        $bodyFat = floatval($_POST['body_fat_percent'] ?? 0);
        $muscleMass = floatval($_POST['muscle_mass_percent'] ?? 0);
        $notes = sanitizeInput($_POST['notes'] ?? '');
        $measurementDate = $_POST['measurement_date'] ?? date('Y-m-d');
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $patientId = intval($data['patient_id'] ?? 0);
        $weight = floatval($data['weight_kg'] ?? 0);
        $bodyFat = floatval($data['body_fat_percent'] ?? 0);
        $muscleMass = floatval($data['muscle_mass_percent'] ?? 0);
        $notes = sanitizeInput($data['notes'] ?? '');
        $measurementDate = $data['measurement_date'] ?? date('Y-m-d');
    }

    if (!$patientId || $weight <= 0) {
        sendJSON(['success' => false, 'error' => 'ID paziente e peso richiesti'], 400);
    }

    try {
        // Verifica che il paziente appartenga al nutrizionista
        $stmt = $db->prepare("
            SELECT id FROM Users 
            WHERE id = ? AND nutritionist_id = ? AND role = 'paziente'
        ");
        $stmt->execute([$patientId, $nutritionistId]);

        if (!$stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        // Inserisci nuovo progresso (o aggiorna se esiste già per quella data)
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
        $stmt->execute([
            $patientId,
            $measurementDate,
            $weight,
            $bodyFat > 0 ? $bodyFat : null,
            $muscleMass > 0 ? $muscleMass : null,
            $notes
        ]);

        sendJSON([
            'success' => true,
            'message' => 'Progresso registrato con successo'
        ]);

    } catch (PDOException $e) {
        error_log("Errore add_patient_progress: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante la registrazione del progresso'], 500);
    }
}

/**
 * Rimuove l'assegnazione di un paziente dal nutrizionista
 */
function removePatient()
{
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];

    // Supporta sia POST tradizionale che JSON
    if (isset($_POST['patient_id'])) {
        $patientId = intval($_POST['patient_id']);
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $patientId = intval($data['patient_id'] ?? 0);
    }

    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }

    try {
        // Verifica che il paziente appartenga al nutrizionista
        $stmt = $db->prepare("
            SELECT id FROM Users 
            WHERE id = ? AND nutritionist_id = ? AND role = 'paziente'
        ");
        $stmt->execute([$patientId, $nutritionistId]);

        if (!$stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        // Rimuovi l'assegnazione (non elimina l'utente, solo il collegamento)
        $stmt = $db->prepare("
            UPDATE Users 
            SET nutritionist_id = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$patientId]);

        // Log attività
        logActivity($db, $nutritionistId, 'remove_patient', "Paziente ID $patientId rimosso");

        sendJSON([
            'success' => true,
            'message' => 'Paziente rimosso con successo'
        ]);

    } catch (PDOException $e) {
        error_log("Errore remove_patient: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante la rimozione del paziente'], 500);
    }
}

/**
 * Ottiene il codice univoco del nutrizionista loggato
 */
function getNutritionistCode()
{
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];

    try {
        $stmt = $db->prepare("
            SELECT nutritionist_code 
            FROM users 
            WHERE id = ? AND role = 'nutrizionista'
        ");
        $stmt->execute([$nutritionistId]);
        $result = $stmt->fetch();

        if (!$result || empty($result['nutritionist_code'])) {
            sendJSON(['success' => false, 'error' => 'Codice non trovato'], 404);
        }

        sendJSON([
            'success' => true,
            'code' => $result['nutritionist_code']
        ]);

    } catch (PDOException $e) {
        error_log("Errore get_nutritionist_code: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero del codice'], 500);
    }
}
?>