<?php
// api/reports.php
// API per analisi, report e recensioni post-visita
require_once __DIR__ . '/../includes/db_connection.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_report_data':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getReportData();
        }
        break;

    case 'submit_review':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            submitReview();
        }
        break;

    case 'get_pending_review':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getPendingReview();
        }
        break;

    case 'get_patient_history':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getPatientHistory();
        }
        break;

    case 'mark_appointment_completed':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            markAppointmentCompleted();
        }
        break;

    case 'get_patient_dashboard':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            getPatientDashboard();
        }
        break;

    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

// =====================================================
// FUNZIONI API
// =====================================================

/**
 * Restituisce tutti i dati per la pagina report del nutrizionista:
 * - pazienti con storico misurazioni
 * - prossimi appuntamenti
 * - statistiche studio (pazienti attivi, appuntamenti mese, media valutazioni)
 * - tutte le recensioni ricevute
 */
function getReportData()
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutId = $_SESSION['user_id'];

    try {
        // --- Pazienti con dati e ultima misurazione ---
        $stmt = $db->prepare("
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.birth_date,
                u.height_cm,
                u.initial_weight,
                u.initial_body_fat,
                u.initial_muscle_mass,
                u.registration_date,
                pp.weight_kg   AS current_weight,
                pp.body_fat_percent AS current_body_fat,
                pp.muscle_mass_percent AS current_muscle_mass,
                pp.bmi         AS current_bmi,
                pp.measurement_date AS last_measurement_date
            FROM Users u
            LEFT JOIN (
                SELECT patient_id, weight_kg, body_fat_percent, muscle_mass_percent, bmi, measurement_date
                FROM PatientProgress pp1
                WHERE measurement_date = (
                    SELECT MAX(measurement_date) FROM PatientProgress pp2 WHERE pp2.patient_id = pp1.patient_id
                )
            ) pp ON u.id = pp.patient_id
            WHERE u.role = 'paziente' AND u.is_active = 1 AND u.nutritionist_id = ?
            ORDER BY u.last_name, u.first_name
        ");
        $stmt->execute([$nutId]);
        $patients = $stmt->fetchAll();

        // --- Storico peso completo per ogni paziente (per grafici) ---
        foreach ($patients as &$p) {
            $stmtH = $db->prepare("
                SELECT measurement_date, weight_kg, body_fat_percent, muscle_mass_percent, bmi, notes
                FROM PatientProgress
                WHERE patient_id = ?
                ORDER BY measurement_date ASC
            ");
            $stmtH->execute([$p['id']]);
            $p['history'] = $stmtH->fetchAll();
        }
        unset($p);

        // --- Prossimi appuntamenti (tutti, non solo i 5) ---
        $stmtA = $db->prepare("
            SELECT
                a.id,
                a.appointment_date,
                a.start_time,
                a.appointment_type,
                a.status,
                a.notes,
                u.first_name,
                u.last_name
            FROM Appointments a
            JOIN Users u ON a.patient_id = u.id
            WHERE a.nutritionist_id = ?
                AND a.appointment_date >= CURDATE()
                AND a.status = 'scheduled'
            ORDER BY a.appointment_date ASC, a.start_time ASC
            LIMIT 20
        ");
        $stmtA->execute([$nutId]);
        $upcoming = $stmtA->fetchAll();

        // --- Conteggio pazienti attivi ---
        $stmtC = $db->prepare("
            SELECT COUNT(*) as total FROM Users
            WHERE nutritionist_id = ? AND role = 'paziente' AND is_active = 1
        ");
        $stmtC->execute([$nutId]);
        $activeCount = (int) $stmtC->fetch()['total'];

        // --- Appuntamenti del mese corrente (completati + programmati) ---
        $stmtM = $db->prepare("
            SELECT COUNT(*) as total FROM Appointments
            WHERE nutritionist_id = ?
                AND YEAR(appointment_date) = YEAR(CURDATE())
                AND MONTH(appointment_date) = MONTH(CURDATE())
        ");
        $stmtM->execute([$nutId]);
        $monthAppt = (int) $stmtM->fetch()['total'];

        // --- Nuovi pazienti questo mese ---
        $stmtN = $db->prepare("
            SELECT COUNT(*) as total FROM Users
            WHERE nutritionist_id = ? AND role = 'paziente' AND is_active = 1
                AND YEAR(registration_date) = YEAR(CURDATE())
                AND MONTH(registration_date) = MONTH(CURDATE())
        ");
        $stmtN->execute([$nutId]);
        $newThisMonth = (int) $stmtN->fetch()['total'];

        // --- Recensioni ricevute ---
        $stmtR = $db->prepare("
            SELECT
                vr.id,
                vr.rating,
                vr.comment,
                vr.created_at,
                u.first_name,
                u.last_name,
                a.appointment_date,
                a.appointment_type
            FROM VisitReviews vr
            JOIN Users u ON vr.patient_id = u.id
            JOIN Appointments a ON vr.appointment_id = a.id
            WHERE vr.nutritionist_id = ?
            ORDER BY vr.created_at DESC
        ");
        $stmtR->execute([$nutId]);
        $reviews = $stmtR->fetchAll();

        // Media valutazioni
        $avgRating = 0;
        if (count($reviews) > 0) {
            $avgRating = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
        }

        sendJSON([
            'success' => true,
            'patients' => $patients,
            'upcoming' => $upcoming,
            'reviews' => $reviews,
            'stats' => [
                'active_patients' => $activeCount,
                'month_appointments' => $monthAppt,
                'new_this_month' => $newThisMonth,
                'avg_rating' => $avgRating,
                'total_reviews' => count($reviews)
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Errore get_report_data: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nel recupero dei dati'], 500);
    }
}

/**
 * Controlla se il paziente loggato ha una visita completata in attesa di recensione
 */
function getPendingReview()
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'paziente') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $patientId = $_SESSION['user_id'];

    try {
        // Trova appuntamenti completati senza recensione
        $stmt = $db->prepare("
            SELECT
                a.id,
                a.appointment_date,
                a.appointment_type,
                n.first_name AS nutritionist_first,
                n.last_name  AS nutritionist_last
            FROM Appointments a
            JOIN Users n ON a.nutritionist_id = n.id
            LEFT JOIN VisitReviews vr ON vr.appointment_id = a.id
            WHERE a.patient_id = ?
                AND a.status = 'completed'
                AND vr.id IS NULL
            ORDER BY a.appointment_date DESC
            LIMIT 1
        ");
        $stmt->execute([$patientId]);
        $pending = $stmt->fetch();

        if ($pending) {
            sendJSON(['success' => true, 'pending' => $pending]);
        } else {
            sendJSON(['success' => true, 'pending' => null]);
        }
    } catch (PDOException $e) {
        error_log("Errore get_pending_review: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore'], 500);
    }
}

/**
 * Il paziente invia una recensione con valutazione a mele (1-5)
 */
function submitReview()
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'paziente') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $patientId = $_SESSION['user_id'];

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $appointmentId = intval($data['appointment_id'] ?? 0);
    $rating = intval($data['rating'] ?? 0);
    $comment = sanitizeInput($data['comment'] ?? '');

    if (!$appointmentId || $rating < 1 || $rating > 5) {
        sendJSON(['success' => false, 'error' => 'Dati non validi. Valutazione 1-5 richiesta.'], 400);
    }

    try {
        // Verifica che l'appuntamento sia del paziente e completato
        $stmt = $db->prepare("
            SELECT id, nutritionist_id FROM Appointments
            WHERE id = ? AND patient_id = ? AND status = 'completed'
        ");
        $stmt->execute([$appointmentId, $patientId]);
        $appt = $stmt->fetch();

        if (!$appt) {
            sendJSON(['success' => false, 'error' => 'Appuntamento non trovato o non completato'], 404);
        }

        // Inserisci la recensione (ignora se già esiste)
        $stmtI = $db->prepare("
            INSERT IGNORE INTO VisitReviews (appointment_id, patient_id, nutritionist_id, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtI->execute([$appointmentId, $patientId, $appt['nutritionist_id'], $rating, $comment]);

        sendJSON(['success' => true, 'message' => 'Recensione inviata. Grazie!']);

    } catch (PDOException $e) {
        error_log("Errore submit_review: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante l\'invio della recensione'], 500);
    }
}

/**
 * Marks an appointment as completed (called by nutritionist)
 */
function markAppointmentCompleted()
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutId = $_SESSION['user_id'];

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $appointmentId = intval($data['appointment_id'] ?? 0);

    if (!$appointmentId) {
        sendJSON(['success' => false, 'error' => 'ID appuntamento richiesto'], 400);
    }

    try {
        $stmt = $db->prepare("
            UPDATE Appointments SET status = 'completed'
            WHERE id = ? AND nutritionist_id = ?
        ");
        $stmt->execute([$appointmentId, $nutId]);

        if ($stmt->rowCount() === 0) {
            sendJSON(['success' => false, 'error' => 'Appuntamento non trovato o non autorizzato'], 404);
        }

        sendJSON(['success' => true, 'message' => 'Visita completata. Il paziente riceverà la richiesta di valutazione.']);

    } catch (PDOException $e) {
        error_log("Errore mark_appointment_completed: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore'], 500);
    }
}

/**
 * Storico completo misurazioni di un paziente specifico
 */
function getPatientHistory()
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $nutId = $_SESSION['user_id'];
    $patId = intval($_GET['patient_id'] ?? 0);

    if (!$patId) {
        sendJSON(['success' => false, 'error' => 'ID paziente richiesto'], 400);
    }

    try {
        // Verifica appartenenza
        $stmt = $db->prepare("SELECT id FROM Users WHERE id = ? AND nutritionist_id = ? AND role = 'paziente'");
        $stmt->execute([$patId, $nutId]);
        if (!$stmt->fetch()) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato'], 404);
        }

        $stmtH = $db->prepare("
            SELECT measurement_date, weight_kg, body_fat_percent, muscle_mass_percent, bmi, notes
            FROM PatientProgress WHERE patient_id = ? ORDER BY measurement_date ASC
        ");
        $stmtH->execute([$patId]);
        $history = $stmtH->fetchAll();

        sendJSON(['success' => true, 'history' => $history]);
    } catch (PDOException $e) {
        error_log("Errore getPatientHistory: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore'], 500);
    }
}

/**
 * Restituisce tutti i dati per la dashboard del paziente loggato:
 * - profilo (dati anagrafici + nutrizionista assegnato)
 * - storico misurazioni (per grafici progressi)
 * - appuntamenti (passati e futuri)
 * - recensioni già inviate
 */
function getPatientDashboard()
{
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'paziente') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }

    $db = getDB();
    $patientId = $_SESSION['user_id'];

    try {
        // --- Profilo paziente + nutrizionista assegnato ---
        $stmt = $db->prepare("
            SELECT
                p.id, p.first_name, p.last_name, p.email, p.birth_date,
                p.height_cm, p.initial_weight, p.initial_body_fat, p.initial_muscle_mass,
                p.registration_date,
                n.id AS nutritionist_id,
                n.first_name AS nut_first_name,
                n.last_name  AS nut_last_name,
                n.email      AS nut_email
            FROM Users p
            LEFT JOIN Users n ON p.nutritionist_id = n.id
            WHERE p.id = ? AND p.role = 'paziente'
        ");
        $stmt->execute([$patientId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato'], 404);
        }

        // --- Storico misurazioni ---
        $stmtH = $db->prepare("
            SELECT id, measurement_date, weight_kg, body_fat_percent,
                   muscle_mass_percent, bmi, notes
            FROM PatientProgress
            WHERE patient_id = ?
            ORDER BY measurement_date ASC
        ");
        $stmtH->execute([$patientId]);
        $history = $stmtH->fetchAll();

        // Calcola la variazione peso rispetto al valore iniziale
        $weightChange = null;
        if ($profile['initial_weight'] && count($history) > 0) {
            $latest = end($history);
            $weightChange = round($latest['weight_kg'] - $profile['initial_weight'], 1);
        }

        // --- Appuntamenti futuri ---
        $stmtUp = $db->prepare("
            SELECT
                a.id, a.appointment_date, a.start_time, a.end_time,
                a.appointment_type, a.status, a.notes,
                n.first_name AS nut_first, n.last_name AS nut_last
            FROM Appointments a
            JOIN Users n ON a.nutritionist_id = n.id
            WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
              AND a.status = 'scheduled'
            ORDER BY a.appointment_date ASC, a.start_time ASC
            LIMIT 10
        ");
        $stmtUp->execute([$patientId]);
        $upcoming = $stmtUp->fetchAll();

        // --- Appuntamenti passati (completati + altri) ---
        $stmtPast = $db->prepare("
            SELECT
                a.id, a.appointment_date, a.start_time,
                a.appointment_type, a.status, a.notes,
                n.first_name AS nut_first, n.last_name AS nut_last,
                vr.rating AS review_rating
            FROM Appointments a
            JOIN Users n ON a.nutritionist_id = n.id
            LEFT JOIN VisitReviews vr ON vr.appointment_id = a.id
            WHERE a.patient_id = ?
              AND (a.appointment_date < CURDATE()
                   OR a.status IN ('completed','cancelled','no-show'))
            ORDER BY a.appointment_date DESC
            LIMIT 20
        ");
        $stmtPast->execute([$patientId]);
        $past = $stmtPast->fetchAll();

        // --- Le recensioni inviate dal paziente ---
        $stmtR = $db->prepare("
            SELECT
                vr.id, vr.rating, vr.comment, vr.created_at,
                a.appointment_date, a.appointment_type,
                n.first_name AS nut_first, n.last_name AS nut_last
            FROM VisitReviews vr
            JOIN Appointments a ON vr.appointment_id = a.id
            JOIN Users n ON vr.nutritionist_id = n.id
            WHERE vr.patient_id = ?
            ORDER BY vr.created_at DESC
        ");
        $stmtR->execute([$patientId]);
        $myReviews = $stmtR->fetchAll();

        sendJSON([
            'success' => true,
            'profile' => $profile,
            'history' => $history,
            'upcoming' => $upcoming,
            'past' => $past,
            'my_reviews' => $myReviews,
            'weight_change' => $weightChange,
        ]);

    } catch (PDOException $e) {
        error_log("Errore getPatientDashboard: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore nel caricamento della dashboard'], 500);
    }
}
?>