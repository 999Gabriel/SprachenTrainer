<?php
// Include configuration
require_once "includes/config.php";

// Set content type to plain text for easier reading
header('Content-Type: text/plain');

echo "Database Insert Test\n";
echo "===================\n\n";

try {
    // Test user data
    $username = "testuser" . rand(1000, 9999);
    $email = "test" . rand(1000, 9999) . "@example.com";
    $password_hash = password_hash("password123", PASSWORD_DEFAULT);
    
    echo "Attempting to insert test user:\n";
    echo "Username: $username\n";
    echo "Email: $email\n\n";
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert user
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
            'Test', 
            'User', 
            NOW(), 
            TRUE, 
            TRUE
        ) RETURNING user_id
    ");
    
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password_hash);
    
    $result = $stmt->execute();
    
    if ($result) {
        $user_data = $stmt->fetch();
        $user_id = $user_data['user_id'] ?? null;
        
        if ($user_id) {
            echo "✓ User inserted successfully with ID: $user_id\n";
        } else {
            echo "✓ User inserted but couldn't retrieve ID using RETURNING clause\n";
            
            // Try to get the user_id directly
            $id_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
            $id_stmt->bindParam(':email', $email);
            $id_stmt->execute();
            $user_id = $id_stmt->fetchColumn();
            
            if ($user_id) {
                echo "✓ Retrieved user ID using separate query: $user_id\n";
            } else {
                echo "✗ Could not retrieve user ID\n";
            }
        }
    } else {
        echo "✗ Insert failed: " . implode(", ", $stmt->errorInfo()) . "\n";
    }
    
    // Commit transaction
    $pdo->commit();
    
    // List all users
    echo "\nCurrent users in database:\n";
    $users = $pdo->query("SELECT user_id, username, email FROM users ORDER BY user_id")->fetchAll();
    
    if (count($users) > 0) {
        foreach ($users as $user) {
            echo "- ID: {$user['user_id']}, Username: {$user['username']}, Email: {$user['email']}\n";
        }
    } else {
        echo "No users found in database.\n";
    }
    
} catch (PDOException $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nTest completed at " . date('Y-m-d H:i:s') . "\n";
?>