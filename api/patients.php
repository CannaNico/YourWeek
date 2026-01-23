<?php
// api/patients.php
// API per gestione pazienti con validazione completa
require_once '../includes/db_connection.php';

// Verifica autenticazione
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Route handling
switch ($action) {
    case 'list':
        if ($method === 'GET') getPatients();
        break;
    
    case 'get':
        if ($method === 'GET') getPatient();
        break;
    
    case 'create':
        if ($method === 'POST') createPatient();
        break;
    
    case 'update':
        if ($method === 'PUT' || $method === 'POST') updatePatient();
        break;
    
    case 'delete':
        if ($method === 'DELETE' || $method === 'POST') deletePatient();
        break;
    
    case 'progress':
        if ($method === 'POST') addProgress();
        elseif ($method === 'GET') getProgress();
        break;
    
    case 'stats':
        if ($method === 'GET') getStats();
        break;
    
    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

// === LISTA PAZIENTI ===
function getPatients() {
    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];
    
    // Solo i nutrizionisti possono vedere i pazienti
    if (!hasRole('nutrizionista')) {
        sendJSON(['success' => false, 'error' => 'Permessi insufficienti'], 403);
    }
    
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
            $weight = $p['current_weight'] ?? $p['initial_weight'];
            $bodyFat = $p['current_body_fat'] ?? $p['initial_body_fat'];
            $muscleMass = $p['current_muscle_mass'] ?? $p['initial_muscle_mass'];
            
            return [
                'id' => (int)$p['id'],
                'name' => $p['first_name'],
                'surname' => $p['last_name'],
                'avatar' => strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)),
                'email' => $p['email'],
                'birthDate' => $p['birth_date'] ? date('d/m/Y', strtotime($p['birth_date'])) : null,
                'registrationDate' => $p['registration_date'],
                'weight' => $weight ? number_format($weight, 1) . ' kg' : 'N/A',
                'height' => $p['height_cm'] ? $p['height_cm'] . ' cm' : 'N/A',
                'bodyFat' => $bodyFat ? number_format($bodyFat, 1) . '%' : 'N/A',
                'muscleMass' => $muscleMass ? number_format($muscleMass, 1) . '%' : 'N/A',
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

// === DETTAGLIO PAZIENTE ===
function getPatient() {
    $db = getDB();
    $patientId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente non valido'], 400);
    }
    
    // Verifica permessi
    if (!hasRole('nutrizionista')) {
        // Se è un paziente, può vedere solo i propri dati
        if (!hasRole('paziente') || $_SESSION['user_id'] != $patientId) {
            sendJSON(['success' => false, 'error' => 'Permessi insufficienti'], 403);
        }
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                id, email, first_name, last_name, birth_date, 
                height_cm, initial_weight, initial_body_fat, 
                initial_muscle_mass, registration_date, last_login
            FROM Users 
            WHERE id = ? AND role = 'paziente' AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();
        
        if (!$patient) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato'], 404);
        }
        
        // Se nutrizionista, verifica che sia suo paziente
        if (hasRole('nutrizionista')) {
            $stmt = $db->prepare("SELECT nutritionist_id FROM Users WHERE id = ?");
            $stmt->execute([$patientId]);
            $nutritionistId = $stmt->fetchColumn();
            
            if ($nutritionistId != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'error' => 'Questo paziente non è assegnato a te'], 403);
            }
        }
        
        // Rimuovi dati sensibili se necessario
        unset($patient['password_hash']);
        
        sendJSON([
            'success' => true,
            'patient' => $patient
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore dettaglio paziente: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nel recupero del paziente'], 500);
    }
}

// === CREA PAZIENTE ===
function createPatient() {
    requireRole('nutrizionista'); // Solo nutrizionisti possono creare pazienti
    
    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validazione input
    $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $firstName = sanitizeInput($data['first_name'] ?? '');
    $lastName = sanitizeInput($data['last_name'] ?? '');
    $birthDate = $data['birth_date'] ?? null;
    $height = filter_var($data['height_cm'] ?? null, FILTER_VALIDATE_FLOAT);
    $initialWeight = filter_var($data['initial_weight'] ?? null, FILTER_VALIDATE_FLOAT);
    $initialBodyFat = filter_var($data['initial_body_fat'] ?? null, FILTER_VALIDATE_FLOAT);
    $initialMuscleMass = filter_var($data['initial_muscle_mass'] ?? null, FILTER_VALIDATE_FLOAT);
    
    // Controlli obbligatori
    if (!$email || empty($firstName) || empty($lastName)) {
        sendJSON(['success' => false, 'error' => 'Email, nome e cognome sono obbligatori'], 400);
    }
    
    // Validazione ulteriore
    if ($height !== null && ($height < 50 || $height > 250)) {
        sendJSON(['success' => false, 'error' => 'Altezza non valida (deve essere tra 50 e 250 cm)'], 400);
    }
    
    if ($initialWeight !== null && ($initialWeight < 20 || $initialWeight > 300)) {
        sendJSON(['success' => false, 'error' => 'Peso non valido (deve essere tra 20 e 300 kg)'], 400);
    }
    
    if ($initialBodyFat !== null && ($initialBodyFat < 0 || $initialBodyFat > 100)) {
        sendJSON(['success' => false, 'error' => 'Percentuale massa grassa non valida'], 400);
    }
    
    if ($initialMuscleMass !== null && ($initialMuscleMass < 0 || $initialMuscleMass > 100)) {
        sendJSON(['success' => false, 'error' => 'Percentuale massa muscolare non valida'], 400);
    }
    
    try {
        // Verifica email duplicata
        $stmt = $db->prepare("SELECT id FROM Users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Email già registrata'], 409);
        }
        
        // Inizia transazione
        beginTransaction();
        
        // Password temporanea sicura
        $tempPassword = bin2hex(random_bytes(8));
        $passwordHash = hashPassword($tempPassword);
        
        // Inserisci utente
        $stmt = $db->prepare("
            INSERT INTO Users (
                email, password_hash, first_name, last_name, role,
                birth_date, height_cm, initial_weight, initial_body_fat,
                initial_muscle_mass, nutritionist_id, registration_date, is_active
            ) VALUES (?, ?, ?, ?, 'paziente', ?, ?, ?, ?, ?, ?, NOW(), 1)
        ");
        
        $stmt->execute([
            $email, $passwordHash, $firstName, $lastName,
            $birthDate, $height, $initialWeight, 
            $initialBodyFat, $initialMuscleMass, $nutritionistId
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
        
        // Log attività
        logActivity($db, $nutritionistId, 'patient_created', "Creato paziente: $firstName $lastName (ID: $patientId)");
        
        // Commit transazione
        commit();
        
        // TODO: Invia email con password temporanea
        // sendWelcomeEmail($email, $firstName, $tempPassword);
        
        sendJSON([
            'success' => true,
            'message' => 'Paziente creato con successo',
            'patient_id' => $patientId,
            'temp_password' => $tempPassword // In produzione, invia via email
        ], 201);
        
    } catch (PDOException $e) {
        rollback();
        error_log("Errore creazione paziente: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nella creazione del paziente'], 500);
    }
}

// === AGGIORNA PAZIENTE ===
function updatePatient() {
    requireRole('nutrizionista');
    
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $patientId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    
    // Verifica che sia paziente del nutrizionista
    $stmt = $db->prepare("SELECT nutritionist_id FROM Users WHERE id = ? AND role = 'paziente'");
    $stmt->execute([$patientId]);
    $nutritionistId = $stmt->fetchColumn();
    
    if ($nutritionistId != $_SESSION['user_id']) {
        sendJSON(['success' => false, 'error' => 'Non autorizzato a modificare questo paziente'], 403);
    }
    
    try {
        $updates = [];
        $params = [];
        
        // Campi modificabili
        if (isset($data['height_cm'])) {
            $height = filter_var($data['height_cm'], FILTER_VALIDATE_FLOAT);
            if ($height && $height >= 50 && $height <= 250) {
                $updates[] = "height_cm = ?";
                $params[] = $height;
            }
        }
        
        if (isset($data['first_name'])) {
            $updates[] = "first_name = ?";
            $params[] = sanitizeInput($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $updates[] = "last_name = ?";
            $params[] = sanitizeInput($data['last_name']);
        }
        
        if (isset($data['birth_date'])) {
            $updates[] = "birth_date = ?";
            $params[] = $data['birth_date'];
        }
        
        if (empty($updates)) {
            sendJSON(['success' => false, 'error' => 'Nessun campo da aggiornare'], 400);
        }
        
        $params[] = $patientId;
        $sql = "UPDATE Users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        logActivity($db, $_SESSION['user_id'], 'patient_updated', "Aggiornato paziente ID: $patientId");
        
        sendJSON([
            'success' => true,
            'message' => 'Paziente aggiornato con successo'
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore aggiornamento paziente: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore aggiornamento'], 500);
    }
}

// === ELIMINA PAZIENTE (SOFT DELETE) ===
function deletePatient() {
    requireRole('nutrizionista');
    
    $db = getDB();
    
    // Supporta DELETE e POST
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        parse_str(file_get_contents('php://input'), $_DELETE);
        $patientId = filter_var($_DELETE['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $patientId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    }
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    
    try {
        // Verifica proprietà
        $stmt = $db->prepare("SELECT nutritionist_id FROM Users WHERE id = ? AND role = 'paziente'");
        $stmt->execute([$patientId]);
        $nutritionistId = $stmt->fetchColumn();
        
        if ($nutritionistId != $_SESSION['user_id']) {
            sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
        }
        
        // Soft delete
        $stmt = $db->prepare("UPDATE Users SET is_active = 0, nutritionist_id = NULL WHERE id = ?");
        $stmt->execute([$patientId]);
        
        logActivity($db, $_SESSION['user_id'], 'patient_deleted', "Eliminato paziente ID: $patientId");
        
        sendJSON(['success' => true, 'message' => 'Paziente eliminato']);
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore eliminazione'], 500);
    }
}

// === AGGIUNGI PROGRESSO ===
function addProgress() {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $patientId = filter_var($data['patient_id'] ?? null, FILTER_VALIDATE_INT);
    $weight = filter_var($data['weight_kg'] ?? null, FILTER_VALIDATE_FLOAT);
    $bodyFat = filter_var($data['body_fat_percent'] ?? null, FILTER_VALIDATE_FLOAT);
    $muscleMass = filter_var($data['muscle_mass_percent'] ?? null, FILTER_VALIDATE_FLOAT);
    $date = $data['measurement_date'] ?? date('Y-m-d');
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    
    // Validazione
    if ($weight !== null && ($weight < 20 || $weight > 300)) {
        sendJSON(['success' => false, 'error' => 'Peso non valido'], 400);
    }
    
    if ($bodyFat !== null && ($bodyFat < 0 || $bodyFat > 100)) {
        sendJSON(['success' => false, 'error' => 'Massa grassa non valida'], 400);
    }
    
    if ($muscleMass !== null && ($muscleMass < 0 || $muscleMass > 100)) {
        sendJSON(['success' => false, 'error' => 'Massa muscolare non valida'], 400);
    }
    
    try {
        // Verifica permessi
        if (hasRole('nutrizionista')) {
            $stmt = $db->prepare("SELECT nutritionist_id FROM Users WHERE id = ?");
            $stmt->execute([$patientId]);
            if ($stmt->fetchColumn() != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
            }
        } elseif (hasRole('paziente')) {
            if ($patientId != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
            }
        }
        
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
        
        logActivity($db, $_SESSION['user_id'], 'progress_added', "Aggiunto progresso paziente ID: $patientId");
        
        sendJSON(['success' => true, 'message' => 'Progresso salvato']);
        
    } catch (PDOException $e) {
        error_log("Errore progresso: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore salvataggio'], 500);
    }
}

// === OTTIENI PROGRESSI ===
function getProgress() {
    $db = getDB();
    $patientId = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }
    
    try {
        // Verifica permessi
        if (hasRole('nutrizionista')) {
            $stmt = $db->prepare("SELECT nutritionist_id FROM Users WHERE id = ?");
            $stmt->execute([$patientId]);
            if ($stmt->fetchColumn() != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
            }
        } elseif (hasRole('paziente')) {
            if ($patientId != $_SESSION['user_id']) {
                sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
            }
        }
        
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
        
        sendJSON(['success' => true, 'progress' => $progress]);
        
    } catch (PDOException $e) {
        error_log("Errore progressi: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore recupero progressi'], 500);
    }
}

// === STATISTICHE ===
function getStats() {
    requireRole('nutrizionista');
    
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
        
        // Nuovi questo mese
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
                'active_patients' => (int)$activePatients,
                'new_this_month' => (int)$newThisMonth
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore statistiche: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore statistiche'], 500);
    }
}
?>