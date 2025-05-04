<?php
// Display all PHP errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get environment variables for database connection
$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPassword = getenv('DB_PASSWORD');

echo "Attempting to connect to PostgreSQL:<br>";
echo "Host: $dbHost<br>";
echo "Database: $dbName<br>";
echo "User: $dbUser<br>";
echo "Password: [hidden]<br><br>";

try {
    // Connect to the PostgreSQL database
    $dsn = "pgsql:host=$dbHost;dbname=$dbName";
    $pdo = new PDO($dsn, $dbUser, $dbPassword);
    
    // Configure PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!";
    
    // Test query
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "<br>PostgreSQL version: " . $version;
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>