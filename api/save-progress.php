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

/**
 * Updates user XP and streak in user_progress table
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param int $xp_earned XP points earned
 * @param int $study_time_seconds Study time in seconds to add
 * @return bool Success status
 */
function update_user_progress(PDO $pdo, int $user_id, int $xp_earned = 0, int $study_time_seconds = 0): bool {
    try {
        // Check if user exists in user_progress
        $stmt = $pdo->prepare("SELECT progress_id FROM user_progress WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $progress_exists = $stmt->fetchColumn();
        
        // Get current date
        $today = new DateTime('today');
        $today_str = $today->format('Y-m-d');
        
        if ($progress_exists) {
            // Get last activity date to check streak
            $date_stmt = $pdo->prepare("SELECT last_activity_date, streak_days FROM user_progress WHERE user_id = :user_id");
            $date_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $date_stmt->execute();
            $progress_data = $date_stmt->fetch(PDO::FETCH_ASSOC);
            
            $last_activity = new DateTime($progress_data['last_activity_date']);
            $diff = $today->diff($last_activity)->days;
            $streak_days = $progress_data['streak_days'];
            
            // Update streak based on difference
            if ($diff == 0) {
                // Already logged in today, no streak change
            } else if ($diff == 1) {
                // Consecutive day, increase streak
                $streak_days++;
            } else {
                // Streak broken, reset to 1
                $streak_days = 1;
            }
            
            // Update user progress
            $update_stmt = $pdo->prepare("
                UPDATE user_progress 
                SET xp_total = xp_total + :xp,
                    streak_days = :streak_days,
                    total_study_time = total_study_time + :study_time,
                    last_activity_date = :today
                WHERE user_id = :user_id
            ");
            $update_stmt->bindParam(':xp', $xp_earned, PDO::PARAM_INT);
            $update_stmt->bindParam(':streak_days', $streak_days, PDO::PARAM_INT);
            $update_stmt->bindParam(':study_time', $study_time_seconds, PDO::PARAM_INT);
            $update_stmt->bindParam(':today', $today_str, PDO::PARAM_STR);
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } else {
            // Create new progress record
            $insert_stmt = $pdo->prepare("
                INSERT INTO user_progress 
                (user_id, current_level_id, xp_total, streak_days, total_study_time, last_activity_date) 
                VALUES (:user_id, 1, :xp, 1, :study_time, :today)
            ");
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':xp', $xp_earned, PDO::PARAM_INT);
            $insert_stmt->bindParam(':study_time', $study_time_seconds, PDO::PARAM_INT);
            $insert_stmt->bindParam(':today', $today_str, PDO::PARAM_STR);
            $insert_stmt->execute();
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating user progress: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates lesson progress for a user
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param int $lesson_id Lesson ID
 * @param int $progress_percentage Progress percentage (0-100)
 * @return bool Success status
 */
function update_lesson_progress(PDO $pdo, int $user_id, int $lesson_id, int $progress_percentage): bool {
    try {
        // Check if lesson progress exists
        $stmt = $pdo->prepare("
            SELECT progress_id 
            FROM user_lesson_progress 
            WHERE user_id = :user_id AND lesson_id = :lesson_id
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
        $stmt->execute();
        $progress_exists = $stmt->fetchColumn();
        
        // Ensure progress is between 0-100
        $progress_percentage = max(0, min(100, $progress_percentage));
        
        if ($progress_exists) {
            // Update existing progress
            $update_stmt = $pdo->prepare("
                UPDATE user_lesson_progress 
                SET progress_percentage = :progress,
                    last_updated = NOW()
                WHERE user_id = :user_id AND lesson_id = :lesson_id
            ");
            $update_stmt->bindParam(':progress', $progress_percentage, PDO::PARAM_INT);
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } else {
            // Create new progress record
            $insert_stmt = $pdo->prepare("
                INSERT INTO user_lesson_progress 
                (user_id, lesson_id, progress_percentage, last_updated) 
                VALUES (:user_id, :lesson_id, :progress, NOW())
            ");
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':progress', $progress_percentage, PDO::PARAM_INT);
            $insert_stmt->execute();
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating lesson progress: " . $e->getMessage());
        return false;
    }
}

// --- Main Logic ---

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    return_json(['success' => false, 'error' => 'Not authenticated']);
}
$user_id = (int) $_SESSION['user_id'];

// Check if required parameters are provided
if (!isset($_POST['progress_type'])) {
    return_json(['success' => false, 'error' => 'Missing progress_type parameter']);
}

$progress_type = $_POST['progress_type'];
$xp_earned = isset($_POST['xp_earned']) ? (int) $_POST['xp_earned'] : 0;
$study_time = isset($_POST['study_time']) ? (int) $_POST['study_time'] : 0;

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Always update user progress (XP, streak, study time)
    $user_progress_updated = update_user_progress($pdo, $user_id, $xp_earned, $study_time);
    
    if (!$user_progress_updated) {
        throw new Exception("Failed to update user progress");
    }
    
    // Handle different progress types
    switch ($progress_type) {
        case 'lesson':
            // Lesson progress
            if (!isset($_POST['lesson_id']) || !isset($_POST['progress_percentage'])) {
                throw new Exception("Missing lesson_id or progress_percentage parameters");
            }
            
            $lesson_id = (int) $_POST['lesson_id'];
            $progress_percentage = (int) $_POST['progress_percentage'];
            
            $lesson_updated = update_lesson_progress($pdo, $user_id, $lesson_id, $progress_percentage);
            
            if (!$lesson_updated) {
                throw new Exception("Failed to update lesson progress");
            }
            break;
            
        case 'vocabulary':
            // Vocabulary progress is handled by save-word-progress.php
            // This is just a fallback
            if (!isset($_POST['word_id'])) {
                throw new Exception("Missing word_id parameter");
            }
            
            $word_id = (int) $_POST['word_id'];
            
            // Check if mastery_level column exists
            $check_column = $pdo->prepare("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = 'user_vocabulary' AND column_name = 'mastery_level'
            ");
            $check_column->execute();
            $mastery_level_exists = $check_column->fetchColumn();
            
            if ($mastery_level_exists) {
                // If mastery_level column exists, use it
                $mastery_level = isset($_POST['mastery_level']) ? (int) $_POST['mastery_level'] : 1;
                
                $vocab_stmt = $pdo->prepare("
                    INSERT INTO user_vocabulary 
                    (user_id, word_id, times_reviewed, last_reviewed, mastery_level) 
                    VALUES (:user_id, :word_id, 1, NOW(), :mastery_level)
                    ON CONFLICT (user_id, word_id) DO UPDATE SET
                        times_reviewed = user_vocabulary.times_reviewed + 1,
                        last_reviewed = NOW(),
                        mastery_level = :mastery_level
                ");
                $vocab_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $vocab_stmt->bindParam(':word_id', $word_id, PDO::PARAM_INT);
                $vocab_stmt->bindParam(':mastery_level', $mastery_level, PDO::PARAM_INT);
                $vocab_stmt->execute();
            } else {
                // If mastery_level column doesn't exist, use the original schema
                $vocab_stmt = $pdo->prepare("
                    INSERT INTO user_vocabulary 
                    (user_id, word_id, times_reviewed, last_reviewed) 
                    VALUES (:user_id, :word_id, 1, NOW())
                    ON CONFLICT (user_id, word_id) DO UPDATE SET
                        times_reviewed = user_vocabulary.times_reviewed + 1,
                        last_reviewed = NOW()
                ");
                $vocab_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $vocab_stmt->bindParam(':word_id', $word_id, PDO::PARAM_INT);
                $vocab_stmt->execute();
            }
            break;
            
        case 'exercise':
            // Exercise completion
            if (!isset($_POST['exercise_id'])) {
                throw new Exception("Missing exercise_id parameter");
            }
            
            $exercise_id = (int) $_POST['exercise_id'];
            $score = isset($_POST['score']) ? (int) $_POST['score'] : 0;
            
            // Update exercise progress
            $exercise_stmt = $pdo->prepare("
                INSERT INTO user_exercise_progress 
                (user_id, exercise_id, score, completed_at) 
                VALUES (:user_id, :exercise_id, :score, NOW())
                ON CONFLICT (user_id, exercise_id) DO UPDATE SET
                    score = GREATEST(user_exercise_progress.score, :score),
                    completed_at = NOW()
            ");
            $exercise_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $exercise_stmt->bindParam(':exercise_id', $exercise_id, PDO::PARAM_INT);
            $exercise_stmt->bindParam(':score', $score, PDO::PARAM_INT);
            $exercise_stmt->execute();
            break;
            
        case 'general':
            // Just update user progress (already done above)
            break;
            
        default:
            throw new Exception("Unknown progress_type: " . $progress_type);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    return_json([
        'success' => true,
        'message' => 'Progress saved successfully',
        'xp_earned' => $xp_earned
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in save-progress.php: ' . $e->getMessage());
    return_json([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Exception in save-progress.php: ' . $e->getMessage());
    return_json([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>