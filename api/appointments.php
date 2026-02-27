<?php
// api/appointments.php
// CRUD appointments (nutritionist)
require_once __DIR__ . '/../includes/db_connection.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') createAppointment();
        break;
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') updateAppointment();
        break;
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') deleteAppointment();
        break;
    case 'list_my':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') listMyAppointments();
        break;
    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

function getJsonOrPost()
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) $data = $_POST;
    return is_array($data) ? $data : [];
}

function normalizeType($type)
{
    $map = [
        'prima' => 'prima-visita',
        'controllo' => 'controllo-mensile',
        'followup' => 'follow-up',
        'urgente' => 'urgente',
        'prima-visita' => 'prima-visita',
        'controllo-mensile' => 'controllo-mensile',
        'follow-up' => 'follow-up',
    ];
    return $map[$type] ?? null;
}

function computeEndTime($startTime, $durationMinutes)
{
    $dt = DateTime::createFromFormat('H:i', $startTime);
    if (!$dt) $dt = DateTime::createFromFormat('H:i:s', $startTime);
    if (!$dt) return null;
    $mins = (int) $durationMinutes;
    if ($mins <= 0) return null;
    $dt->modify("+{$mins} minutes");
    return $dt->format('H:i:s');
}

function ensureNutritionist()
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'nutrizionista') {
        sendJSON(['success' => false, 'error' => 'Non autorizzato'], 403);
    }
    return (int) $_SESSION['user_id'];
}

function assertPatientBelongsToNut($db, $patientId, $nutId)
{
    $stmt = $db->prepare("SELECT id FROM Users WHERE id = ? AND role = 'paziente' AND nutritionist_id = ?");
    $stmt->execute([(int) $patientId, (int) $nutId]);
    return (bool) $stmt->fetch();
}

function createAppointment()
{
    $nutId = ensureNutritionist();
    $db = getDB();

    $data = getJsonOrPost();
    $patientId = (int) ($data['patient_id'] ?? 0);
    $date = $data['appointment_date'] ?? '';
    $start = $data['start_time'] ?? '';
    $duration = (int) ($data['duration_minutes'] ?? 60);
    $type = normalizeType($data['appointment_type'] ?? '');
    $notes = sanitizeInput($data['notes'] ?? null);

    if ($patientId <= 0 || !$date || !$start || !$type) {
        sendJSON(['success' => false, 'error' => 'Dati mancanti o non validi'], 400);
    }

    if ($duration <= 0 || $duration > 24 * 60) {
        sendJSON(['success' => false, 'error' => 'Durata non valida'], 400);
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        sendJSON(['success' => false, 'error' => 'Data non valida'], 400);
    }
    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $start)) {
        sendJSON(['success' => false, 'error' => 'Orario non valido'], 400);
    }

    try {
        if (!assertPatientBelongsToNut($db, $patientId, $nutId)) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        $end = computeEndTime($start, $duration);

        $stmt = $db->prepare("
            INSERT INTO Appointments
            (nutritionist_id, patient_id, appointment_date, start_time, end_time, duration_minutes, appointment_type, status, notes, reminder_sent)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, 0)
        ");
        $stmt->execute([
            $nutId,
            $patientId,
            $date,
            $start,
            $end,
            $duration,
            $type,
            $notes
        ]);

        $id = (int) $db->lastInsertId();

        sendJSON(['success' => true, 'appointment' => ['id' => $id]]);
    } catch (PDOException $e) {
        error_log("Errore createAppointment: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il salvataggio'], 500);
    }
}

function updateAppointment()
{
    $nutId = ensureNutritionist();
    $db = getDB();
    $data = getJsonOrPost();

    $appointmentId = (int) ($data['appointment_id'] ?? 0);
    $patientId = (int) ($data['patient_id'] ?? 0);
    $date = $data['appointment_date'] ?? '';
    $start = $data['start_time'] ?? '';
    $duration = (int) ($data['duration_minutes'] ?? 60);
    $type = normalizeType($data['appointment_type'] ?? '');
    $notes = sanitizeInput($data['notes'] ?? null);

    if ($appointmentId <= 0 || $patientId <= 0 || !$date || !$start || !$type) {
        sendJSON(['success' => false, 'error' => 'Dati mancanti o non validi'], 400);
    }

    try {
        if (!assertPatientBelongsToNut($db, $patientId, $nutId)) {
            sendJSON(['success' => false, 'error' => 'Paziente non trovato o non autorizzato'], 404);
        }

        $end = computeEndTime($start, $duration);

        $stmt = $db->prepare("
            UPDATE Appointments
            SET patient_id = ?, appointment_date = ?, start_time = ?, end_time = ?, duration_minutes = ?,
                appointment_type = ?, notes = ?
            WHERE id = ? AND nutritionist_id = ?
        ");
        $stmt->execute([
            $patientId,
            $date,
            $start,
            $end,
            $duration,
            $type,
            $notes,
            $appointmentId,
            $nutId
        ]);

        if ($stmt->rowCount() === 0) {
            sendJSON(['success' => false, 'error' => 'Appuntamento non trovato o non autorizzato'], 404);
        }

        sendJSON(['success' => true]);
    } catch (PDOException $e) {
        error_log("Errore updateAppointment: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante l\'aggiornamento'], 500);
    }
}

function deleteAppointment()
{
    $nutId = ensureNutritionist();
    $db = getDB();
    $data = getJsonOrPost();
    $appointmentId = (int) ($data['appointment_id'] ?? 0);

    if ($appointmentId <= 0) {
        sendJSON(['success' => false, 'error' => 'ID appuntamento richiesto'], 400);
    }

    try {
        $stmt = $db->prepare("DELETE FROM Appointments WHERE id = ? AND nutritionist_id = ?");
        $stmt->execute([$appointmentId, $nutId]);
        if ($stmt->rowCount() === 0) {
            sendJSON(['success' => false, 'error' => 'Appuntamento non trovato o non autorizzato'], 404);
        }
        sendJSON(['success' => true]);
    } catch (PDOException $e) {
        error_log("Errore deleteAppointment: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante l\'eliminazione'], 500);
    }
}

function listMyAppointments()
{
    $nutId = ensureNutritionist();
    $db = getDB();
    try {
        $stmt = $db->prepare("
            SELECT
                a.id,
                a.patient_id,
                a.appointment_date,
                a.start_time,
                a.duration_minutes,
                a.appointment_type,
                a.status,
                a.notes,
                u.first_name,
                u.last_name
            FROM Appointments a
            JOIN Users u ON a.patient_id = u.id
            WHERE a.nutritionist_id = ?
            ORDER BY a.appointment_date ASC, a.start_time ASC
            LIMIT 200
        ");
        $stmt->execute([$nutId]);
        $rows = $stmt->fetchAll();

        sendJSON(['success' => true, 'appointments' => $rows]);
    } catch (PDOException $e) {
        error_log("Errore listMyAppointments: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore durante il recupero'], 500);
    }
}

?>

