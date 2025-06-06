<?php
// Display all PHP errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Datenbankverbindungstest</h1>";

// Umgebungsvariablen anzeigen
echo "<h2>Umgebungsvariablen:</h2>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'nicht gesetzt') . "<br>";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'nicht gesetzt') . "<br>";
echo "DB_USER: " . (getenv('DB_USER') ?: 'nicht gesetzt') . "<br>";
echo "DB_PASSWORD: " . (getenv('DB_PASSWORD') ?: 'nicht gesetzt') . "<br><br>";

// Verbindung mit Docker-Umgebungsvariablen testen
echo "<h2>Test mit Docker-Umgebungsvariablen:</h2>";
try {
    $db_host = getenv('DB_HOST') ?: 'db';
    $db_name = getenv('DB_NAME') ?: 'cervelingua';
    $db_user = getenv('DB_USER') ?: 'postgres';
    $db_password = getenv('DB_PASSWORD') ?: 'root';
    
    echo "Verbindungsparameter:<br>";
    echo "Host: $db_host<br>";
    echo "Datenbank: $db_name<br>";
    echo "Benutzer: $db_user<br>";
    echo "Passwort: $db_password<br><br>";
    
    $pdo = new PDO(
        "pgsql:host=$db_host;dbname=$db_name", 
        $db_user, 
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<span style='color:green'>✅ Verbindung erfolgreich!</span><br>";
    
    // Tabellen auflisten
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Gefundene Tabellen: " . count($tables) . "<br>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<span style='color:red'>❌ Verbindungsfehler: " . $e->getMessage() . "</span><br>";
}
?>