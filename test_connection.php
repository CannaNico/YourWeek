<?php
// test_connection.php
// File per testare la connessione al database

// Includi configurazione
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Connessione Database</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        h3 {
            margin-bottom: 10px;
            color: #333;
        }
        .info {
            margin: 5px 0;
            font-size: 14px;
        }
        .label {
            font-weight: bold;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-error {
            background: #dc3545;
            color: white;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test Connessione Database</h1>

        <?php
        // TEST 1: Configurazione
        echo '<div class="test-section success">';
        echo '<h3>✅ Test 1: Configurazione</h3>';
        echo '<div class="info"><span class="label">Ambiente:</span> ' . APP_ENV . '</div>';
        echo '<div class="info"><span class="label">Database Host:</span> ' . DB_HOST . '</div>';
        echo '<div class="info"><span class="label">Database Nome:</span> ' . DB_NAME . '</div>';
        echo '<div class="info"><span class="label">Database User:</span> ' . DB_USER . '</div>';
        echo '<div class="info"><span class="label">Database Port:</span> ' . DB_PORT . '</div>';
        echo '</div>';

        // TEST 2: Connessione PDO
        try {
            require_once 'includes/db_connection.php';
            $db = getDB();
            
            echo '<div class="test-section success">';
            echo '<h3>✅ Test 2: Connessione Database</h3>';
            echo '<div class="info">Connessione PDO stabilita con successo!</div>';
            echo '</div>';
            
            // TEST 3: Verifica Tabelle
            $stmt = $db->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo '<div class="test-section success">';
            echo '<h3>✅ Test 3: Tabelle Database</h3>';
            echo '<div class="info">Trovate ' . count($tables) . ' tabelle:</div>';
            echo '<table>';
            echo '<tr><th>Nome Tabella</th><th>Status</th></tr>';
            foreach ($tables as $table) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($table) . '</td>';
                echo '<td><span class="status-badge badge-success">OK</span></td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            
            // TEST 4: Verifica Utenti
            $stmt = $db->query("SELECT COUNT(*) as total FROM Users");
            $userCount = $stmt->fetch()['total'];
            
            echo '<div class="test-section ' . ($userCount > 0 ? 'success' : 'warning') . '">';
            echo '<h3>' . ($userCount > 0 ? '✅' : '⚠️') . ' Test 4: Utenti nel Database</h3>';
            echo '<div class="info">Totale utenti: <strong>' . $userCount . '</strong></div>';
            
            if ($userCount > 0) {
                $stmt = $db->query("SELECT id, email, first_name, last_name, role FROM Users LIMIT 5");
                $users = $stmt->fetchAll();
                
                echo '<table>';
                echo '<tr><th>ID</th><th>Email</th><th>Nome</th><th>Ruolo</th></tr>';
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<td>' . $user['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>';
                    echo '<td><span class="status-badge badge-success">' . $user['role'] . '</span></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="info" style="margin-top: 10px;">⚠️ Nessun utente trovato. Esegui il file SQL completo per inserire dati di esempio.</div>';
            }
            echo '</div>';
            
            // TEST 5: Test Query
            $testQuery = "SELECT VERSION() as version";
            $stmt = $db->query($testQuery);
            $version = $stmt->fetch()['version'];
            
            echo '<div class="test-section success">';
            echo '<h3>✅ Test 5: Versione MySQL</h3>';
            echo '<div class="info"><span class="label">Versione:</span> ' . htmlspecialchars($version) . '</div>';
            echo '</div>';
            
            // TEST 6: Permessi
            echo '<div class="test-section success">';
            echo '<h3>✅ Test 6: Verifica Permessi</h3>';
            
            // Verifica directory uploads
            $uploadsWritable = is_writable(UPLOAD_DIR);
            echo '<div class="info">';
            echo '<span class="label">Directory Uploads:</span> ';
            echo '<span class="status-badge ' . ($uploadsWritable ? 'badge-success' : 'badge-error') . '">';
            echo $uploadsWritable ? 'Scrivibile' : 'Non scrivibile';
            echo '</span>';
            echo '</div>';
            
            echo '<div class="info"><span class="label">Path:</span> <code>' . UPLOAD_DIR . '</code></div>';
            echo '</div>';
            
            // Riepilogo Finale
            echo '<div class="test-section success" style="margin-top: 30px; text-align: center;">';
            echo '<h3>🎉 Tutti i Test Superati!</h3>';
            echo '<div class="info">Il database è configurato correttamente e pronto all\'uso.</div>';
            echo '<div class="info" style="margin-top: 15px;">';
            echo '<strong>Prossimi passi:</strong><br>';
            echo '1. Accedi a <a href="src/loginPage.html">loginPage.html</a><br>';
            echo '2. Usa le credenziali: <code>dott.rossi@yourweek.com</code> / <code>password123</code><br>';
            echo '3. Inizia a usare l\'applicazione!';
            echo '</div>';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="test-section error">';
            echo '<h3>❌ Test 2: Errore Connessione</h3>';
            echo '<div class="info"><strong>Errore:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="info" style="margin-top: 10px;"><strong>Soluzioni:</strong></div>';
            echo '<ul style="margin-left: 20px; margin-top: 5px;">';
            echo '<li>Verifica che XAMPP sia avviato (Apache e MySQL)</li>';
            echo '<li>Verifica le credenziali in <code>config.php</code></li>';
            echo '<li>Verifica che il database <code>yourweek_db</code> esista</li>';
            echo '<li>Esegui lo script <code>database_schema.sql</code></li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>