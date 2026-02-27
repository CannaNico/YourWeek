<?php
/**
 * Search Users API
 * Endpoint for searching users by province and role
 * 
 * GET Parameters:
 * - provincia: Province code (e.g., BG, MI, RM)
 * - role: Role to search for ('nutrizionista' or 'paziente')
 * 
 * Returns: JSON with user list
 */

// Enable error reporting for debugging (sviluppo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header (gli header CORS sono gestiti da db_connection.php)
header('Content-Type: application/json; charset=utf-8');

// Start session
session_start();

// Database connection (PDO + session + CORS)
require_once '../includes/db_connection.php';

/**
 * Send JSON response
 */
function sendResponse($success, $data = null, $error = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'users' => $data, // Alias for compatibility
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

/**
 * Check if user is authenticated
 */
function checkAuthentication() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        sendResponse(false, null, 'Non autenticato. Effettua il login.', 401);
    }
    return true;
}

/**
 * Validate provincia code
 */
function validateProvincia($provincia) {
    // List of valid Italian province codes
    $validProvinces = [
        'AG', 'AL', 'AN', 'AO', 'AR', 'AP', 'AT', 'AV', 'BA', 'BT', 'BL', 'BN', 'BG', 'BI', 'BO', 'BZ', 'BS', 'BR',
        'CA', 'CL', 'CB', 'CI', 'CE', 'CT', 'CZ', 'CH', 'CO', 'CS', 'CR', 'KR', 'CN', 'EN', 'FM', 'FE', 'FI', 'FG',
        'FC', 'FR', 'GE', 'GO', 'GR', 'IM', 'IS', 'SP', 'AQ', 'LT', 'LE', 'LC', 'LI', 'LO', 'LU', 'MC', 'MN', 'MS',
        'MT', 'ME', 'MI', 'MO', 'MB', 'NA', 'NO', 'NU', 'OT', 'OR', 'PD', 'PA', 'PR', 'PV', 'PG', 'PU', 'PE', 'PC',
        'PI', 'PT', 'PN', 'PZ', 'PO', 'RG', 'RA', 'RC', 'RE', 'RI', 'RN', 'RM', 'RO', 'SA', 'VS', 'SS', 'SV', 'SI',
        'SR', 'SO', 'TA', 'TE', 'TR', 'TO', 'OG', 'TP', 'TN', 'TV', 'TS', 'UD', 'VA', 'VE', 'VB', 'VC', 'VR', 'VV',
        'VI', 'VT'
    ];
    
    return in_array(strtoupper($provincia), $validProvinces);
}

/**
 * Validate role
 */
function validateRole($role) {
    return in_array($role, ['nutrizionista', 'paziente']);
}

/**
 * Get full province name
 */
function getProvinciaFullName($code) {
    $provinces = [
        'BG' => 'Bergamo',
        'MI' => 'Milano',
        'RM' => 'Roma',
        'NA' => 'Napoli',
        'TO' => 'Torino',
        'FI' => 'Firenze',
        'BO' => 'Bologna',
        'PA' => 'Palermo',
        'GE' => 'Genova',
        'VE' => 'Venezia',
        // Add more as needed
    ];
    
    return $provinces[$code] ?? $code;
}

/**
 * Search users by provincia and role
 */
function searchUsers($db, $provincia, $role, $onlyWithoutNutritionist = false) {
    try {
        // Prepare SQL query
        $sql = "SELECT 
                    id,
                    email,
                    first_name,
                    last_name,
                    role,
                    provincia,
                    nutritionist_code
                FROM Users 
                WHERE UPPER(provincia) = ? 
                AND role = ? 
                AND is_active = TRUE";

        // If requested, for patients show only those without an assigned nutritionist
        if ($onlyWithoutNutritionist && $role === 'paziente') {
            $sql .= " AND (nutritionist_id IS NULL OR nutritionist_id = 0)";
        }

        $sql .= " ORDER BY last_name ASC, first_name ASC";

        $users = [];
        
        // Log the query for debugging
        error_log("Search Query: " . $sql);
        error_log("Parameters: provincia=" . $provincia . ", role=" . $role . ", onlyWithoutNutritionist=" . ($onlyWithoutNutritionist ? 'true' : 'false'));
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$provincia, $role]);
        
        $rowCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rowCount++;
            // Add full province name
            $row['provincia_full'] = getProvinciaFullName($row['provincia']);
            
            // Add description based on role
            if ($row['role'] === 'nutrizionista') {
                $row['description'] = 'Nutrizionista professionista';
                if ($row['nutritionist_code']) {
                    $row['description'] .= ' - Codice: ' . $row['nutritionist_code'];
                }
            } else {
                $row['description'] = 'Cliente YourWeek';
            }
            
            // Remove sensitive data
            unset($row['email']); // Don't expose email in search results
            
            $users[] = $row;
        }
        
        error_log("Search Results: Found " . $rowCount . " users");
        
        return $users;
        
    } catch (Exception $e) {
        error_log('Search error: ' . $e->getMessage());
        throw $e;
    }
}

// ==========================================
// MAIN EXECUTION
// ==========================================

try {
    // Check authentication
    checkAuthentication();
    
    // Get parameters
    $provincia = isset($_GET['provincia']) ? trim($_GET['provincia']) : '';
    $role = isset($_GET['role']) ? trim($_GET['role']) : '';
    $onlyWithoutNutritionist = isset($_GET['only_without_nutritionist']) && $_GET['only_without_nutritionist'] == '1';
    
    // Validate parameters
    if (empty($provincia)) {
        sendResponse(false, null, 'Parametro provincia mancante', 400);
    }
    
    if (empty($role)) {
        sendResponse(false, null, 'Parametro role mancante', 400);
    }
    
    // Validate provincia code
    if (!validateProvincia($provincia)) {
        sendResponse(false, null, 'Codice provincia non valido', 400);
    }
    
    // Validate role
    if (!validateRole($role)) {
        sendResponse(false, null, 'Ruolo non valido. Usa "nutrizionista" o "paziente"', 400);
    }
    
    // Ensure user is searching for the opposite role
    $currentUserRole = $_SESSION['user_role'];
    
    if ($currentUserRole === 'paziente' && $role !== 'nutrizionista') {
        sendResponse(false, null, 'I pazienti possono cercare solo nutrizionisti', 403);
    }
    
    if ($currentUserRole === 'nutrizionista' && $role !== 'paziente') {
        sendResponse(false, null, 'I nutrizionisti possono cercare solo pazienti', 403);
    }
    
    // Search users
    $db = getDB();
    $users = searchUsers($db, strtoupper($provincia), $role, $onlyWithoutNutritionist);
    
    // Log search activity (usiamo PDO come nel resto del progetto)
    $userId = $_SESSION['user_id'];
    $logSql = "INSERT INTO ActivityLog (user_id, action, description) 
               VALUES (?, 'user_search', ?)";
    $logStmt = $db->prepare($logSql);
    $logDesc = "Ricerca utenti: provincia=$provincia, role=$role, risultati=" . count($users);
    $logStmt->execute([$userId, $logDesc]);
    
    // Send success response
    sendResponse(true, $users, null, 200);
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    sendResponse(false, null, 'Errore del server: ' . $e->getMessage(), 500);
}

// Connessione gestita dal singleton PDO (nessuna chiusura esplicita necessaria)
?>
