<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both username and password.";
        header("Location: login.php");
        exit;
    }
    
    try {
        // Find user by username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user';
            
            // Update last login time
            $update_stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id");
            $update_stmt->bindParam(':user_id', $user['user_id']);
            $update_stmt->execute();
            
            // Set remember me cookie if requested
            if ($remember) {
                // Generate a unique token
                $token = generate_token();
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Check if remember_tokens table exists, create if not
                $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
                    token_id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                )");
                
                // Store token in database
                $token_stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
                $token_stmt->bindParam(':user_id', $user['user_id']);
                $token_stmt->bindParam(':token', $token);
                $token_stmt->bindParam(':expires_at', $expires);
                $token_stmt->execute();
                
                // Set cookie
                setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
            }
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            // Invalid credentials
            $_SESSION['login_error'] = "Invalid username or password.";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['login_error'] = "An error occurred during login. Please try again.";
        header("Location: login.php");
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: login.php");
    exit;
}
?>