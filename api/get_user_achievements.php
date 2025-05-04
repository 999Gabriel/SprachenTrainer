<?php
// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Turn off HTML error display for API
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Initialize session
session_start();

// Set content type
header('Content-Type: application/json');

// Return static achievements data for now
$achievements = [
    [
        'id' => 1,
        'name' => 'Administrator',
        'description' => 'You have administrator privileges',
        'icon' => 'fas fa-crown',
        'date_earned' => date('Y-m-d H:i:s', strtotime('-10 days'))
    ],
    [
        'id' => 2,
        'name' => 'Power User',
        'description' => 'You have used all features of the application',
        'icon' => 'fas fa-bolt',
        'date_earned' => date('Y-m-d H:i:s', strtotime('-5 days'))
    ],
    [
        'id' => 3,
        'name' => 'Content Creator',
        'description' => 'You have created new learning content',
        'icon' => 'fas fa-pencil-alt',
        'date_earned' => date('Y-m-d H:i:s', strtotime('-2 days'))
    ]
];

// Output the JSON data
echo json_encode($achievements);
?>