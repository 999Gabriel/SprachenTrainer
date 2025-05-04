<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once "includes/config.php";
require_once "includes/functions.php";

// Clear all session variables
$_SESSION = array();

// Delete the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Delete remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    // Remove token from database if the table exists
    try {
        // Check if the remember_tokens table exists
        $table_check = $pdo->query("SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'remember_tokens'
        )");
        
        $table_exists = $table_check->fetchColumn();
        
        if ($table_exists && isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        // Log error but continue with logout
        error_log("Error removing remember token: " . $e->getMessage());
    }
    
    // Delete the cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>