<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        // Handle case where user doesn't exist
        session_destroy();
        header("Location: login.php?error=invalid_user");
        exit;
    }
    
    // Fetch user's progress data
    $progress_stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :user_id");
    $progress_stmt->bindParam(':user_id', $user_id);
    $progress_stmt->execute();
    $progress = $progress_stmt->fetch();
    
    if (!$progress) {
        $progress = [
            'current_level_id' => 1,
            'xp_points' => 0,
            'streak_days' => 0,
            'total_study_time' => 0,
            'last_activity_date' => date('Y-m-d')
        ];
    }
    
    // Fetch user's current level
    $level_stmt = $pdo->prepare("
        SELECT pl.* FROM proficiency_levels pl
        JOIN user_progress up ON pl.level_id = up.current_level_id
        WHERE up.user_id = :user_id
    ");
    $level_stmt->bindParam(':user_id', $user_id);
    $level_stmt->execute();
    $current_level = $level_stmt->fetch();
    
    if (!$current_level) {
        // Default to A1 level if not found
        $level_stmt = $pdo->prepare("SELECT * FROM proficiency_levels WHERE level_code = 'A1'");
        $level_stmt->execute();
        $current_level = $level_stmt->fetch();
    }
    
    // Fetch available lessons for the user's level
    $lessons_stmt = $pdo->prepare("
        SELECT l.*, 
               (SELECT COUNT(*) FROM lesson_completions lc WHERE lc.lesson_id = l.lesson_id AND lc.user_id = :user_id) as completed
        FROM lessons l
        WHERE l.level_id = :level_id
        ORDER BY l.lesson_order
    ");
    $lessons_stmt->bindParam(':user_id', $user_id);
    $lessons_stmt->bindParam(':level_id', $current_level['level_id']);
    $lessons_stmt->execute();
    $lessons = $lessons_stmt->fetchAll();
    
} catch (PDOException $e) {
    // Log error and continue with default values
    error_log("Lessons page error: " . $e->getMessage());
    
    // Set default values
    $user = [
        'username' => $_SESSION['username'] ?? 'User',
        'first_name' => '',
        'profile_image' => ''
    ];
    
    $progress = [
        'current_level_id' => 1,
        'xp_points' => 0,
        'streak_days' => 0,
        'total_study_time' => 0
    ];
    
    $current_level = [
        'level_id' => 1,
        'level_code' => 'A1',
        'level_name' => 'Beginner'
    ];
    
    $lessons = [];
}

// Set page title
$page_title = "Spanish Lessons";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - Spanish Lessons</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/lessons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Include Navigation -->
    <?php include 'includes/nav.php'; ?>

    <!-- Lessons Content -->
    <div class="lessons-container">
        <div class="container">
            <div class="page-header">
                <br>
                <br>
                <br>
                <br>
                <h1>Spanish Lessons</h1>
                <p>Level: <?php echo htmlspecialchars($current_level['level_code'] . ' - ' . $current_level['level_name']); ?></p>
            </div>
            
            <div class="level-progress">
                <div class="progress-bar">
                    <?php 
                    // Calculate completion percentage
                    $completed_count = 0;
                    foreach ($lessons as $lesson) {
                        if ($lesson['completed'] > 0) {
                            $completed_count++;
                        }
                    }
                    $total_lessons = count($lessons);
                    $completion_percentage = $total_lessons > 0 ? ($completed_count / $total_lessons) * 100 : 0;
                    ?>
                    <div class="progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                </div>
                <div class="progress-stats">
                    <span><?php echo $completed_count; ?>/<?php echo $total_lessons; ?> lessons completed</span>
                    <span><?php echo round($completion_percentage); ?>% complete</span>
                </div>
            </div>
            
            <div class="lessons-grid">
                <?php if (empty($lessons)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book"></i>
                        <h3>No lessons available</h3>
                        <p>We're working on adding lessons for your level. Please check back soon!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($lessons as $index => $lesson): ?>
                        <div class="lesson-card <?php echo $lesson['completed'] > 0 ? 'completed' : ''; ?> <?php echo $index > 0 && $lessons[$index-1]['completed'] == 0 ? 'locked' : ''; ?>">
                            <div class="lesson-icon">
                                <?php if ($lesson['completed'] > 0): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php elseif ($index > 0 && $lessons[$index-1]['completed'] == 0): ?>
                                    <i class="fas fa-lock"></i>
                                <?php else: ?>
                                    <i class="<?php echo $lesson['icon'] ?? 'fas fa-book'; ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="lesson-info">
                                <h3><?php echo htmlspecialchars($lesson['title']); ?></h3>
                                <p><?php echo htmlspecialchars($lesson['description']); ?></p>
                                <div class="lesson-meta">
                                    <span><i class="fas fa-clock"></i> <?php echo $lesson['estimated_time']; ?> min</span>
                                    <span><i class="fas fa-star"></i> <?php echo $lesson['xp_reward']; ?> XP</span>
                                </div>
                            </div>
                            <div class="lesson-action">
                                <?php if ($lesson['completed'] > 0): ?>
                                    <a href="lesson-detail.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-outline">Review</a>
                                <?php elseif ($index > 0 && $lessons[$index-1]['completed'] == 0): ?>
                                    <button class="btn btn-disabled" disabled>Locked</button>
                                <?php else: ?>
                                    <a href="lesson-detail.php?id=<?php echo $lesson['lesson_id']; ?>" class="btn btn-primary">Start</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="level-navigation">
                <h3>Other Levels</h3>
                <div class="level-buttons">
                    <?php 
                    // Fetch all levels
                    try {
                        $all_levels_stmt = $pdo->prepare("SELECT * FROM proficiency_levels ORDER BY level_order");
                        $all_levels_stmt->execute();
                        $all_levels = $all_levels_stmt->fetchAll();
                        
                        foreach ($all_levels as $level):
                    ?>
                        <a href="change-level.php?level_id=<?php echo $level['level_id']; ?>" 
                           class="level-button <?php echo $level['level_id'] == $current_level['level_id'] ? 'active' : ''; ?>">
                            <?php echo $level['level_code']; ?>
                        </a>
                    <?php 
                        endforeach;
                    } catch (PDOException $e) {
                        error_log("Error fetching levels: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="js/dark-mode.js"></script>
    <script src="js/user-dropdown.js"></script>
    <script src="js/mobile-nav.js"></script>
</body>
</html>