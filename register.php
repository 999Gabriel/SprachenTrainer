<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once "includes/config.php";
require_once "includes/functions.php";

// Set content type to JSON for API response
header('Content-Type: application/json');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize and validate input
$username = trim($data['username']);
$email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
$password = $data['password'];

// Additional validation
if (empty($username) || strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters long']);
    exit;
}

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit;
}

try {
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (:username, :email, :password_hash, CURRENT_TIMESTAMP)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->execute();
    
    // Get the new user ID
    $user_id = $pdo->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful! You can now log in.',
        'user_id' => $user_id
    ]);
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error during registration']);
}
?>