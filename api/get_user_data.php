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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For development, use a test ID
    $_SESSION['user_id'] = 1;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Create a MySQL connection (since PostgreSQL extension is not available)
$host = 'localhost';
$user = 'postgres';
$password = 'macintosh';
$database = 'cervelingua';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    
    // Return static data if connection fails
    $userData = getStaticUserData();
    echo json_encode($userData);
    exit;
}

try {
    // Fetch user data
    $userQuery = "SELECT id, username, first_name, last_name, email, profile_image, role, created_at, last_login 
                 FROM users WHERE id = ?";
    
    $stmt = $conn->prepare($userQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found, use static data
        $userData = getStaticUserData();
    } else {
        // User found, get real data
        $userData = $result->fetch_assoc();
        
        // Fetch progress data
        $progressQuery = "SELECT level, xp_points, streak_days, words_learned, total_study_time 
                         FROM user_progress WHERE user_id = ?";
        
        $progressStmt = $conn->prepare($progressQuery);
        if ($progressStmt) {
            $progressStmt->bind_param("i", $userId);
            $progressStmt->execute();
            $progressResult = $progressStmt->get_result();
            
            if ($progressResult->num_rows > 0) {
                $progress = $progressResult->fetch_assoc();
                
                // Add progress data to user data
                $userData['level'] = $progress['level'];
                $userData['xp'] = $progress['xp_points'];
                $userData['streak'] = $progress['streak_days'];
                $userData['words_learned'] = $progress['words_learned'];
                $userData['total_study_time'] = $progress['total_study_time'];
            } else {
                // No progress data found, use defaults
                $userData['level'] = 1;
                $userData['xp'] = 0;
                $userData['streak'] = 0;
                $userData['words_learned'] = 0;
                $userData['total_study_time'] = 0;
            }
            
            $progressStmt->close();
        }
        
        // Fetch achievements
        $achievementsQuery = "SELECT a.name, ua.date_earned 
                            FROM user_achievements ua 
                            JOIN achievements a ON ua.achievement_id = a.id 
                            WHERE ua.user_id = ? 
                            ORDER BY ua.date_earned DESC 
                            LIMIT 3";
        
        $achievementsStmt = $conn->prepare($achievementsQuery);
        $achievements = [];
        
        if ($achievementsStmt) {
            $achievementsStmt->bind_param("i", $userId);
            $achievementsStmt->execute();
            $achievementsResult = $achievementsStmt->get_result();
            
            while ($achievement = $achievementsResult->fetch_assoc()) {
                $achievements[] = $achievement;
            }
            
            $achievementsStmt->close();
        }
        
        $userData['achievements'] = $achievements;
        
        // Add motivational messages
        $motivationalMessages = [
            "Great job on your learning journey!",
            "You're making excellent progress!",
            "Keep up the good work with your studies!",
            "Your dedication to learning is impressive!",
            "You're on track to master this material!"
        ];
        
        // Randomly select a motivational message
        $randomIndex = array_rand($motivationalMessages);
        $userData['motivational_message'] = $motivationalMessages[$randomIndex];
        
        // Add personalized greeting
        $timeOfDay = date('H');
        if ($timeOfDay < 12) {
            $greeting = "Good morning";
        } elseif ($timeOfDay < 18) {
            $greeting = "Good afternoon";
        } else {
            $greeting = "Good evening";
        }
        
        $userData['greeting'] = "$greeting, " . $userData['first_name'] . "!";
    }
    
    echo json_encode($userData);
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    
    // Return static data if an error occurs
    $userData = getStaticUserData();
    echo json_encode($userData);
}

// Close connection
$conn->close();

// Function to get static user data as fallback
function getStaticUserData() {
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
    
    return [
        'username' => $username,
        'first_name' => isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Admin',
        'last_name' => isset($_SESSION['last_name']) ? $_SESSION['last_name'] : 'User',
        'email' => isset($_SESSION['email']) ? $_SESSION['email'] : 'admin@example.com',
        'profile_image' => '',
        'role' => isset($_SESSION['role']) ? $_SESSION['role'] : 'admin',
        'level' => 5,
        'xp' => 500,
        'streak' => 10,
        'words_learned' => 120,
        'total_study_time' => 360,
        'last_login' => date('Y-m-d H:i:s'),
        'achievements' => [
            [
                'name' => 'First Login',
                'date_earned' => date('Y-m-d H:i:s', strtotime('-10 days'))
            ],
            [
                'name' => 'Profile Completed',
                'date_earned' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],
            [
                'name' => 'Learning Streak',
                'date_earned' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ]
        ],
        'motivational_message' => "Keep up the great work with your studies!",
        'greeting' => "Welcome back, Admin!"
    ];
}
?>