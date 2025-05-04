<?php
// Include configuration file
require_once "config.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Common functions for CerveLingua application
 */

/**
 * Sanitize user input
 * @param string $data The data to sanitize
 * @return string The sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has admin role
 * @return bool True if user is admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect to a URL
 * @param string $url The URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check for remember me cookie and log in user if valid
 */
function check_remember_me() {
    global $pdo;
    
    // Only check if user is not logged in and the remember token exists
    if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            // Check if the remember_tokens table exists before querying it
            $table_check = $pdo->query("SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'remember_tokens'
            )");
            
            $table_exists = $table_check->fetchColumn();
            
            if (!$table_exists) {
                // Table doesn't exist, so we can't validate the token
                return false;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM remember_tokens WHERE token = :token AND expires_at > NOW()");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $token_data = $stmt->fetch();
                $user_id = $token_data['user_id'];
                
                // Get user data
                $user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
                $user_stmt->bindParam(':user_id', $user_id);
                $user_stmt->execute();
                
                if ($user_stmt->rowCount() > 0) {
                    $user_data = $user_stmt->fetch();
                    $_SESSION['user_id'] = $user_data['user_id'];
                    $_SESSION['username'] = $user_data['username'];
                    $_SESSION['email'] = $user_data['email'];
                    $_SESSION['role'] = isset($user_data['role']) ? $user_data['role'] : 'user';
                    
                    // Update last login time
                    $update_stmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id");
                    $update_stmt->bindParam(':user_id', $user_id);
                    $update_stmt->execute();
                    
                    // Prevent redirect loops by checking the current page
                    $current_page = basename($_SERVER['PHP_SELF']);
                    if ($current_page === 'login.php' || $current_page === 'signup.php') {
                        header("Location: dashboard.php");
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Remember me error: " . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

/**
 * Generate a random token
 * @param int $length - Length of the token
 * @return string - Random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Get user data by ID
 * @param int $user_id - User ID
 * @return array|false - User data or false if not found
 */
function get_user_by_id($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Get user progress data
 * @param int $user_id - User ID
 * @return array|false - Progress data or false if not found
 */
function get_user_progress($user_id) {
    global $pdo;
    
    try {
        // First check if the table exists
        $table_check = $pdo->query("SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'user_progress'
        )");
        $table_exists = $table_check->fetchColumn();
        
        if (!$table_exists) {
            // Table doesn't exist, return default progress
            return [
                'user_id' => $user_id,
                'current_level_id' => 1,
                'xp_points' => 0,
                'streak_days' => 0,
                'last_activity_date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        
        // If table exists, query it
        $stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        if (!$result) {
            // No progress record found, create default
            return [
                'user_id' => $user_id,
                'current_level_id' => 1,
                'xp_points' => 0,
                'streak_days' => 0,
                'last_activity_date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error getting user progress: " . $e->getMessage());
        // Return default progress on error
        return [
            'user_id' => $user_id,
            'current_level_id' => 1,
            'xp_points' => 0,
            'streak_days' => 0,
            'last_activity_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * Update user XP
 * @param int $user_id - User ID
 * @param int $xp - XP to add
 * @return boolean - Success or failure
 */
function add_user_xp($user_id, $xp) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE user_progress SET xp_points = xp_points + :xp, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id");
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":xp", $xp, PDO::PARAM_INT);
    
    return $stmt->execute();
}

/**
 * Log user activity
 * @param int $user_id - User ID
 * @param string $activity_type - Type of activity
 * @param int $xp_earned - XP earned from activity
 * @param array $details - Additional details (will be stored as JSON)
 * @return boolean - Success or failure
 */
function log_activity($user_id, $activity_type, $xp_earned = 0, $details = []) {
    global $pdo;
    
    $json_details = json_encode($details);
    
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, activity_type, xp_earned, details, created_at) VALUES (:user_id, :activity_type, :xp_earned, :details, CURRENT_TIMESTAMP)");
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->bindParam(":activity_type", $activity_type, PDO::PARAM_STR);
    $stmt->bindParam(":xp_earned", $xp_earned, PDO::PARAM_INT);
    $stmt->bindParam(":details", $json_details, PDO::PARAM_STR);
    
    // If successful and XP was earned, update user's XP
    if ($stmt->execute() && $xp_earned > 0) {
        add_user_xp($user_id, $xp_earned);
        return true;
    }
    
    return false;
}

/**
 * Get level information
 * @param int $level_id - Level ID
 * @return array|false - Level data or false if not found
 */
function get_level_info($level_id) {
    global $pdo;
    
    try {
        // First check if the table exists
        $table_check = $pdo->query("SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'proficiency_levels'
        )");
        $table_exists = $table_check->fetchColumn();
        
        if (!$table_exists) {
            // Table doesn't exist, return default level info
            return [
                'level_id' => $level_id,
                'level_name' => 'Level ' . $level_id,
                'description' => 'Default level description',
                'xp_required' => $level_id * 100
            ];
        }
        
        // If table exists, query it
        $stmt = $pdo->prepare("SELECT * FROM proficiency_levels WHERE level_id = :level_id");
        $stmt->bindParam(":level_id", $level_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting level info: " . $e->getMessage());
        // Return default level info on error
        return [
            'level_id' => $level_id,
            'level_name' => 'Level ' . $level_id,
            'description' => 'Default level description',
            'xp_required' => $level_id * 100
        ];
    }
}

/**
 * Update user streak
 * @param int $user_id - User ID
 * @return boolean - Success or failure
 */
function update_user_streak($user_id) {
    global $pdo;
    
    // Get user progress
    $progress = get_user_progress($user_id);
    
    if (!$progress) {
        return false;
    }
    
    // Check if last activity was today
    $last_activity = new DateTime($progress['last_activity_date']);
    $today = new DateTime('today');
    
    // If last activity was yesterday, increment streak
    if ($last_activity->diff($today)->days == 1) {
        $stmt = $pdo->prepare("UPDATE user_progress SET streak_days = streak_days + 1, last_activity_date = CURRENT_DATE, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    } 
    // If last activity was today, just update the timestamp
    elseif ($last_activity->format('Y-m-d') == $today->format('Y-m-d')) {
        return true;
    } 
    // If more than 1 day has passed, reset streak to 1
    else {
        $stmt = $pdo->prepare("UPDATE user_progress SET streak_days = 1, last_activity_date = CURRENT_DATE, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

/**
 * Get vocabulary items for a user based on level
 * @param int $user_id - User ID
 * @param int $limit - Maximum number of items to return
 * @return array - Vocabulary items
 */
function get_user_vocabulary($user_id, $limit = 10) {
    global $pdo;
    
    // Get user's current level
    $progress = get_user_progress($user_id);
    
    if (!$progress) {
        return [];
    }
    
    $level_id = $progress['current_level_id'];
    
    // Get vocabulary items for user's level
    $stmt = $pdo->prepare("SELECT * FROM vocabulary WHERE level_id <= :level_id ORDER BY RANDOM() LIMIT :limit");
    $stmt->bindParam(":level_id", $level_id, PDO::PARAM_INT);
    $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Check if email exists
 * @param string $email - Email to check
 * @return boolean - True if email exists, false otherwise
 */
function email_exists($email) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if username exists
 * @param string $username - Username to check
 * @return boolean - True if username exists, false otherwise
 */
function username_exists($username) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
    $stmt->bindParam(":username", $username, PDO::PARAM_STR);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Format date for display
 * @param string $date - Date string
 * @param string $format - Format string (default: 'M d, Y')
 * @return string - Formatted date
 */
function format_date($date, $format = 'M d, Y') {
    $datetime = new DateTime($date);
    return $datetime->format($format);
}

// Only call check_remember_me if we're not already in a potential redirect situation
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'dashboard.php' && $current_page !== 'login.php' && $current_page !== 'signup.php') {
    check_remember_me();
}
?>