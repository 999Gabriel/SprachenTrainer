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
    // Fetch unlocked achievements for the user
    $stmt = $pdo->prepare("
        SELECT a.achievement_id, a.name, a.description, a.icon_url, a.xp_reward, ua.unlocked_at
        FROM achievements a
        JOIN user_achievements ua ON a.achievement_id = ua.achievement_id
        WHERE ua.user_id = :user_id
        ORDER BY ua.unlocked_at DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'achievements' => $achievements]);

} catch (PDOException $e) {
    error_log("Get User Achievements error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error fetching user achievements']);
}
?>