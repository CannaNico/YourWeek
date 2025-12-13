<?php
// api/appointments.php
require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    sendJSON(['success' => false, 'error' => 'Non autenticato'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        if ($method === 'GET') getAppointments();
        break;
    case 'create':
        if ($method === 'POST') createAppointment();
        break;
    case 'delete':
        if ($method === 'DELETE' || $method === 'POST') deleteAppointment();
        break;
    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

function getAppointments() {
    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];
    
    try {
        $stmt = $db->prepare("
            SELECT 
                a.id,
                a.appointment_date,
                a.start_time,
                a.duration_minutes,
                a.appointment_type,
                a.notes,
                a.status,
                u.first_name,
                u.last_name
            FROM Appointments a
            JOIN Users u ON a.patient_id = u.id
            WHERE a.nutritionist_id = ?
            AND a.status != 'cancelled'
            ORDER BY a.appointment_date ASC, a.start_time ASC
        ");
        $stmt->execute([$nutritionistId]);
        $appointments = $stmt->fetchAll();
        
        $formatted = array_map(function($apt) {
            return [
                'id' => $apt['id'],
                'patientName' => $apt['first_name'] . ' ' . $apt['last_name'],
                'date' => $apt['appointment_date'],
                'time' => substr($apt['start_time'], 0, 5),
                'duration' => $apt['duration_minutes'],
                'type' => $apt['appointment_type'],
                'typeLabel' => ucfirst(str_replace('-', ' ', $apt['appointment_type'])),
                'notes' => $apt['notes'],
                'reminder' => true
            ];
        }, $appointments);
        
        sendJSON(['success' => true, 'appointments' => $formatted]);
    } catch (PDOException $e) {
        error_log("Errore appuntamenti: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore recupero appuntamenti'], 500);
    }
}

function createAppointment() {
    $db = getDB();
    $nutritionistId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    
    $patientId = $data['patient_id'] ?? null;
    $date = $data['date'] ?? null;
    $time = $data['time'] ?? null;
    $duration = $data['duration'] ?? 60;
    $type = $data['type'] ?? 'controllo mensile';
    $notes = $data['notes'] ?? '';
    
    if (!$patientId || !$date || !$time) {
        sendJSON(['success' => false, 'error' => 'Dati mancanti'], 400);
    }
    
    try {
        $endTime = date('H:i:s', strtotime($time) + ($duration * 60));
        
        $stmt = $db->prepare("
            INSERT INTO Appointments (
                nutritionist_id, patient_id, appointment_date,
                start_time, end_time, duration_minutes,
                appointment_type, notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        
        $stmt->execute([
            $nutritionistId, $patientId, $date,
            $time, $endTime, $duration, $type, $notes
        ]);
        
        sendJSON(['success' => true, 'message' => 'Appuntamento creato'], 201);
    } catch (PDOException $e) {
        error_log("Errore creazione appuntamento: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore creazione appuntamento'], 500);
    }
}

function deleteAppointment() {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $appointmentId = $data['id'] ?? $_GET['id'] ?? null;
    
    if (!$appointmentId) {
        sendJSON(['success' => false, 'error' => 'ID richiesto'], 400);
    }
    
    try {
        $stmt = $db->prepare("UPDATE Appointments SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$appointmentId]);
        sendJSON(['success' => true, 'message' => 'Appuntamento cancellato']);
    } catch (PDOException $e) {
        error_log("Errore cancellazione: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore cancellazione'], 500);
    }
}
?>