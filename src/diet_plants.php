<?php
// api/patients.php
// API per gestione pazienti

require_once '../db_connection.php';

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    sendJSON(['success' => false, 'error' => 'Non autenticato'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        if ($method === 'GET') {
            getPatients();
        }
        break;
    
    case 'get':
        if ($method === 'GET') {
            getPatient();
        }
        break;
    
    case 'create':
        if ($method === 'POST') {
            createPatient();
        }
        break;
    
    case 'update':
        if ($method === 'PUT' || $method === 'POST') {
            updatePatient();
        }
        break;
    
    case 'progress':
        if ($method === 'POST') {
            addProgress();
        } elseif ($method === 'GET') {
            getProgress();
        }
        break;
    
    case 'stats':
        if ($method === 'GET') {
            getStats();
        }
        break;
    
    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

// LISTA PAZIENTI
function getPatients() {
    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];
    
    try {
        $stmt = $db->prepare("
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.birth_date,
                u.registration_date,
                u.initial_weight,
                u.initial_body_fat,
                u.initial_muscle_mass,
                u.height_cm,
                (
                    SELECT p.weight_kg 
                    FROM PatientProgress p 
                    WHERE p.patient_id = u.id 
                    ORDER BY p.measurement_date DESC 
                    LIMIT 1
                ) as current_weight,
                (
                    SELECT p.body_fat_percent 
                    FROM PatientProgress p 
                    WHERE p.patient_id = u.id 
                    ORDER BY p.measurement_date DESC 
                    LIMIT 1
                ) as current_body_fat,
                (
                    SELECT p.muscle_mass_percent 
                    FROM PatientProgress p 
                    WHERE p.patient_id = u.id 
                    ORDER BY p.measurement_date DESC 
                    LIMIT 1
                ) as current_muscle_mass,
                (
                    SELECT a.appointment_date
                    FROM Appointments a
                    WHERE a.patient_id = u.id 
                    AND a.appointment_date >= CURDATE()
                    AND a.status = 'scheduled'
                    ORDER BY a.appointment_date ASC, a.start_time ASC
                    LIMIT 1
                ) as next_visit
            FROM Users u
            WHERE u.nutritionist_id = ?
            AND u.role = 'paziente'
            AND u.is_active = 1
            ORDER BY u.registration_date DESC
        ");
        
        $stmt->execute([$nutritionistId]);
        $patients = $stmt->fetchAll();
        
        // Formatta i dati per il frontend
        $formattedPatients = array_map(function($p) {
            return [
                'id' => $p['id'],
                'name' => $p['first_name'],
                'surname' => $p['last_name'],
                'avatar' => strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)),
                'email' => $p['email'],
                'birthDate' => $p['birth_date'] ? date('d/m/Y', strtotime($p['birth_date'])) : null,
                'registrationDate' => $p['registration_date'],
                'weight' => $p['current_weight'] ?? $p['initial_weight'] . ' kg',
                'height' => $p['height_cm'] . ' cm',
                'bodyFat' => ($p['current_body_fat'] ?? $p['initial_body_fat']) . '%',
                'muscleMass' => ($p['current_muscle_mass'] ?? $p['initial_muscle_mass']) . '%',
                'nextVisit' => $p['next_visit'] ? date('d/m/Y', strtotime($p['next_visit'])) : 'Da programmare',
                'description' => 'Paziente attivo'
            ];
        }, $patients);
        
        sendJSON([
            'success' => true,
            'patients' => $formattedPatients,
            'total' => count($formattedPatients)
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore lista pazienti: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nel recupero dei pazienti'], 500);
    }
}

// DETTAGLIO PAZIENTE
function getPatient() {
    $db = getDB();
    $patientId = $_GET['id'] ?? null;
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    
    try {
        $stmt = $db->prepare("
            SELECT * FROM Users WHERE id = ? AND role = 'paziente'
        ");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        
        if (!$patient) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato'], 404);
        }
        
        sendJSON([
            'success' => true,
            'patient' => $patient
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore dettaglio paziente: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nel recupero del paziente'], 500);
    }
}

// CREA PAZIENTE
function createPatient() {
    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = sanitizeInput($data['email'] ?? '');
    $firstName = sanitizeInput($data['first_name'] ?? '');
    $lastName = sanitizeInput($data['last_name'] ?? '');
    $birthDate = $data['birth_date'] ?? null;
    $height = $data['height_cm'] ?? null;
    $initialWeight = $data['initial_weight'] ?? null;
    $initialBodyFat = $data['initial_body_fat'] ?? null;
    $initialMuscleMass = $data['initial_muscle_mass'] ?? null;
    
    if (empty($email) || empty($firstName) || empty($lastName)) {
        sendJSON(['success' => false, 'error' => 'Campi obbligatori mancanti'], 400);
    }
    
    try {
        // Password temporanea
        $tempPassword = bin2hex(random_bytes(8));
        $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("
            INSERT INTO Users (
                email, password_hash, first_name, last_name, role,
                birth_date, height_cm, initial_weight, initial_body_fat,
                initial_muscle_mass, nutritionist_id, registration_date
            ) VALUES (?, ?, ?, ?, 'paziente', ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $email,
            $passwordHash,
            $firstName,
            $lastName,
            $birthDate,
            $height,
            $initialWeight,
            $initialBodyFat,
            $initialMuscleMass,
            $nutritionistId
        ]);
        
        $patientId = $db->lastInsertId();
        
        // Se ci sono misurazioni iniziali, salvale
        if ($initialWeight || $initialBodyFat || $initialMuscleMass) {
            $stmt = $db->prepare("
                INSERT INTO PatientProgress (
                    patient_id, measurement_date, weight_kg, 
                    body_fat_percent, muscle_mass_percent
                ) VALUES (?, CURDATE(), ?, ?, ?)
            ");
            $stmt->execute([$patientId, $initialWeight, $initialBodyFat, $initialMuscleMass]);
        }
        
        sendJSON([
            'success' => true,
            'message' => 'Paziente creato con successo',
            'patient_id' => $patientId,
            'temp_password' => $tempPassword
        ], 201);
        
    } catch (PDOException $e) {
        error_log("Errore creazione paziente: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nella creazione del paziente'], 500);
    }
}

// AGGIORNA PAZIENTE
function updatePatient() {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $patientId = $data['id'] ?? null;
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    
    try {
        $updates = [];
        $params = [];
        
        if (isset($data['height_cm'])) {
            $updates[] = "height_cm = ?";
            $params[] = $data['height_cm'];
        }
        
        if (!empty($updates)) {
            $params[] = $patientId;
            $sql = "UPDATE Users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        sendJSON([
            'success' => true,
            'message' => 'Paziente aggiornato con successo'
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore aggiornamento paziente: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nell\'aggiornamento del paziente'], 500);
    }
}

// AGGIUNGI PROGRESSO
function addProgress() {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $patientId = $data['patient_id'] ?? null;
    $weight = $data['weight_kg'] ?? null;
    $bodyFat = $data['body_fat_percent'] ?? null;
    $muscleMass = $data['muscle_mass_percent'] ?? null;
    $date = $data['measurement_date'] ?? date('Y-m-d');
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO PatientProgress (
                patient_id, measurement_date, weight_kg,
                body_fat_percent, muscle_mass_percent
            ) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                weight_kg = VALUES(weight_kg),
                body_fat_percent = VALUES(body_fat_percent),
                muscle_mass_percent = VALUES(muscle_mass_percent)
        ");
        
        $stmt->execute([$patientId, $date, $weight, $bodyFat, $muscleMass]);
        
        sendJSON([
            'success' => true,
            'message' => 'Progresso salvato con successo'
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore salvataggio progresso: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nel salvataggio del progresso'], 500);
    }
}

// STATISTICHE
function getStats() {
    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];
    
    try {
        // Pazienti attivi
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM Users
            WHERE nutritionist_id = ? AND role = 'paziente' AND is_active = 1
        ");
        $stmt->execute([$nutritionistId]);
        $activePatients = $stmt->fetch()['total'];
        
        // Pazienti nuovi questo mese
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM Users
            WHERE nutritionist_id = ?
            AND role = 'paziente'
            AND MONTH(registration_date) = MONTH(CURDATE())
            AND YEAR(registration_date) = YEAR(CURDATE())
        ");
        $stmt->execute([$nutritionistId]);
        $newThisMonth = $stmt->fetch()['total'];
        
        sendJSON([
            'success' => true,
            'stats' => [
                'active_patients' => $activePatients,
                'new_this_month' => $newThisMonth
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore statistiche: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nel recupero delle statistiche'], 500);
    }
}

// OTTIENI PROGRESSI
function getProgress() {
    $db = getDB();
    $patientId = $_GET['patient_id'] ?? null;
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                measurement_date,
                weight_kg,
                body_fat_percent,
                muscle_mass_percent,
                bmi
            FROM PatientProgress
            WHERE patient_id = ?
            ORDER BY measurement_date ASC
        ");
        $stmt->execute([$patientId]);
        $progress = $stmt->fetchAll();
        
        sendJSON([
            'success' => true,
            'progress' => $progress
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore progressi: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nel recupero dei progressi'], 500);
    }
}
?>