<?php
// Test script to check database users and provincia values
// Place this in the root directory and access via browser

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db_connection.php';

echo "<h1>Database Debug - Users and Provincia</h1>";

try {
    $db = getDB();
    
    echo "<h2>All Users with Provincia</h2>";
    $stmt = $db->query("SELECT id, email, first_name, last_name, role, provincia, nutritionist_id FROM Users ORDER BY role, id");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Provincia</th><th>Nutritionist ID</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>" . ($row['provincia'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['nutritionist_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>Count by Provincia and Role</h2>";
    $stmt = $db->query("SELECT provincia, role, COUNT(*) as count FROM Users GROUP BY provincia, role");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Provincia</th><th>Role</th><th>Count</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . ($row['provincia'] ?? 'NULL') . "</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td>{$row['count']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>Patients WITHOUT Nutritionist</h2>";
    $stmt = $db->query("SELECT id, email, first_name, last_name, provincia, nutritionist_id FROM Users WHERE role = 'paziente' AND (nutritionist_id IS NULL OR nutritionist_id = 0)");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Provincia</th><th>Nutritionist ID</th></tr>";
    
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>" . ($row['provincia'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['nutritionist_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    if ($count === 0) {
        echo "<tr><td colspan='5' style='color: red; font-weight: bold;'>NO PATIENTS WITHOUT NUTRITIONIST FOUND!</td></tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
