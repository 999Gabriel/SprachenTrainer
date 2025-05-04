<?php
// PostgreSQL connection settings
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'cervelingua';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD') ?: 'macintosh';

try {
    // Create PDO connection
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Log successful connection
    error_log("Successfully connected to PostgreSQL database at $host");
} catch (PDOException $e) {
    // Log detailed error
    error_log("Database connection failed: " . $e->getMessage());
    
    // Create a fallback connection or set to null
    $conn = null;
    
    // For debugging - create a file to check if this code is executing
    file_put_contents('/var/www/html/db_error.log', date('Y-m-d H:i:s') . ': ' . $e->getMessage() . "\n", FILE_APPEND);
}

// Add a simple test function to verify the connection
function testDatabaseConnection() {
    global $conn;
    if ($conn) {
        return true;
    }
    return false;
}
?>