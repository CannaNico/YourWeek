<?php
// api/documents.php
require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    sendJSON(['success' => false, 'error' => 'Non autenticato'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        if ($method === 'GET') getDocuments();
        break;
    case 'upload':
        if ($method === 'POST') uploadDocument();
        break;
    case 'download':
        if ($method === 'GET') downloadDocument();
        break;
    case 'delete':
        if ($method === 'DELETE' || $method === 'POST') deleteDocument();
        break;
    default:
        sendJSON(['success' => false, 'error' => 'Azione non valida'], 400);
}

function getDocuments() {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    try {
        $stmt = $db->prepare("
            SELECT 
                id,
                file_name,
                file_type,
                file_size,
                mime_type,
                uploaded_at
            FROM Documents
            WHERE user_id = ?
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$userId]);
        $documents = $stmt->fetchAll();
        
        $formatted = array_map(function($doc) {
            return [
                'id' => $doc['id'],
                'name' => $doc['file_name'],
                'type' => $doc['mime_type'],
                'size' => $doc['file_size'],
                'date' => date('d/m/Y', strtotime($doc['uploaded_at']))
            ];
        }, $documents);
        
        sendJSON(['success' => true, 'documents' => $formatted]);
        
    } catch (PDOException $e) {
        error_log("Errore documenti: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore recupero documenti'], 500);
    }
}

function uploadDocument() {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    if (!isset($_FILES['file'])) {
        sendJSON(['success' => false, 'error' => 'Nessun file caricato'], 400);
    }
    
    $file = $_FILES['file'];
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 
                     'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        sendJSON(['success' => false, 'error' => 'Tipo file non supportato'], 400);
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB max
        sendJSON(['success' => false, 'error' => 'File troppo grande (max 10MB)'], 400);
    }
    
    try {
        // Crea directory se non esiste
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Nome file sicuro
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            sendJSON(['success' => false, 'error' => 'Errore durante il caricamento'], 500);
        }
        
        // Salva nel database
        $stmt = $db->prepare("
            INSERT INTO Documents (user_id, file_name, file_type, file_size, file_path, mime_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $file['name'],
            $extension,
            $file['size'],
            $fileName,
            $file['type']
        ]);
        
        $docId = $db->lastInsertId();
        
        sendJSON([
            'success' => true,
            'message' => 'File caricato con successo',
            'document_id' => $docId
        ], 201);
        
    } catch (PDOException $e) {
        error_log("Errore upload: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore salvataggio file'], 500);
    }
}

function downloadDocument() {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $docId = $_GET['id'] ?? null;
    
    if (!$docId) {
        sendJSON(['success' => false, 'error' => 'ID documento richiesto'], 400);
    }
    
    try {
        $stmt = $db->prepare("
            SELECT file_name, file_path, mime_type
            FROM Documents
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$docId, $userId]);
        $doc = $stmt->fetch();
        
        if (!$doc) {
            sendJSON(['success' => false, 'error' => 'Documento non trovato'], 404);
        }
        
        $filePath = '../uploads/' . $doc['file_path'];
        
        if (!file_exists($filePath)) {
            sendJSON(['success' => false, 'error' => 'File non trovato sul server'], 404);
        }
        
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
        
    } catch (PDOException $e) {
        error_log("Errore download: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore download'], 500);
    }
}

function deleteDocument() {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        parse_str(file_get_contents('php://input'), $_DELETE);
        $docId = $_DELETE['id'] ?? $_GET['id'] ?? null;
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        $docId = $data['id'] ?? null;
    }
    
    if (!$docId) {
        sendJSON(['success' => false, 'error' => 'ID documento richiesto'], 400);
    }
    
    try {
        // Ottieni info file
        $stmt = $db->prepare("SELECT file_path FROM Documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$docId, $userId]);
        $doc = $stmt->fetch();
        
        if (!$doc) {
            sendJSON(['success' => false, 'error' => 'Documento non trovato'], 404);
        }
        
        // Elimina file fisico
        $filePath = '../uploads/' . $doc['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Elimina dal database
        $stmt = $db->prepare("DELETE FROM Documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$docId, $userId]);
        
        sendJSON(['success' => true, 'message' => 'Documento eliminato']);
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione documento: " . $e->getMessage());
        sendJSON(['success' => false, 'error' => 'Errore eliminazione'], 500);
    }
}
?>