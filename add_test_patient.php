<?php
/**
 * Script to add a test patient without nutritionist
 * This will help test the search functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connection.php';

echo "<h1>Add Test Patient</h1>";

try {
    $db = getDB();
    
    // Check if test patient already exists
    $checkStmt = $db->prepare("SELECT id FROM Users WHERE email = ?");
    $checkStmt->execute(['test.paziente@example.com']);
    
    if ($checkStmt->fetch()) {
        echo "<p style='color: orange;'>Test patient already exists!</p>";
        
        // Update to remove nutritionist
        $updateStmt = $db->prepare("UPDATE Users SET nutritionist_id = NULL, provincia = 'BG' WHERE email = ?");
        $updateStmt->execute(['test.paziente@example.com']);
        echo "<p style='color: green;'>Updated test patient: removed nutritionist and set provincia to BG</p>";
    } else {
        // Insert new test patient
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $insertStmt = $db->prepare("
            INSERT INTO Users (
                email, 
                password_hash, 
                first_name, 
                last_name, 
                role, 
                provincia,
                nutritionist_id,
                is_active, 
                email_verified
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertStmt->execute([
            'test.paziente@example.com',
            $password,
            'Test',
            'Paziente',
            'paziente',
            'BG',  // Bergamo
            NULL,  // No nutritionist assigned
            1,     // is_active
            1      // email_verified
        ]);
        
        echo "<p style='color: green;'>✅ Test patient created successfully!</p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> test.paziente@example.com</li>";
        echo "<li><strong>Password:</strong> password123</li>";
        echo "<li><strong>Provincia:</strong> BG (Bergamo)</li>";
        echo "<li><strong>Nutritionist:</strong> None (NULL)</li>";
        echo "</ul>";
    }
    
    // Show all patients without nutritionist
    echo "<h2>All Patients WITHOUT Nutritionist</h2>";
    $stmt = $db->query("
        SELECT id, email, first_name, last_name, provincia, nutritionist_id 
        FROM Users 
        WHERE role = 'paziente' 
        AND (nutritionist_id IS NULL OR nutritionist_id = 0)
        ORDER BY provincia, last_name
    ");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Provincia</th></tr>";
    
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>" . ($row['provincia'] ?? '<span style="color:red;">NULL</span>') . "</td>";
        echo "</tr>";
    }
    
    if ($count === 0) {
        echo "<tr><td colspan='4' style='color: red;'>No patients without nutritionist found!</td></tr>";
    }
    
    echo "</table>";
    echo "<p><strong>Total:</strong> $count patients without nutritionist</p>";
    
    echo "<hr>";
    echo "<p><a href='src/trovaCliente.html'>Go to Trova Clienti page</a></p>";
    echo "<p><a href='debug_users.php'>View all users</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
