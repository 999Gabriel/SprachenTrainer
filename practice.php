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
    
} catch (PDOException $e) {
    // Log error and continue with default values
    error_log("Practice page error: " . $e->getMessage());
    
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
}

// Set page title
$page_title = "Practice Spanish";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - Practice Spanish</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/practice.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar ipad-style">
        <div class="container">
            <div class="logo">
                <a href="dashboard.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <img src="img/Generiertes Bild.jpeg" alt="CerveLingua Logo">
                    <span>CerveLingua</span>
                </a>
            </div>
            <div class="nav-links">
                <!-- Links relevant to logged-in user -->
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="lessons.php" class="nav-link">Lessons</a>
                <a href="practice.php" class="nav-link active">Practice</a>
                <a href="ai-conversation.php" class="nav-link">AI Conversation</a>
                <a href="camera-learning.php" class="nav-link">Visual Learning</a>
            </div>
            <div class="cta-buttons user-menu-container">
                <!-- <div class="theme-toggle">
                    <i class="fas fa-sun sun-icon"></i>
                    <label>
                        <input type="checkbox" id="dark-mode-toggle">
                        <span class="slider"></span>
                    </label>
                    <i class="fas fa-moon moon-icon"></i>
                </div> -->
                <div class="user-profile">
                    <?php if(!empty($user['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image">
                    <?php else: ?>
                        <div class="profile-initial"><?php echo strtoupper(substr(htmlspecialchars($user['username']), 0, 1)); ?></div>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                    <!-- Dropdown Menu -->
                    <div class="dropdown-menu">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Practice Content -->
    <div class="practice-container">
        <div class="container">
            <div class="page-header">
                <br>
                <br>
                <br>
                <br>
                <h1>Practice Spanish</h1>
                <p>Choose a skill to practice and improve your Spanish</p>
            </div>
            
            <div class="practice-grid">
                <!-- Listening Practice -->
                <div class="practice-card">
                    <div class="practice-icon">
                        <i class="fas fa-headphones"></i>
                    </div>
                    <div class="practice-info">
                        <h3>Listening</h3>
                        <p>Improve your Spanish listening comprehension with audio exercises</p>
                        <div class="practice-meta">
                            <span><i class="fas fa-clock"></i> 5-15 min</span>
                            <span><i class="fas fa-star"></i> Earn XP</span>
                        </div>
                    </div>
                    <div class="practice-action">
                        <a href="practice-listening.php" class="btn btn-primary">Start</a>
                    </div>
                </div>
                
                <!-- Speaking Practice -->
                <div class="practice-card">
                    <div class="practice-icon">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <div class="practice-info">
                        <h3>Speaking</h3>
                        <p>Practice your Spanish pronunciation and speaking skills</p>
                        <div class="practice-meta">
                            <span><i class="fas fa-clock"></i> 5-15 min</span>
                            <span><i class="fas fa-star"></i> Earn XP</span>
                        </div>
                    </div>
                    <div class="practice-action">
                        <a href="practice-speaking.php" class="btn btn-primary">Start</a>
                    </div>
                </div>
                
                <!-- Reading Practice -->
                <div class="practice-card">
                    <div class="practice-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="practice-info">
                        <h3>Reading</h3>
                        <p>Improve your Spanish reading comprehension with texts at your level</p>
                        <div class="practice-meta">
                            <span><i class="fas fa-clock"></i> 10-20 min</span>
                            <span><i class="fas fa-star"></i> Earn XP</span>
                        </div>
                    </div>
                    <div class="practice-action">
                        <a href="practice-reading.php" class="btn btn-primary">Start</a>
                    </div>
                </div>
                
                <!-- Writing Practice -->
                <div class="practice-card">
                    <div class="practice-icon">
                        <i class="fas fa-pen"></i>
                    </div>
                    <div class="practice-info">
                        <h3>Writing</h3>
                        <p>Practice writing in Spanish with guided exercises and feedback</p>
                        <div class="practice-meta">
                            <span><i class="fas fa-clock"></i> 10-20 min</span>
                            <span><i class="fas fa-star"></i> Earn XP</span>
                        </div>
                    </div>
                    <div class="practice-action">
                        <a href="practice-writing.php" class="btn btn-primary">Start</a>
                    </div>
                </div>
                
                <!-- Vocabulary Practice -->
                <div class="practice-card">
                    <div class="practice-icon">
                        <i class="fas fa-language"></i>
                    </div>
                    <div class="practice-info">
                        <h3>Vocabulary</h3>
                        <p>Expand your Spanish vocabulary with flashcards and memory games</p>
                        <div class="practice-meta">
                            <span><i class="fas fa-clock"></i> 5-10 min</span>
                            <span><i class="fas fa-star"></i> Earn XP</span>
                        </div>
                    </div>
                    <div class="practice-action">
                        <a href="practice-vocabulary.php" class="btn btn-primary">Start</a>
                    </div>
                </div>
                
                <!-- Grammar Practice -->
                <div class="practice-card">
                    <div class="practice-icon">
                        <i class="fas fa-check-square"></i>
                    </div>
                    <div class="practice-info">
                        <h3>Grammar</h3>
                        <p>Master Spanish grammar with interactive exercises and quizzes</p>
                        <div class="practice-meta">
                            <span><i class="fas fa-clock"></i> 10-15 min</span>
                            <span><i class="fas fa-star"></i> Earn XP</span>
                        </div>
                    </div>
                    <div class="practice-action">
                        <a href="practice-grammar.php" class="btn btn-primary">Start</a>
                    </div>
                </div>
            </div>
            
            <div class="daily-challenge">
                <div class="challenge-header">
                    <h3><i class="fas fa-fire"></i> Daily Challenge</h3>
                    <span class="challenge-xp">+50 XP</span>
                </div>
                <p>Complete today's challenge to maintain your streak and earn bonus XP!</p>
                <div class="challenge-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress['streak_days'] % 7 * (100/7); ?>%"></div>
                    </div>
                    <div class="progress-days">
                        <?php for($i = 1; $i <= 7; $i++): ?>
                            <div class="day-marker <?php echo ($progress['streak_days'] % 7 >= $i) ? 'completed' : ''; ?>">
                                <?php echo $i; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <a href="daily-challenge.php" class="btn btn-primary btn-block">Start Daily Challenge</a>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="js/dark-mode.js"></script>
    <script src="js/user-dropdown.js"></script>
</body>
</html>