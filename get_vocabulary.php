<?php
require_once "includes/config.php";
require_once "includes/functions.php";

// Optional: Check if user is logged in if vocabulary access is restricted
// session_start();
// if(!isset($_SESSION['user_id'])) { ... }

header('Content-Type: application/json');

// --- Add logic here to handle potential filters (e.g., category, difficulty) ---
// Example: $category = isset($_GET['category']) ? $_GET['category'] : null;

try {
    // Base query
    $sql = "SELECT word_id, spanish_word, english_translation, difficulty_level, category, image_url, audio_url, example_sentence FROM vocabulary";
    $params = [];

    // --- Add WHERE clauses based on filters ---
    // if ($category) {
    //     $sql .= " WHERE category = :category";
    //     $params[':category'] = $category;
    // }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vocabulary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'vocabulary' => $vocabulary]);

} catch (PDOException $e) {
    error_log("Get Vocabulary error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error fetching vocabulary']);
}
?>