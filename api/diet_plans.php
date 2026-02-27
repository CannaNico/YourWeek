<?php
// api/diet_plans.php
// API per la gestione dei piani dietetici
require_once __DIR__ . '/../includes/db_connection.php';

// Impedisci accesso diretto al file senza azione
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_my_plan':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getMyDietPlan();
        }
        break;

    case 'get_patient_plan':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getPatientDietPlan();
        }
        break;

    case 'save_plan':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            saveDietPlan();
        }
        break;

    case 'get_my_nutritionist':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getMyNutritionist();
        }
        break;

    case 'get_plan_file':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getPlanFile();
        }
        break;

    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

/**
 * Ottiene il piano dietetico del paziente loggato
 */
function getMyDietPlan() {
    // Verifica autenticazione
    if (!isset($_SESSION['user_id'])) {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $patientId = $_SESSION['user_id'];
    $db = getDB();

    try {
        // Ottieni il piano dietetico attivo del paziente
        $stmt = $db->prepare("
            SELECT 
                dp.id,
                dp.plan_name,
                dp.start_date,
                dp.end_date,
                dp.daily_calories,
                dp.daily_protein_grams,
                dp.daily_carbs_grams,
                dp.daily_fats_grams,
                dp.meals_per_day,
                dp.notes,
                dp.created_at,
                u.first_name as nutritionist_first_name,
                u.last_name as nutritionist_last_name,
                u.email as nutritionist_email
            FROM DietPlans dp
            JOIN Users u ON dp.nutritionist_id = u.id
            WHERE dp.patient_id = ? AND dp.is_active = 1
            ORDER BY dp.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$patientId]);
        $plan = $stmt->fetch();

        if (!$plan) {
            sendJSON([
                'success' => true,
                'hasPlan' => false,
                'message' => 'Nessun piano dietetico attivo'
            ]);
        }

        // Ottieni i pasti del piano
        $stmt = $db->prepare("
            SELECT 
                day_of_week,
                meal_type,
                meal_description,
                calories,
                protein_grams,
                carbs_grams,
                fats_grams
            FROM DietPlanMeals
            WHERE diet_plan_id = ?
            ORDER BY 
                FIELD(day_of_week, 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'),
                FIELD(meal_type, 'colazione', 'spuntino-mattina', 'pranzo', 'spuntino-pomeriggio', 'cena', 'spuntino-sera')
        ");
        $stmt->execute([$plan['id']]);
        $meals = $stmt->fetchAll();

        // Organizza i pasti per giorno
        $organizedMeals = [];
        $dayNames = ['lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'];
        foreach ($dayNames as $day) {
            $organizedMeals[$day] = [];
        }

        foreach ($meals as $meal) {
            $day = $meal['day_of_week'];
            $mealType = $meal['meal_type'];
            $organizedMeals[$day][$mealType] = $meal['meal_description'];
        }

        sendJSON([
            'success' => true,
            'hasPlan' => true,
            'plan' => [
                'id' => $plan['id'],
                'plan_name' => $plan['plan_name'],
                'start_date' => $plan['start_date'],
                'end_date' => $plan['end_date'],
                'daily_calories' => $plan['daily_calories'],
                'notes' => $plan['notes'],
                'created_at' => $plan['created_at']
            ],
            'nutritionist' => [
                'first_name' => $plan['nutritionist_first_name'],
                'last_name' => $plan['nutritionist_last_name'],
                'email' => $plan['nutritionist_email'],
                'full_name' => $plan['nutritionist_first_name'] . ' ' . $plan['nutritionist_last_name']
            ],
            'meals' => $organizedMeals
        ]);

    } catch (PDOException $e) {
        error_log("Errore get_my_diet_plan: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero del piano'], 500);
    }
}

/**
 * Ottiene il piano dietetico di un paziente specifico (per nutrizionisti)
 */
function getPatientDietPlan() {
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
        // Verifica che il paziente appartenga al nutrizionista
        $stmt = $db->prepare("
            SELECT id FROM Users 
            WHERE id = ? AND nutritionist_id = ? AND role = 'paziente'
        ");
        $stmt->execute([$patientId, $nutritionistId]);

        if (!$stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        // Ottieni il piano dietetico attivo
        $stmt = $db->prepare("
            SELECT 
                dp.id,
                dp.plan_name,
                dp.start_date,
                dp.end_date,
                dp.daily_calories,
                dp.daily_protein_grams,
                dp.daily_carbs_grams,
                dp.daily_fats_grams,
                dp.meals_per_day,
                dp.notes,
                dp.created_at
            FROM DietPlans dp
            WHERE dp.patient_id = ? AND dp.nutritionist_id = ? AND dp.is_active = 1
            ORDER BY dp.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$patientId, $nutritionistId]);
        $plan = $stmt->fetch();

        if (!$plan) {
            sendJSON([
                'success' => true,
                'hasPlan' => false,
                'message' => 'Nessun piano dietetico attivo per questo paziente'
            ]);
        }

        // Ottieni i pasti del piano
        $stmt = $db->prepare("
            SELECT 
                day_of_week,
                meal_type,
                meal_description,
                calories,
                protein_grams,
                carbs_grams,
                fats_grams
            FROM DietPlanMeals
            WHERE diet_plan_id = ?
            ORDER BY 
                FIELD(day_of_week, 'lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'),
                FIELD(meal_type, 'colazione', 'spuntino-mattina', 'pranzo', 'spuntino-pomeriggio', 'cena', 'spuntino-sera')
        ");
        $stmt->execute([$plan['id']]);
        $meals = $stmt->fetchAll();

        // Organizza i pasti per giorno
        $organizedMeals = [];
        $dayNames = ['lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'];
        foreach ($dayNames as $day) {
            $organizedMeals[$day] = [];
        }

        foreach ($meals as $meal) {
            $day = $meal['day_of_week'];
            $mealType = $meal['meal_type'];
            $organizedMeals[$day][$mealType] = $meal['meal_description'];
        }

        // Verifica se c'è un file associato al piano
        $fileInfo = null;
        $stmt = $db->prepare("
            SELECT 
                id,
                file_name,
                file_path,
                file_type,
                mime_type,
                file_size,
                uploaded_at
            FROM Documents
            WHERE user_id = ? 
                AND description LIKE ?
            ORDER BY uploaded_at DESC
            LIMIT 1
        ");
        $stmt->execute([$patientId, 'Piano dietetico: ' . $plan['plan_name']]);
        $file = $stmt->fetch();
        
        if ($file) {
            $fileInfo = [
                'id' => $file['id'],
                'file_name' => $file['file_name'],
                'file_path' => $file['file_path'],
                'file_type' => $file['file_type'],
                'mime_type' => $file['mime_type'],
                'file_size' => $file['file_size'],
                'uploaded_at' => $file['uploaded_at']
            ];
        }

        sendJSON([
            'success' => true,
            'hasPlan' => true,
            'plan' => [
                'id' => $plan['id'],
                'plan_name' => $plan['plan_name'],
                'start_date' => $plan['start_date'],
                'end_date' => $plan['end_date'],
                'daily_calories' => $plan['daily_calories'],
                'notes' => $plan['notes'],
                'created_at' => $plan['created_at'],
                'file_path' => $fileInfo ? $fileInfo['file_path'] : null
            ],
            'meals' => $organizedMeals,
            'file' => $fileInfo
        ]);

    } catch (PDOException $e) {
        error_log("Errore get_patient_diet_plan: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero del piano'], 500);
    }
}

/**
 * Salva un nuovo piano dietetico per un paziente
 */
function saveDietPlan() {
    // Verifica autenticazione
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $nutritionistId = $_SESSION['user_id'];
    $db = getDB();

    // Recupera dati dal POST
    $patientId = intval($_POST['patient_id'] ?? 0);
    $inputMethod = $_POST['input_method'] ?? 'manual';
    $planName = sanitizeInput($_POST['plan_name'] ?? 'Piano Dietetico');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    if (!$patientId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }

    try {
        $db->beginTransaction();

        // Verifica che il paziente appartenga al nutrizionista
        $stmt = $db->prepare("
            SELECT id FROM Users 
            WHERE id = ? AND nutritionist_id = ? AND role = 'paziente'
        ");
        $stmt->execute([$patientId, $nutritionistId]);

        if (!$stmt->fetch()) {
            $db->rollBack();
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        // Verifica se esiste già un piano attivo per questo paziente
        $stmt = $db->prepare("
            SELECT id FROM DietPlans 
            WHERE patient_id = ? AND nutritionist_id = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$patientId, $nutritionistId]);
        $existingPlan = $stmt->fetch();

        if ($existingPlan) {
            // Aggiorna il piano esistente invece di crearne uno nuovo
            $dietPlanId = $existingPlan['id'];
            
            $stmt = $db->prepare("
                UPDATE DietPlans 
                SET plan_name = ?, notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$planName, $notes, $dietPlanId]);

            // Elimina i pasti esistenti per ricrearli
            $stmt = $db->prepare("DELETE FROM DietPlanMeals WHERE diet_plan_id = ?");
            $stmt->execute([$dietPlanId]);
        } else {
            // Crea un nuovo piano solo se non esiste
            $stmt = $db->prepare("
                INSERT INTO DietPlans (
                    patient_id, 
                    nutritionist_id, 
                    plan_name, 
                    start_date, 
                    notes, 
                    is_active,
                    created_at
                ) VALUES (?, ?, ?, CURDATE(), ?, 1, NOW())
            ");
            $stmt->execute([
                $patientId,
                $nutritionistId,
                $planName,
                $notes
            ]);

            $dietPlanId = $db->lastInsertId();
        }

        // Se è inserimento manuale, salva i pasti
        if ($inputMethod === 'manual') {
            $mealPlan = json_decode($_POST['meal_plan'] ?? '{}', true);

            $stmt = $db->prepare("
                INSERT INTO DietPlanMeals (
                    diet_plan_id,
                    day_of_week,
                    meal_type,
                    meal_description,
                    created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");

            $mealTypes = ['colazione', 'spuntino-mattina', 'pranzo', 'spuntino-pomeriggio', 'cena', 'spuntino-sera'];
            $dayNames = ['lunedi', 'martedi', 'mercoledi', 'giovedi', 'venerdi', 'sabato', 'domenica'];

            foreach ($dayNames as $day) {
                if (isset($mealPlan[$day]) && is_array($mealPlan[$day])) {
                    foreach ($mealPlan[$day] as $mealType => $description) {
                        if (!empty($description)) {
                            // Mappa i tipi di pasto dal frontend ai tipi del database
                            $dbMealType = mapMealType($mealType);
                            $stmt->execute([
                                $dietPlanId,
                                $day,
                                $dbMealType,
                                $description
                            ]);
                        }
                    }
                }
            }
        }

        // Se è upload file, salva il documento
        if ($inputMethod === 'file' && isset($_FILES['plan_file'])) {
            $file = $_FILES['plan_file'];

            // Validazione file
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $maxSize = 10 * 1024 * 1024; // 10MB

            if (!in_array($file['type'], $allowedTypes)) {
                $db->rollBack();
                sendJSON(['success' => false, 'error' => 'Tipo di file non supportato'], 400);
            }

            if ($file['size'] > $maxSize) {
                $db->rollBack();
                sendJSON(['success' => false, 'error' => 'File troppo grande (max 10MB)'], 400);
            }

            // Crea directory se non esiste
            $uploadDir = __DIR__ . '/../uploads/diet_plans/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Genera nome file univoco
            $fileName = 'diet_plan_' . $patientId . '_' . time() . '_' . basename($file['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Salva info file nel database
                $stmt = $db->prepare("
                    INSERT INTO Documents (
                        user_id,
                        file_name,
                        file_type,
                        file_size,
                        file_path,
                        mime_type,
                        description,
                        uploaded_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $patientId,
                    $file['name'],
                    pathinfo($file['name'], PATHINFO_EXTENSION),
                    $file['size'],
                    'uploads/diet_plans/' . $fileName,
                    $file['type'],
                    'Piano dietetico: ' . $planName
                ]);
            } else {
                $db->rollBack();
                sendJSON(['success' => false, 'error' => 'Errore durante il caricamento del file'], 500);
            }
        }

        // Log attività
        logActivity($db, $nutritionistId, 'create_diet_plan', "Piano dietetico creato per paziente ID $patientId");

        $db->commit();

        sendJSON([
            'success' => true,
            'message' => 'Piano dietetico salvato con successo',
            'plan_id' => $dietPlanId
        ]);

    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Errore save_diet_plan: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il salvataggio del piano'], 500);
    }
}

/**
 * Ottiene le informazioni del nutrizionista assegnato al paziente loggato
 */
function getMyNutritionist() {
    // Verifica autenticazione
    if (!isset($_SESSION['user_id'])) {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $patientId = $_SESSION['user_id'];
    $db = getDB();

    try {
        // Ottieni il nutrizionista assegnato al paziente
        $stmt = $db->prepare("
            SELECT 
                n.id,
                n.first_name,
                n.last_name,
                n.email,
                n.nutritionist_code
            FROM Users p
            JOIN Users n ON p.nutritionist_id = n.id
            WHERE p.id = ? AND p.role = 'paziente' AND n.role = 'nutrizionista'
        ");
        $stmt->execute([$patientId]);
        $nutritionist = $stmt->fetch();

        if (!$nutritionist) {
            sendJSON([
                'success' => true,
                'hasNutritionist' => false,
                'message' => 'Nessun nutrizionista assegnato'
            ]);
        }

        sendJSON([
            'success' => true,
            'hasNutritionist' => true,
            'nutritionist' => [
                'id' => $nutritionist['id'],
                'first_name' => $nutritionist['first_name'],
                'last_name' => $nutritionist['last_name'],
                'full_name' => $nutritionist['first_name'] . ' ' . $nutritionist['last_name'],
                'email' => $nutritionist['email'],
                'code' => $nutritionist['nutritionist_code']
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Errore get_my_nutritionist: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero del nutrizionista'], 500);
    }
}

/**
 * Ottiene il file del piano dietetico per un paziente
 */
function getPlanFile() {
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
        // Verifica che il paziente appartenga al nutrizionista
        $stmt = $db->prepare("
            SELECT id FROM Users 
            WHERE id = ? AND nutritionist_id = ? AND role = 'paziente'
        ");
        $stmt->execute([$patientId, $nutritionistId]);

        if (!$stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        // Ottieni il piano dietetico attivo
        $stmt = $db->prepare("
            SELECT plan_name
            FROM DietPlans
            WHERE patient_id = ? AND nutritionist_id = ? AND is_active = 1
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$patientId, $nutritionistId]);
        $plan = $stmt->fetch();

        if (!$plan) {
            sendJSON([
                'success' => true,
                'file' => null,
                'message' => 'Nessun piano dietetico attivo'
            ]);
        }

        // Ottieni il file associato al piano
        $stmt = $db->prepare("
            SELECT 
                id,
                file_name,
                file_path,
                file_type,
                mime_type,
                file_size,
                uploaded_at
            FROM Documents
            WHERE user_id = ? 
                AND description LIKE ?
            ORDER BY uploaded_at DESC
            LIMIT 1
        ");
        $stmt->execute([$patientId, 'Piano dietetico: ' . $plan['plan_name']]);
        $file = $stmt->fetch();

        if ($file) {
            sendJSON([
                'success' => true,
                'file' => [
                    'id' => $file['id'],
                    'file_name' => $file['file_name'],
                    'file_path' => $file['file_path'],
                    'file_type' => $file['file_type'],
                    'mime_type' => $file['mime_type'],
                    'file_size' => $file['file_size'],
                    'uploaded_at' => $file['uploaded_at']
                ]
            ]);
        } else {
            sendJSON([
                'success' => true,
                'file' => null,
                'message' => 'Nessun file trovato per questo piano'
            ]);
        }

    } catch (PDOException $e) {
        error_log("Errore get_plan_file: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero del file'], 500);
    }
}

/**
 * Mappa i tipi di pasto dal frontend ai tipi del database
 */
function mapMealType($frontendType) {
    $mapping = [
        'colazione' => 'colazione',
        'pranzo' => 'pranzo',
        'cena' => 'cena',
        'spuntini' => 'spuntino-mattina',
        'spuntino-mattina' => 'spuntino-mattina',
        'spuntino-pomeriggio' => 'spuntino-pomeriggio',
        'spuntino-sera' => 'spuntino-sera'
    ];

    return $mapping[$frontendType] ?? 'colazione';
}