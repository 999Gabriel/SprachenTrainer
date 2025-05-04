<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = "Invalid email format";
        header("Location: login.php");
        exit;
    }
    
    try {
        // Debug: Test database connection
        $test_stmt = $pdo->query("SELECT 1");
        if (!$test_stmt) {
            throw new PDOException("Database connection test failed");
        }
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        // Debug: Log the query and result
        error_log("Login attempt for email: " . $email . ", found rows: " . $stmt->rowCount());
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verify password - using password_hash column instead of password
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct, start a new session
                session_regenerate_id();
                
                // Store user data in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'user'; // Default to 'user' if role column doesn't exist
                
                // Update last login time
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id");
                $update_stmt->bindParam(':user_id', $user['user_id']);
                $update_stmt->execute();
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    // Store token in database - using remember_tokens table
                    $token_stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires)");
                    $token_stmt->bindParam(':user_id', $user['user_id']);
                    $token_stmt->bindParam(':token', $token);
                    $token_stmt->bindParam(':expires', date('Y-m-d H:i:s', $expires));
                    $token_stmt->execute();
                    
                    // Set cookie
                    setcookie('remember_token', $token, $expires, '/', '', false, true);
                }
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                // Password is incorrect
                $_SESSION['login_error'] = "Invalid email or password";
                header("Location: login.php");
                exit;
            }
        } else {
            // User does not exist
            $_SESSION['login_error'] = "Invalid email or password";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        // Enhanced error logging
        error_log("Database error in login process: " . $e->getMessage());
        $_SESSION['login_error'] = "Database error: " . $e->getMessage();
        header("Location: login.php");
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: login.php");
    exit;
}
?>