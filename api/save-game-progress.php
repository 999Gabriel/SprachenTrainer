<?php
// Include configuration
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Get JSON data from request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate required fields
if (!isset($data['game_id']) || !isset($data['user_id']) || !isset($data['score'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Extract data
$game_id = intval($data['game_id']);
$user_id = intval($data['user_id']);
$score = intval($data['score']);
$game_data = isset($data['data']) ? json_encode($data['data']) : null;

// Verify user ID matches session
if ($user_id !== intval($_SESSION['user_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'User ID mismatch']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Record game progress
    $stmt = $pdo->prepare("
        INSERT INTO user_game_progress 
        (user_id, game_id, score, game_data, played_at) 
        VALUES (:user_id, :game_id, :score, :game_data, NOW())
    ");
    
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':game_id', $game_id, PDO::PARAM_INT);
    $stmt->bindParam(':score', $score, PDO::PARAM_INT);
    $stmt->bindParam(':game_data', $game_data, PDO::PARAM_STR);
    $stmt->execute();
    
    // Update user XP if provided in game data
    if (isset($data['data']['xp_gained']) && $data['data']['xp_gained'] > 0) {
        $xp_gained = intval($data['data']['xp_gained']);
        
        // Update user_progress table
        $progress_stmt = $pdo->prepare("
            UPDATE user_progress 
            SET xp_points = xp_points + :xp_gained,
                last_activity_date = CURRENT_DATE
            WHERE user_id = :user_id
        ");
        
        $progress_stmt->bindParam(':xp_gained', $xp_gained, PDO::PARAM_INT);
        $progress_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $progress_stmt->execute();
        
        // Check if user should level up based on XP
        $level_check_stmt = $pdo->prepare("
            SELECT up.xp_points, up.current_level_id, pl.xp_threshold, pl.level_id
            FROM user_progress up
            JOIN proficiency_levels pl ON up.current_level_id = pl.level_id
            LEFT JOIN proficiency_levels next_level ON pl.level_id + 1 = next_level.level_id
            WHERE up.user_id = :user_id AND next_level.xp_threshold IS NOT NULL
        ");
        
        $level_check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $level_check_stmt->execute();
        $level_data = $level_check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($level_data && $level_data['xp_points'] >= $level_data['xp_threshold']) {
            // Level up the user
            $level_up_stmt = $pdo->prepare("
                UPDATE user_progress
                SET current_level_id = current_level_id + 1
                WHERE user_id = :user_id
            ");
            
            $level_up_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $level_up_stmt->execute();
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Game progress saved successfully',
        'game_id' => $game_id,
        'score' => $score,
        'xp_gained' => $data['data']['xp_gained'] ?? 0
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Log error
    error_log("Error saving game progress: " . $e->getMessage());
    
    // Return error response
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}