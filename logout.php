<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once "includes/config.php";
require_once "includes/functions.php";

// Store user ID before clearing session (for database cleanup if needed)
$user_id = $_SESSION['user_id'] ?? null;

// Clear all session variables
$_SESSION = array();
session_unset();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, '/');
}

// Clear any active sessions from the database for this user
if ($user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NULL WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error updating user session: " . $e->getMessage());
    }
}

// Destroy the session
session_destroy();

// Force a new session to start
session_start();
session_regenerate_id(true);
session_destroy();

// Clear any remaining cookies
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/');
}

// Redirect with a cache-control header
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Location: login.php");
exit();
?>