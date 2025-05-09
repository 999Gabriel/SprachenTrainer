<?php
// Prevent any output before headers
ob_start();

// Enable error reporting for debugging but don't display errors to user
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Include configuration and start session
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Set content type to JSON
header('Content-Type: application/json');

// --- Helper Functions ---

/**
 * Safely return JSON response and exit script.
 * @param array $data Data to encode as JSON.
 */
function return_json(array $data): void {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    echo json_encode($data);
    exit;
}

// --- Main Logic ---

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    return_json(['success' => false, 'error' => 'Not authenticated']);
}
$user_id = (int) $_SESSION['user_id'];

// Check if required parameters are provided
if (!isset($_POST['word']) || !isset($_POST['action'])) {
    return_json(['success' => false, 'error' => 'Missing parameters']);
}
$word = trim($_POST['word']);
$action = $_POST['action'];

// Validate word
if (empty($word)) {
    return_json(['success' => false, 'error' => 'Invalid word']);
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // 1. Find or create word in vocabulary table
    $stmt = $pdo->prepare("SELECT word_id FROM vocabulary WHERE spanish_word = :word LIMIT 1");
    $stmt->bindParam(':word', $word, PDO::PARAM_STR);
    $stmt->execute();
    $word_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($word_data) {
        // Word exists
        $word_id = (int) $word_data['word_id'];
    } else {
        // Word doesn't exist, insert it
        $insertStmt = $pdo->prepare("
            INSERT INTO vocabulary (spanish_word, english_translation, created_at) 
            VALUES (:word, '', NOW()) 
            RETURNING word_id
        ");
        $insertStmt->bindParam(':word', $word, PDO::PARAM_STR);
        $insertStmt->execute();
        $word_id = (int) $insertStmt->fetchColumn();
    }

    // 2. Update or insert user_vocabulary progress
    $xp_earned = 0;

    // Check existing progress
    $progressStmt = $pdo->prepare("
        SELECT user_vocab_id, times_reviewed 
        FROM user_vocabulary 
        WHERE user_id = :user_id AND word_id = :word_id
    ");
    $progressStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $progressStmt->bindParam(':word_id', $word_id, PDO::PARAM_INT);
    $progressStmt->execute();
    $existingProgress = $progressStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingProgress) {
        // First time practice - 10 XP
        $xp_earned = 10;
        
        // Fixed: Removed duplicate last_reviewed column
        $insertUserVocabStmt = $pdo->prepare("
            INSERT INTO user_vocabulary 
            (user_id, word_id, times_reviewed, last_reviewed, mastery_level) 
            VALUES (:user_id, :word_id, 1, NOW(), 1)
        ");
        $insertUserVocabStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $insertUserVocabStmt->bindParam(':word_id', $word_id, PDO::PARAM_INT);
        $insertUserVocabStmt->execute();
    } else {
        // Subsequent practice - 5 XP
        $xp_earned = 5;
        $updateUserVocabStmt = $pdo->prepare("
            UPDATE user_vocabulary 
            SET times_reviewed = times_reviewed + 1, 
                last_reviewed = NOW(),
                mastery_level = LEAST(mastery_level + 1, 5)
            WHERE user_vocab_id = :pk_id
        ");
        $updateUserVocabStmt->bindParam(':pk_id', $existingProgress['user_vocab_id'], PDO::PARAM_INT);
        $updateUserVocabStmt->execute();
    }

    // 3. Update user_progress XP
    $userProgressStmt = $pdo->prepare("
        SELECT progress_id 
        FROM user_progress 
        WHERE user_id = :user_id
    ");
    $userProgressStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $userProgressStmt->execute();
    $userProgressExists = $userProgressStmt->fetchColumn();

    if ($userProgressExists !== false) {
        // User exists, update XP
        $updateXpStmt = $pdo->prepare("
            UPDATE user_progress 
            SET xp_total = xp_total + :xp, 
                last_activity_date = CURRENT_DATE 
            WHERE user_id = :user_id
        ");
        $updateXpStmt->bindParam(':xp', $xp_earned, PDO::PARAM_INT);
        $updateXpStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $updateXpStmt->execute();
    } else {
        // User doesn't exist, insert new progress record
        $insertXpStmt = $pdo->prepare("
            INSERT INTO user_progress 
            (user_id, xp_total, streak_days, total_study_time, last_activity_date, current_level_id) 
            VALUES (:user_id, :xp, 1, 0, CURRENT_DATE, 1)
        ");
        $insertXpStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $insertXpStmt->bindParam(':xp', $xp_earned, PDO::PARAM_INT);
        $insertXpStmt->execute();
    }

    // Commit transaction
    $pdo->commit();

    // Return success response
    return_json([
        'success' => true,
        'message' => 'Word progress saved successfully',
        'xp' => $xp_earned
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in save-word-progress.php: ' . $e->getMessage());
    return_json([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Exception in save-word-progress.php: ' . $e->getMessage());
    return_json([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>