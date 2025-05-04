<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate data
if(!isset($data['xp_earned']) || !is_numeric($data['xp_earned']) || $data['xp_earned'] <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid XP value']);
    exit;
}

$xp_earned = (int)$data['xp_earned'];
$activity_type = isset($data['activity_type']) ? $data['activity_type'] : 'camera_learning';
$activity_details = isset($data['activity_details']) ? $data['activity_details'] : '';

// Check if we have vocabulary to save
$object_name = null;
$object_translation = null;

if (isset($data['object_name']) && isset($data['object_translation'])) {
    $object_name = $data['object_name'];
    $object_translation = $data['object_translation'];
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // 1. Get current user progress
    $stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$progress) {
        // Create new progress record if it doesn't exist
        // Using the correct column names from the actual database structure
        $stmt = $pdo->prepare("INSERT INTO user_progress (user_id, xp_total, current_level, streak_days, last_activity_date, minutes_learned, objects_recognized, words_learned) 
                              VALUES (:user_id, 0, 1, 0, NOW(), 0, 0, 0)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $current_xp = 0;
        $current_level_id = 1; // Default level ID
    } else {
        $current_xp = $progress['xp_total']; // Using 'xp_total' instead of 'xp'
        $current_level_id = $progress['current_level']; // Using 'current_level' from the database
    }
    
    // 2. Get level information
    $stmt = $pdo->prepare("SELECT * FROM levels WHERE level_id = :level_id");
    $stmt->bindParam(':level_id', $current_level_id);
    $stmt->execute();
    $current_level = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$current_level) {
        // Default level info if not found
        $current_level = [
            'level_id' => 1,
            'name' => 'Beginner',
            'xp_required' => 100,
            'next_level_id' => 2
        ];
    }
    
    // 3. Calculate new XP and check for level up
    $new_xp = $current_xp + $xp_earned;
    $leveled_up = false;
    $new_level = null;
    
    if($new_xp >= $current_level['xp_required'] && isset($current_level['next_level_id']) && $current_level['next_level_id'] !== null) {
        // Level up!
        $leveled_up = true;
        $new_level_id = $current_level['next_level_id'];
        
        // Get new level info
        $stmt = $pdo->prepare("SELECT * FROM levels WHERE level_id = :level_id");
        $stmt->bindParam(':level_id', $new_level_id);
        $stmt->execute();
        $new_level = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update user progress with new XP and level
        $stmt = $pdo->prepare("UPDATE user_progress SET xp_total = :xp_total, current_level = :current_level, 
                              last_activity_date = NOW() WHERE user_id = :user_id");
        $stmt->bindParam(':xp_total', $new_xp);
        $stmt->bindParam(':current_level', $new_level_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    } else {
        // Just update XP and last activity date
        $stmt = $pdo->prepare("UPDATE user_progress SET xp_total = :xp_total, last_activity_date = NOW() 
                              WHERE user_id = :user_id");
        $stmt->bindParam(':xp_total', $new_xp);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    // 4. Log activity
    // Modify this query to match your actual activity_log table structure
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity_type, xp_earned, created_at) 
                          VALUES (:user_id, :activity_type, :xp_earned, NOW())");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':activity_type', $activity_type);
    $stmt->bindParam(':xp_earned', $xp_earned);
    $stmt->execute();
    
    // 5. Save vocabulary if provided
    if ($object_name && $object_translation) {
        // Check if the Spanish word exists in the vocabulary table
        // Using the correct column name 'spanish_word'
        $stmt = $pdo->prepare("SELECT word_id FROM vocabulary WHERE spanish_word = :spanish_word");
        $stmt->bindParam(':spanish_word', $object_translation);
        $stmt->execute();
        $vocab = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $word_id = null;
    
        if (!$vocab) {
            // Add to vocabulary if it doesn't exist
            // Using the correct column names 'spanish_word' and 'english_translation'
            // Removed the try-catch fallback as we now know the correct columns
            $stmt = $pdo->prepare("INSERT INTO vocabulary (spanish_word, english_translation, difficulty_level, category) VALUES (:spanish_word, :english_word, 1, 'objects')"); // Added default values for required columns if applicable
            $stmt->bindParam(':spanish_word', $object_translation);
            $stmt->bindParam(':english_word', $object_name);
            $stmt->execute();
            $word_id = $pdo->lastInsertId();
    
        } else {
            $word_id = $vocab['word_id'];
        }
    
        // Add to user vocabulary using the word_id from vocabulary table
        if ($word_id) {
            // Check if user already has this vocabulary
            $stmt = $pdo->prepare("SELECT * FROM user_vocabulary WHERE user_id = :user_id AND word_id = :word_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':word_id', $word_id);
            $stmt->execute();
            $user_vocab = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$user_vocab) {
                // Add to user vocabulary
                $stmt = $pdo->prepare("INSERT INTO user_vocabulary (user_id, word_id, proficiency_level, next_review_date, times_reviewed, times_correct, last_reviewed)
                                      VALUES (:user_id, :word_id, 1, NOW() + INTERVAL '1 day', 0, 0, NOW())");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':word_id', $word_id);
                $stmt->execute();
            } else {
                // Update proficiency level and last reviewed
                $new_proficiency = min(5, $user_vocab['proficiency_level'] + 1);
                $stmt = $pdo->prepare("UPDATE user_vocabulary SET proficiency_level = :proficiency, last_reviewed = NOW(),
                                      times_reviewed = times_reviewed + 1, times_correct = times_correct + 1
                                      WHERE user_id = :user_id AND word_id = :word_id");
                $stmt->bindParam(':proficiency', $new_proficiency);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':word_id', $word_id);
                $stmt->execute();
            }
        }
    }

    // 6. Check for achievements
    $achievements = [];
    
    // Example: Check for vocabulary-related achievements
    if ($object_name && $object_translation) {
        // Count user's vocabulary
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_vocabulary WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $vocab_count = $stmt->fetchColumn();
        
        // Check for vocabulary milestones
        $milestones = [5, 10, 25, 50, 100];
        foreach ($milestones as $milestone) {
            if ($vocab_count == $milestone) {
                // Check if user already has this achievement
                $achievement_code = "vocab_" . $milestone;
                $stmt = $pdo->prepare("SELECT a.* FROM achievements a 
                                      JOIN user_achievements ua ON a.achievement_id = ua.achievement_id 
                                      WHERE ua.user_id = :user_id AND a.requirement_type = 'words_learned' AND a.requirement_value = :milestone");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':milestone', $milestone);
                $stmt->execute();
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existing) {
                    // Get achievement details
                    $stmt = $pdo->prepare("SELECT * FROM achievements WHERE requirement_type = 'words_learned' AND requirement_value = :milestone");
                    $stmt->bindParam(':milestone', $milestone);
                    $stmt->execute();
                    $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($achievement) {
                        // Award achievement to user
                        $stmt = $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) 
                                              VALUES (:user_id, :achievement_id, NOW())");
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':achievement_id', $achievement['achievement_id']);
                        $stmt->execute();
                        
                        // Add XP reward
                        $new_xp += $achievement['xp_reward'];
                        $stmt = $pdo->prepare("UPDATE user_progress SET xp_total = :xp_points WHERE user_id = :user_id");
                        $stmt->bindParam(':xp_points', $new_xp);
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        
                        // Add to response
                        $achievements[] = [
                            'name' => $achievement['name'],
                            'description' => $achievement['description'],
                            'icon_url' => $achievement['icon_url'],
                            'xp_reward' => $achievement['xp_reward']
                        ];
                    }
                }
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare response
    $response = [
        'success' => true,
        'xp_earned' => $xp_earned,
        'current_xp' => $new_xp,
        'leveled_up' => $leveled_up,
        'current_level' => $leveled_up ? $new_level['name'] : $current_level['name'],
        'next_level_xp' => $leveled_up ? $new_level['xp_required'] : $current_level['xp_required'],
        'xp_progress' => $leveled_up ? 
            ($new_xp / $new_level['xp_required'] * 100) : 
            ($new_xp / $current_level['xp_required'] * 100)
    ];
    
    // Add achievements if any
    if (!empty($achievements)) {
        $response['achievements'] = $achievements;
    }
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log("XP update error: " . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
}
?>