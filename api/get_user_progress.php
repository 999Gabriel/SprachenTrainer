<?php
// Prevent PHP from outputting HTML error messages for API calls
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON before any output
header('Content-Type: application/json');

// For testing purposes, allow access even if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use a test user ID for development
}

try {
    // For now, just return dummy data to ensure valid JSON
    echo json_encode([
        'level' => 3,
        'xp' => 250,
        'streak' => 5,
        'words_learned' => 42
    ]);
    
} catch (Exception $e) {
    // Log the error for server-side debugging
    error_log("API Error in get_user_progress.php: " . $e->getMessage());
    
    // Return a more detailed error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error', 
        'message' => $e->getMessage()
    ]);
}
?>