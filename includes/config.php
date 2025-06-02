<?php
// Only set session parameters if no session is active yet
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1); // Force sessions to only use cookies
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
}

// Database connection settings
$db_host = getenv('DB_HOST') ?: 'db'; // Use environment variable or default to 'db'
$db_name = getenv('DB_NAME') ?: 'cervelingua';
$db_user = getenv('DB_USER') ?: 'postgres';
$db_password = getenv('DB_PASSWORD') ?: 'macintosh';
//$chatgpt_api_key = getenv('CHATGPT_API_KEY')?: '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';

// Define the OpenAI API key as a constant for easier access
// Add this line to your config.php file if it's not already there
define('OPENAI_API_KEY', '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'); // Replace with your actual API key

// PDO connection
try {
    $pdo = new PDO(
        "pgsql:host=$db_host;dbname=$db_name", 
        $db_user, 
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error but don't expose details to users
    error_log("Database connection error: " . $e->getMessage());
    // Für Debugging - in der Produktion entfernen
    echo "Verbindungsfehler: " . $e->getMessage();
}

// Site configuration
$site_name = "CerveLingua";
$site_url = "http://localhost:8080"; // Update based on your environment

// Define APP_URL constant if not already defined
if (!defined('APP_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    define('APP_URL', $protocol . '://' . $host);
}

// Error reporting - set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production
?>