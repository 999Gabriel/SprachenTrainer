<?php
// Include functions file
require_once "functions.php";

/**
 * Register a new user
 * @param string $username - Username
 * @param string $email - Email address
 * @param string $password - Password (plain text)
 * @param string $first_name - First name (optional)
 * @param string $last_name - Last name (optional)
 * @return array - Status and message
 */
function register_user($username, $email, $password, $first_name = "", $last_name = "") {
    global $pdo;
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(":username", $username, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return ["status" => "error", "message" => "Username already exists"];
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return ["status" => "error", "message" => "Email already exists"];
    }
    
    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (:username, :email, :password_hash, :first_name, :last_name)");
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":password_hash", $password_hash, PDO::PARAM_STR);
        $stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
        $stmt->bindParam(":last_name", $last_name, PDO::PARAM_STR);
        $stmt->execute();
        
        // Get the user ID
        $user_id = $pdo->lastInsertId();
        
        // Create user progress entry
        $stmt = $pdo->prepare("INSERT INTO user_progress (user_id) VALUES (:user_id)");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        return ["status" => "success", "message" => "Registration successful", "user_id" => $user_id];
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        return ["status" => "error", "message" => "Registration failed: " . $e->getMessage()];
    }
}

/**
 * Login a user
 * @param string $email - Email address
 * @param string $password - Password (plain text)
 * @param boolean $remember - Remember login
 * @return array - Status and message
 */
function login_user($email, $password, $remember = false) {
    global $pdo;
    
    // Get user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        return ["status" => "error", "message" => "Invalid email or password"];
    }
    
    $user = $stmt->fetch();
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        return ["status" => "error", "message" => "Invalid email or password"];
    }
    
    // Check if account is active
    if (!$user['is_active']) {
        return ["status" => "error", "message" => "Account is inactive"];
    }
    
    // Update last login time
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $user['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = generate_token();
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database
        $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
        $stmt->bindParam(":user_id", $user['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        $stmt->bindParam(":expires_at", date('Y-m-d H:i:s', $expiry), PDO::PARAM_STR);
        $stmt->execute();
        
        // Set cookie
        setcookie("remember_token", $token, $expiry, "/", "", false, true);
    }
    
    // Log activity
    log_activity($user['user_id'], 'login');
    
    return ["status" => "success", "message" => "Login successful", "user" => $user];
}

/**
 * Logout a user
 */
function logout_user() {
    // Clear session
    session_unset();
    session_destroy();
    
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        // Delete token from database
        global $pdo;
        $token = $_COOKIE['remember_token'];
        
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = :token");
        $stmt->bindParam(":token", $token, PDO::PARAM_STR);
        $stmt->execute();
        
        // Delete cookie
        setcookie("remember_token", "", time() - 3600, "/", "", false, true);
    }
    
    // Redirect to login page
    redirect(APP_URL . "/login.php");
}

/**
 * Reset user password
 * @param string $email - User email
 * @return array - Status and message
 */
function reset_password($email) {
    global $pdo;
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        return ["status" => "error", "message" => "Email not found"];
    }
    
    $user = $stmt->fetch();
    
    // Generate reset token
    $token = generate_token();
    $expiry = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
    
    // Store token in database
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
    $stmt->bindParam(":user_id", $user['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(":token", $token, PDO::PARAM_STR);
    $stmt->bindParam(":expires_at", $expiry, PDO::PARAM_STR);
    $stmt->execute();
    
    // In a real application, you would send an email with the reset link
    // For now, we'll just return the token
    return [
        "status" => "success", 
        "message" => "Password reset link sent", 
        "token" => $token, 
        "reset_link" => APP_URL . "/reset-password.php?token=" . $token
    ];
}

/**
 * Check if user is authenticated, redirect if not
 */
function require_login() {
    if (!is_logged_in()) {
        redirect(APP_URL . "/login.php");
    }
}
?>