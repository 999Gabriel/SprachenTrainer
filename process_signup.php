<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    
    // Debug: Log form submission
    error_log("Form submitted: Username=$username, Email=$email");
    
    // Validate inputs
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    } elseif (username_exists($username)) {
        $errors[] = "Username already exists";
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif (email_exists($email)) {
        $errors[] = "Email already exists";
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If there are errors, redirect back to signup page
    if (!empty($errors)) {
        $_SESSION['signup_error'] = implode("<br>", $errors);
        $_SESSION['signup_data'] = [
            'username' => $username,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name
        ];
        header("Location: signup.php");
        exit;
    }
    
    try {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Debug: Log the data we're about to insert
        error_log("Attempting to insert new user: $username, $email");
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Insert user - using the exact same approach that worked in test_insert.php
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, 
                email, 
                password_hash, 
                first_name, 
                last_name, 
                created_at, 
                is_active, 
                is_verified
            ) VALUES (
                :username, 
                :email, 
                :password_hash, 
                :first_name, 
                :last_name, 
                NOW(), 
                TRUE, 
                TRUE
            ) RETURNING user_id
        ");
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("SQL Error: " . print_r($stmt->errorInfo(), true));
            throw new PDOException("Failed to insert user: " . implode(", ", $stmt->errorInfo()));
        }
        
        // Get the new user ID using RETURNING clause in PostgreSQL
        $user_data = $stmt->fetch();
        $user_id = $user_data['user_id'] ?? null;
        
        if (!$user_id) {
            error_log("Warning: Could not get user_id from RETURNING clause");
            
            // Try to get the user_id directly
            $id_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
            $id_stmt->bindParam(':email', $email);
            $id_stmt->execute();
            $user_id = $id_stmt->fetchColumn();
            
            if (!$user_id) {
                error_log("Failed to retrieve user_id after insert");
                throw new PDOException("Could not determine user_id after insert");
            }
        }
        
        error_log("User ID retrieved: $user_id");
        
        // Commit transaction
        $pdo->commit();
        
        // Debug: Log success
        error_log("User successfully created with ID: $user_id");
        
        // Set success message
        $_SESSION['login_success'] = "Account created successfully! You can now log in.";
        
        // Redirect to login page
        header("Location: login.php");
        exit;
        
    } catch (PDOException $e) {
        // Rollback transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log detailed error
        error_log("Registration error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['signup_error'] = "Database error: " . $e->getMessage();
        
        // Redirect back to signup page
        header("Location: signup.php");
        exit;
    }
} else {
    // If not a POST request, redirect to signup page
    header("Location: signup.php");
    exit;
}
?>