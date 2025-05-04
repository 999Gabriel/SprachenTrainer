<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email from form
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reset_error'] = "Invalid email format";
        header("Location: forgot-password.php");
        exit;
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            $user_id = $user['user_id'];
            
            // Generate token
            $token = generate_token();
            $expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
            
            // Store token in database
            $token_stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (:user_id, :token, :expires, CURRENT_TIMESTAMP)");
            $token_stmt->bindParam(':user_id', $user_id);
            $token_stmt->bindParam(':token', $token);
            $token_stmt->bindParam(':expires', $expires);
            $token_stmt->execute();
            
            // In a real application, send email with reset link
            // For demo purposes, just show the link
            $reset_link = APP_URL . "/reset-password.php?token=" . $token;
            
            $_SESSION['reset_success'] = "Password reset link has been sent to your email.";
            $_SESSION['reset_link'] = $reset_link; // For demo only
            
            header("Location: forgot-password.php");
            exit;
        } else {
            // User does not exist, but don't reveal this for security
            $_SESSION['reset_success'] = "If your email exists in our system, a password reset link has been sent.";
            header("Location: forgot-password.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['reset_error'] = "Database error: " . $e->getMessage();
        header("Location: forgot-password.php");
        exit;
    }
} else {
    // If not a POST request, redirect to forgot password page
    header("Location: forgot-password.php");
    exit;
}
?>