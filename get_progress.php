<?php
session_start();
require_once "includes/config.php";
require_once "includes/functions.php";

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user progress and join with levels table to get level name and next XP requirement
    $stmt = $pdo->prepare("
        SELECT
            up.*,
            l.name as current_level_name,
            l.xp_required as current_level_xp_required,
            nl.xp_required as next_level_xp_required
        FROM user_progress up
        JOIN levels l ON up.current_level = l.level_id
        LEFT JOIN levels nl ON l.next_level_id = nl.level_id
        WHERE up.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($progress) {
        // Calculate XP progress percentage
        $xp_progress_percentage = 0;
        if ($progress['current_level_xp_required'] > 0) {
             // Ensure we handle the case where current XP might exceed the requirement for the current level (e.g., after level up)
             $xp_in_current_level = $progress['xp_total'];
             // Find the XP requirement of the *previous* level to calculate progress within the current level more accurately if needed,
             // or simply cap the percentage at 100 if xp_total meets/exceeds current_level_xp_required.
             // For simplicity here, we'll just calculate based on the current level's requirement.
             $xp_progress_percentage = min(100, ($progress['xp_total'] / $progress['current_level_xp_required']) * 100);
        }
         $progress['xp_progress_percentage'] = round($xp_progress_percentage, 2);


        echo json_encode(['success' => true, 'progress' => $progress]);
    } else {
        // Handle case where user exists but has no progress record yet (should ideally be created on registration or first login)
        echo json_encode(['success' => false, 'message' => 'User progress not found']);
    }

} catch (PDOException $e) {
    error_log("Get Progress error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error fetching progress']);
}
?>