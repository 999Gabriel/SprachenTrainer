<?php
// Include configuration
require_once "includes/config.php";

// Set content type to plain text for easier reading
header('Content-Type: text/plain');

echo "Database Diagnostic Tool\n";
echo "=======================\n\n";

try {
    // Check connection
    echo "1. Testing database connection...\n";
    $test_stmt = $pdo->query("SELECT 1");
    if ($test_stmt) {
        echo "   ✓ Database connection successful\n\n";
    } else {
        echo "   ✗ Database connection test failed\n\n";
    }
    
    // Get database info
    echo "2. Database information:\n";
    echo "   Host: " . $db_host . "\n";
    echo "   Port: " . (isset($db_port) ? $db_port : '5432') . "\n";
    echo "   Database name: " . $db_name . "\n";
    echo "   Username: " . $db_user . "\n";
    echo "   Password: " . (empty($db_password) ? "(empty)" : "(set)") . "\n\n";
    
    // Check tables - PostgreSQL version
    echo "3. Checking database tables:\n";
    $tables_query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
    $tables = $pdo->query($tables_query)->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "   ✗ No tables found in the database\n\n";
    } else {
        echo "   ✓ Found " . count($tables) . " tables:\n";
        foreach ($tables as $table) {
            echo "     - " . $table . "\n";
        }
        echo "\n";
    }
    
    // Check users table specifically
    echo "4. Checking 'users' table:\n";
    if (in_array('users', $tables)) {
        echo "   ✓ Users table exists\n";
        
        // Check structure - PostgreSQL version
        echo "   Table structure:\n";
        $columns_query = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users' AND table_schema = 'public'";
        $columns = $pdo->query($columns_query)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "     - " . $column['column_name'] . " (" . $column['data_type'] . ")\n";
        }
        
        // Check user count
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "   Total users: " . $user_count . "\n\n";
        
        if ($user_count > 0) {
            echo "   Sample user data (first user, sensitive data masked):\n";
            $user = $pdo->query("SELECT * FROM users LIMIT 1")->fetch();
            foreach ($user as $key => $value) {
                if (in_array($key, ['password', 'email', 'password_hash'])) {
                    echo "     - " . $key . ": " . (is_null($value) ? "NULL" : substr($value, 0, 3) . "...") . "\n";
                } else {
                    echo "     - " . $key . ": " . (is_null($value) ? "NULL" : $value) . "\n";
                }
            }
        }
    } else {
        echo "   ✗ Users table does not exist\n\n";
        
        // Provide SQL to create users table - PostgreSQL version
        echo "   SQL to create users table:\n";
        echo "   CREATE TABLE users (\n";
        echo "     user_id SERIAL PRIMARY KEY,\n";
        echo "     username VARCHAR(50) NOT NULL UNIQUE,\n";
        echo "     email VARCHAR(100) NOT NULL UNIQUE,\n";
        echo "     password_hash VARCHAR(255) NOT NULL,\n";
        echo "     first_name VARCHAR(50),\n";
        echo "     last_name VARCHAR(50),\n";
        echo "     role VARCHAR(20) DEFAULT 'user',\n";
        echo "     profile_image VARCHAR(255),\n";
        echo "     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        echo "     last_login TIMESTAMP\n";
        echo "   );\n\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nDiagnostic completed at " . date('Y-m-d H:i:s') . "\n";
?>