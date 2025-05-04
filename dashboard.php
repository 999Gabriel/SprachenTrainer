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
    
    // Check if user_progress table exists and fetch progress data
    $table_check = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'user_progress'
    )");
    $user_progress_exists = $table_check->fetchColumn();
    
    if ($user_progress_exists) {
        // Get user progress if table exists
        $progress_stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :user_id");
        $progress_stmt->bindParam(':user_id', $user_id);
        $progress_stmt->execute();
        $progress = $progress_stmt->fetch();
        
        // If no progress record, create default values
        if (!$progress) {
            // Insert default progress record
            $default_progress_stmt = $pdo->prepare("
                INSERT INTO user_progress 
                (user_id, current_level_id, xp_points, streak_days, total_study_time, last_activity_date) 
                VALUES (:user_id, 1, 0, 0, 0, CURRENT_DATE)
                RETURNING *
            ");
            $default_progress_stmt->bindParam(':user_id', $user_id);
            $default_progress_stmt->execute();
            $progress = $default_progress_stmt->fetch();
            
            if (!$progress) {
                $progress = [
                    'current_level_id' => 1,
                    'xp_points' => 0,
                    'streak_days' => 0,
                    'total_study_time' => 0,
                    'last_activity_date' => date('Y-m-d')
                ];
            }
        }
        
        // Update streak if needed
        $last_activity = new DateTime($progress['last_activity_date']);
        $today = new DateTime('today');
        $diff = $today->diff($last_activity)->days;
        
        if ($diff == 0) {
            // Already logged in today, do nothing
        } else if ($diff == 1) {
            // Consecutive day, increase streak
            $update_streak = $pdo->prepare("
                UPDATE user_progress 
                SET streak_days = streak_days + 1, last_activity_date = CURRENT_DATE 
                WHERE user_id = :user_id
            ");
            $update_streak->bindParam(':user_id', $user_id);
            $update_streak->execute();
            $progress['streak_days']++;
        } else {
            // Streak broken, reset to 1
            $reset_streak = $pdo->prepare("
                UPDATE user_progress 
                SET streak_days = 1, last_activity_date = CURRENT_DATE 
                WHERE user_id = :user_id
            ");
            $reset_streak->bindParam(':user_id', $user_id);
            $reset_streak->execute();
            $progress['streak_days'] = 1;
        }
    } else {
        // Default progress values if table doesn't exist
        $progress = [
            'current_level_id' => 1,
            'xp_points' => 0,
            'streak_days' => 0,
            'total_study_time' => 0,
            'last_activity_date' => date('Y-m-d')
        ];
    }
    
    // Check if proficiency_levels table exists and fetch level data
    $table_check = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'proficiency_levels'
    )");
    $levels_exists = $table_check->fetchColumn();
    
    if ($levels_exists) {
        // Get user's current level if table exists
        $level_stmt = $pdo->prepare("SELECT * FROM proficiency_levels WHERE level_id = :level_id");
        $level_stmt->bindParam(':level_id', $progress['current_level_id']);
        $level_stmt->execute();
        $level = $level_stmt->fetch();
        
        // If level not found, use default
        if (!$level) {
            $level = [
                'level_name' => 'Beginner',
                'level_code' => 'A1',
                'description' => 'Basic understanding of Spanish'
            ];
        }
    } else {
        // Default level if table doesn't exist
        $level = [
            'level_name' => 'Beginner',
            'level_code' => 'A1',
            'description' => 'Basic understanding of Spanish'
        ];
    }
    
    // Fetch user's lessons
    $table_check = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'lessons'
    )");
    $lessons_exists = $table_check->fetchColumn();
    
    if ($lessons_exists) {
        // Get user's current lesson
        $lesson_stmt = $pdo->prepare("
            SELECT l.*, COALESCE(ul.progress_percentage, 0) as progress_percentage 
            FROM lessons l
            LEFT JOIN user_lesson_progress ul ON l.lesson_id = ul.lesson_id AND ul.user_id = :user_id
            WHERE l.level_id <= :level_id
            ORDER BY 
                CASE WHEN ul.progress_percentage < 100 OR ul.progress_percentage IS NULL THEN 0 ELSE 1 END,
                l.lesson_id
            LIMIT 1
        ");
        $lesson_stmt->bindParam(':user_id', $user_id);
        $lesson_stmt->bindParam(':level_id', $progress['current_level_id']);
        $lesson_stmt->execute();
        $current_lesson = $lesson_stmt->fetch();
        
        if (!$current_lesson) {
            $current_lesson = [
                'lesson_id' => 1,
                'title' => 'Basic Conversations',
                'description' => 'Learn how to introduce yourself and have simple conversations',
                'progress_percentage' => 0
            ];
        }
    } else {
        $current_lesson = [
            'lesson_id' => 1,
            'title' => 'Basic Conversations',
            'description' => 'Learn how to introduce yourself and have simple conversations',
            'progress_percentage' => 75
        ];
    }
    
    // Fetch vocabulary items
    $table_check = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'vocabulary'
    )");
    $vocab_exists = $table_check->fetchColumn();
    
    if ($vocab_exists) {
        // Get vocabulary items for review
        $vocab_stmt = $pdo->prepare("
            SELECT v.* 
            FROM vocabulary v
            LEFT JOIN user_vocabulary uv ON v.vocab_id = uv.vocab_id AND uv.user_id = :user_id
            WHERE v.level_id <= :level_id
            ORDER BY 
                CASE WHEN uv.last_reviewed IS NULL THEN 0 ELSE 1 END,
                COALESCE(uv.mastery_level, 0),
                RANDOM()
            LIMIT 3
        ");
        $vocab_stmt->bindParam(':user_id', $user_id);
        $vocab_stmt->bindParam(':level_id', $progress['current_level_id']);
        $vocab_stmt->execute();
        $vocab_items = $vocab_stmt->fetchAll();
        
        if (empty($vocab_items)) {
            $vocab_items = [
                ['spanish_word' => 'Hola', 'english_translation' => 'Hello'],
                ['spanish_word' => 'Gracias', 'english_translation' => 'Thank you'],
                ['spanish_word' => 'Por favor', 'english_translation' => 'Please']
            ];
        }
    } else {
        $vocab_items = [
            ['spanish_word' => 'Hola', 'english_translation' => 'Hello'],
            ['spanish_word' => 'Gracias', 'english_translation' => 'Thank you'],
            ['spanish_word' => 'Por favor', 'english_translation' => 'Please']
        ];
    }
    
    // Calculate streak calendar
    $streak_days = $progress['streak_days'];
    $today_day = date('N'); // 1 (Monday) to 7 (Sunday)
    $streak_calendar = [];
    
    // Fill the streak calendar
    for ($i = 1; $i <= 7; $i++) {
        if ($i < $today_day) {
            // Past days in current week
            $days_ago = $today_day - $i;
            $is_active = ($days_ago < $streak_days);
            $streak_calendar[] = ['day' => $i, 'active' => $is_active];
        } else if ($i == $today_day) {
            // Today
            $streak_calendar[] = ['day' => $i, 'active' => true, 'today' => true];
        } else {
            // Future days
            $streak_calendar[] = ['day' => $i, 'active' => false];
        }
    }
    
} catch (PDOException $e) {
    // Log error and continue with default values
    error_log("Dashboard error: " . $e->getMessage());
    
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
    
    $level = [
        'level_name' => 'Beginner',
        'level_code' => 'A1'
    ];
    
    $current_lesson = [
        'lesson_id' => 1,
        'title' => 'Basic Conversations',
        'description' => 'Learn how to introduce yourself and have simple conversations',
        'progress_percentage' => 0
    ];
    
    $vocab_items = [
        ['spanish_word' => 'Hola', 'english_translation' => 'Hello'],
        ['spanish_word' => 'Gracias', 'english_translation' => 'Thank you'],
        ['spanish_word' => 'Por favor', 'english_translation' => 'Please']
    ];
    
    $streak_calendar = [
        ['day' => 1, 'active' => false],
        ['day' => 2, 'active' => false],
        ['day' => 3, 'active' => false],
        ['day' => 4, 'active' => false],
        ['day' => 5, 'active' => false],
        ['day' => 6, 'active' => false],
        ['day' => 7, 'active' => false]
    ];
    $streak_calendar[date('N')-1]['active'] = true;
    $streak_calendar[date('N')-1]['today'] = true;
}

// Set page title
$page_title = "Dashboard";

// Map day numbers to day names
$day_names = [
    1 => 'M',
    2 => 'T',
    3 => 'W',
    4 => 'T',
    5 => 'F',
    6 => 'S',
    7 => 'S'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - Dashboard</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- Base styles (includes navbar) -->
    <link rel="stylesheet" href="css/dashboard.css"> <!-- Dashboard specific styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> <!-- Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"> <!-- Font -->
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
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="lessons.php" class="nav-link">Lessons</a>
                <a href="practice.php" class="nav-link">Practice</a>
                <a href="ai-conversation.php" class="nav-link">AI Conversation</a>
                <a href="camera-learning.php" class="nav-link">Start to learn visually!</a>
            </div>
            <div class="cta-buttons user-menu-container"> <!-- Combine CTA and user menu area -->
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
            <div class="menu-toggle"> <!-- Keep mobile menu toggle -->
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-header">
                <h1>Welcome back, <?php echo htmlspecialchars($user['first_name'] ? $user['first_name'] : $user['username']); ?>!</h1>
                <p>Continue your Spanish learning journey</p>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Current Level</h3>
                        <p><?php echo $level['level_name']; ?> (<?php echo $level['level_code']; ?>)</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3>XP Points</h3>
                        <p><?php echo $progress['xp_points']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Streak</h3>
                        <p><?php echo $progress['streak_days']; ?> days</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Study Time</h3>
                        <p><?php echo isset($progress['total_study_time']) ? floor($progress['total_study_time'] / 60) : 0; ?> hours</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="section-row">
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Continue Learning</h2>
                            <a href="lessons.php" class="view-all">View All</a>
                        </div>
                        <div class="section-content">
                            <div class="lesson-card">
                                <div class="lesson-progress">
                                    <div class="progress-bar" style="width: <?php echo $current_lesson['progress_percentage']; ?>%"></div>
                                </div>
                                <h3><?php echo $current_lesson['title']; ?></h3>
                                <p><?php echo $current_lesson['description']; ?></p>
                                <a href="lesson.php?id=<?php echo $current_lesson['lesson_id']; ?>" class="btn btn-primary">Continue</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Daily Practice</h2>
                            <a href="practice.php" class="view-all">View All</a>
                        </div>
                        <div class="section-content">
                            <div class="practice-card">
                                <div class="practice-icon">
                                    <i class="fas fa-volume-up"></i>
                                </div>
                                <div class="practice-info">
                                    <h3>Listening Exercise</h3>
                                    <p>Improve your listening comprehension</p>
                                </div>
                                <a href="practice.php?type=listening" class="btn btn-outline">Start</a>
                            </div>
                            <div class="practice-card">
                                <div class="practice-icon">
                                    <i class="fas fa-comment"></i>
                                </div>
                                <div class="practice-info">
                                    <h3>Speaking Practice</h3>
                                    <p>Practice your pronunciation</p>
                                </div>
                                <a href="practice.php?type=speaking" class="btn btn-outline">Start</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="section-row">
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Vocabulary Review</h2>
                            <a href="vocabulary.php" class="view-all">View All</a>
                        </div>
                        <div class="section-content">
                            <div class="vocab-cards">
                                <?php foreach ($vocab_items as $vocab): ?>
                                <div class="vocab-card">
                                    <div class="vocab-front">
                                        <span><?php echo $vocab['spanish_word']; ?></span>
                                    </div>
                                    <div class="vocab-back">
                                        <span><?php echo $vocab['english_translation']; ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2>Learning Streak</h2>
                        </div>
                        <div class="section-content">
                            <div class="streak-calendar">
                                <div class="streak-week">
                                    <?php foreach ($streak_calendar as $day): ?>
                                    <div class="streak-day <?php echo $day['active'] ? 'active' : ''; ?> <?php echo isset($day['today']) && $day['today'] ? 'today' : ''; ?>">
                                        <?php echo $day_names[$day['day']]; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <p>You're on a <?php echo $progress['streak_days']; ?>-day streak! Keep it up!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Avatar -->
    <div id="interactive-avatar-container">
        <img src="img/CerveLingua_Avatar.png" alt="CerveLingua Mascot" id="interactive-avatar">
        <div id="avatar-speech-bubble" class="speech-bubble">
            <h4>Your Learning Stats</h4>
            <p><i class="fas fa-trophy"></i> Level: <?php echo $level['level_name']; ?> (<?php echo $level['level_code']; ?>)</p>
            <p><i class="fas fa-star"></i> XP Points: <?php echo $progress['xp_points']; ?></p>
            <p><i class="fas fa-fire"></i> Streak: <?php echo $progress['streak_days']; ?> days</p>
            <p><i class="fas fa-clock"></i> Study Time: <?php echo isset($progress['total_study_time']) ? floor($progress['total_study_time'] / 60) : 0; ?> hours</p>
            <p><i class="fas fa-book"></i> Current Lesson: <?php echo $current_lesson['title']; ?></p>
            <p><i class="fas fa-chart-line"></i> Lesson Progress: <?php echo $current_lesson['progress_percentage']; ?>%</p>
            <p><i class="fas fa-calendar-check"></i> Last Activity: <?php echo isset($progress['last_activity_date']) && !empty($progress['last_activity_date']) ? date('M d, Y', strtotime($progress['last_activity_date'])) : date('M d, Y'); ?></p>
            <p><i class="fas fa-graduation-cap"></i> Next Level: <?php echo ($progress['current_level_id'] < 6) ? $progress['current_level_id'] + 1 : 'Max Level'; ?></p>
        </div>
    </div>
    <!-- End Interactive Avatar -->

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- <script src="js/dashboard.js"></script> -->
    <script src="js/user-dropdown.js"></script> 
    
    <!-- Avatar Interaction Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const avatar = document.getElementById('interactive-avatar');
            const speechBubble = document.getElementById('avatar-speech-bubble');
            let bubbleVisible = false;
            
            // Show speech bubble when avatar is clicked
            avatar.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent event from bubbling up
                
                if (bubbleVisible) {
                    speechBubble.classList.remove('show');
                } else {
                    // Show progress info with proper date handling
                    speechBubble.innerHTML = `
                        <h4>Your Learning Stats</h4>
                        <p><i class="fas fa-trophy"></i> Level: <?php echo $level['level_name']; ?> (<?php echo $level['level_code']; ?>)</p>
                        <p><i class="fas fa-star"></i> XP Points: <?php echo $progress['xp_points']; ?></p>
                        <p><i class="fas fa-fire"></i> Streak: <?php echo $progress['streak_days']; ?> days</p>
                        <p><i class="fas fa-clock"></i> Study Time: <?php echo isset($progress['total_study_time']) ? floor($progress['total_study_time'] / 60) : 0; ?> hours</p>
                        <p><i class="fas fa-book"></i> Current Lesson: <?php echo $current_lesson['title']; ?></p>
                        <p><i class="fas fa-chart-line"></i> Lesson Progress: <?php echo $current_lesson['progress_percentage']; ?>%</p>
                        <p><i class="fas fa-calendar-check"></i> Last Activity: <?php echo isset($progress['last_activity_date']) && !empty($progress['last_activity_date']) ? date('M d, Y', strtotime($progress['last_activity_date'])) : date('M d, Y'); ?></p>
                        <p><i class="fas fa-graduation-cap"></i> Next Level: <?php echo ($progress['current_level_id'] < 6) ? $progress['current_level_id'] + 1 : 'Max Level'; ?></p>
                    `;
                    speechBubble.classList.add('show');
                }
                
                bubbleVisible = !bubbleVisible;
            });
            
            // Hide speech bubble when clicked outside
            document.addEventListener('click', function(event) {
                if (bubbleVisible && !avatar.contains(event.target) && !speechBubble.contains(event.target)) {
                    speechBubble.classList.remove('show');
                    bubbleVisible = false;
                }
            });
            
            // Prevent bubble clicks from closing it
            speechBubble.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Show welcome message on page load (with slight delay)
            setTimeout(function() {
                speechBubble.innerHTML = `
                    <h4>¡Hola, <?php echo htmlspecialchars($user['first_name'] ? $user['first_name'] : $user['username']); ?>!</h4>
                    <p><i class="fas fa-fire"></i> You're on a <?php echo $progress['streak_days']; ?>-day streak!</p>
                    <p><i class="fas fa-star"></i> You have <?php echo $progress['xp_points']; ?> XP points</p>
                    <p><i class="fas fa-info-circle"></i> Click on me anytime to see your detailed progress.</p>
                `;
                speechBubble.classList.add('show');
                bubbleVisible = true;
                
                // Hide initial message after 6 seconds
                setTimeout(function() {
                    speechBubble.classList.remove('show');
                    bubbleVisible = false;
                }, 6000);
            }, 1500);
        });
    </script>
</body>
</html>

