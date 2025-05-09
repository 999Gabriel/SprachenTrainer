<?php
// Include configuration
require_once "../includes/config.php";

// Set content type to plain text for debugging
header('Content-Type: text/plain');

try {
    // Check if mastery_level column exists
    $check_column = $pdo->prepare("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'user_vocabulary' AND column_name = 'mastery_level'
    ");
    $check_column->execute();
    $mastery_level_exists = $check_column->fetchColumn();
    
    if (!$mastery_level_exists) {
        // Add mastery_level column to user_vocabulary table
        $pdo->exec("
            ALTER TABLE user_vocabulary 
            ADD COLUMN mastery_level INTEGER DEFAULT 1 NOT NULL
        ");
        echo "Successfully added mastery_level column to user_vocabulary table.\n";
    } else {
        echo "mastery_level column already exists in user_vocabulary table.\n";
    }
    
    echo "Database schema update completed successfully.";
    
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage();
}
?>