<?php
// Include configuration
require_once "includes/config.php";

// Get all tables
$stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h1>Database Tables</h1>";

// Display each table and its data
foreach ($tables as $table) {
    echo "<h2>Table: $table</h2>";
    
    // Get table structure
    $stmt = $pdo->query("SELECT column_name, data_type, character_maximum_length 
                         FROM information_schema.columns 
                         WHERE table_name = '$table'
                         ORDER BY ordinal_position");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Max Length</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['column_name']}</td>";
        echo "<td>{$column['data_type']}</td>";
        echo "<td>{$column['character_maximum_length']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Get table data
    $stmt = $pdo->query("SELECT * FROM $table LIMIT 100");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        echo "<h3>Data (up to 100 rows):</h3>";
        echo "<table border='1'>";
        
        // Table header
        echo "<tr>";
        foreach (array_keys($rows[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";
        
        // Table data
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No data in this table.</p>";
    }
    
    echo "<hr>";
}
?>