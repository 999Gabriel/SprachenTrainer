<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $token = $_POST['token'];
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate form data
    $errors = [];
    
    // Validate password
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If there are errors, redirect back to reset password page
    if (!empty($errors)) {
        $_SESSION['reset_error'] = implode("<br>", $errors);
        header("Location: reset-password.php?token=" . $token);
        exit;
    }
    
    try {
        // Check if token exists and is valid
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND user_id = :user_id AND expires > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Token is valid, update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user password
            $update_stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
            $update_stmt->bindParam(':password', $hashed_password);
            $update_stmt->bindParam(':user_id', $user_id);
            $update_stmt->execute();
            
            // Delete all password reset tokens for this user
            $delete_stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :user_id");
            $delete_stmt->bindParam(':user_id', $user_id);
            $delete_stmt->execute();
            
            // Set success message
            $_SESSION['success_message'] = "Your password has been reset successfully. You can now log in with your new password.";
            
            // Redirect to login page
            header("Location: login.php");
            exit;
        } else {
            // Token is invalid
            $_SESSION['login_error'] = "Password reset link is invalid or has expired";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['reset_error'] = "Database error: " . $e->getMessage();
        header("Location: reset-password.php?token=" . $token);
        exit;
    }
} else {
    // If not a POST request, redirect to login page
    header("Location: login.php");
    exit;
}
?>