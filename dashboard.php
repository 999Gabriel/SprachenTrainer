<?php
// Include necessary files and start session
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Handle case where user doesn't exist
        error_log("User not found in database: " . $user_id);
        session_destroy();
        header("Location: login.php?error=invalid_user");
        exit;
    }
    
    // Directly query for user progress data
    $progress_stmt = $pdo->prepare("
        SELECT * FROM user_progress WHERE user_id = :user_id
    ");
    $progress_stmt->bindParam(':user_id', $user_id);
    $progress_stmt->execute();
    $progress = $progress_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug output
    error_log("Raw progress data: " . json_encode($progress));
    
    // If no progress record exists, create one
    if (!$progress) {
        // Insert default progress record
        $insert_stmt = $pdo->prepare("
            INSERT INTO user_progress 
            (user_id, current_level, xp_total, streak_days, minutes_learned, last_activity_date) 
            VALUES (:user_id, 1, 0, 1, 0, CURRENT_DATE)
        ");
        $insert_stmt->bindParam(':user_id', $user_id);
        $insert_stmt->execute();
        
        // Fetch the newly created record
        $progress_stmt->execute();
        $progress = $progress_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$progress) {
            // Fallback if fetch doesn't work
            $progress = [
                'user_id' => $user_id,
                'current_level' => 1,
                'xp_total' => 0,
                'streak_days' => 1,
                'minutes_learned' => 0,
                'last_activity_date' => date('Y-m-d')
            ];
        }
    }
    
    // Ensure we have the correct column mappings
    if (!isset($progress['current_level_id']) && isset($progress['current_level'])) {
        $progress['current_level_id'] = $progress['current_level'];
    }
    
    if (!isset($progress['total_study_time']) && isset($progress['minutes_learned'])) {
        $progress['total_study_time'] = $progress['minutes_learned'];
    }
    
    // Force cast xp_total to integer to ensure proper display
    $progress['xp_total'] = isset($progress['xp_total']) ? (int)$progress['xp_total'] : 0;
    
    // Debug output
    error_log("User progress data after fixes: " . json_encode($progress));
    
    // Update streak if needed
    $last_activity = new DateTime($progress['last_activity_date']);
    $today = new DateTime('today');
    $diff = $today->diff($last_activity)->days;
    
    if ($diff == 1) {
        // Consecutive day, increase streak
        $update_streak = $pdo->prepare("
            UPDATE user_progress 
            SET streak_days = streak_days + 1, last_activity_date = CURRENT_DATE 
            WHERE user_id = :user_id
        ");
        $update_streak->bindParam(':user_id', $user_id);
        $update_streak->execute();
        $progress['streak_days']++;
    } else if ($diff > 1) {
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
    
    // Get user's current level
    $level_stmt = $pdo->prepare("SELECT * FROM levels WHERE level_id = :level_id");
    $level_stmt->bindParam(':level_id', $progress['current_level']);
    $level_stmt->execute();
    $level = $level_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If level not found, use default
    if (!$level) {
        $level = [
            'level_id' => 1,
            'level_name' => 'Beginner',
            'level_code' => 'A1',
            'description' => 'Basic understanding of Spanish'
        ];
    }
    
    // Get vocabulary items for review
    $vocab_stmt = $pdo->prepare("
        SELECT v.* 
        FROM vocabulary v
        LEFT JOIN user_vocabulary uv ON v.word_id = uv.word_id AND uv.user_id = :user_id
        ORDER BY RANDOM()
        LIMIT 3
    ");
    $vocab_stmt->bindParam(':user_id', $user_id);
    $vocab_stmt->execute();
    $vocab_items = $vocab_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($vocab_items)) {
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
        'current_level' => 1,
        'current_level_id' => 1,
        'xp_total' => 0,
        'streak_days' => 0,
        'minutes_learned' => 0,
        'total_study_time' => 0,
        'last_activity_date' => date('Y-m-d')
    ];
    
    $level = [
        'level_name' => 'Beginner',
        'level_code' => 'A1'
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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Enhanced Dashboard Styles */
        .user-profile {
    position: relative;
    cursor: pointer;
}

.user-profile .dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 110%;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    min-width: 160px;
    z-index: 100;
    padding: 10px 0;
}

.user-profile .dropdown-menu.show {
    display: block;
}

.user-profile .dropdown-menu a {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    color: #333;
    text-decoration: none;
    font-size: 1rem;
    transition: background 0.2s;
}

.user-profile .dropdown-menu a:hover {
    background: #f5f6fa;
}
        body {
            background-color: #f8f9fa;
            color: #333;
        }
        
        .dashboard-container {
            padding: 30px 0;
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .dashboard-header h1 {
            font-size: 2.5rem;
            color: #4a6fa5;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .dashboard-header p {
            font-size: 1.2rem;
            color: #6c757d;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #4a6fa5;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card:nth-child(1) {
            border-left-color: #4a6fa5; /* Blue */
        }
        
        .stat-card:nth-child(2) {
            border-left-color: #ffc107; /* Yellow */
        }
        
        .stat-card:nth-child(3) {
            border-left-color: #ff7043; /* Orange */
        }
        
        .stat-card:nth-child(4) {
            border-left-color: #66bb6a; /* Green */
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
        }
        
        .stat-card:nth-child(1) .stat-icon {
            background-color: rgba(74, 111, 165, 0.1);
            color: #4a6fa5;
        }
        
        .stat-card:nth-child(2) .stat-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .stat-card:nth-child(3) .stat-icon {
            background-color: rgba(255, 112, 67, 0.1);
            color: #ff7043;
        }
        
        .stat-card:nth-child(4) .stat-icon {
            background-color: rgba(102, 187, 106, 0.1);
            color: #66bb6a;
        }
        
        .stat-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #6c757d;
        }
        
        .stat-info p {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: #333;
        }
        
        .dashboard-sections {
            margin-top: 30px;
        }
        
        .section-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .dashboard-section {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .section-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #4a6fa5;
            margin: 0;
        }
        
        .view-all {
            color: #6c757d;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .view-all:hover {
            color: #4a6fa5;
        }
        
        .vocab-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .vocab-card {
            height: 120px;
            perspective: 1000px;
            cursor: pointer;
        }
        
        .vocab-front, .vocab-back {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 10px;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.6s ease;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .vocab-front {
            background: linear-gradient(135deg, #4a6fa5, #6a8cbe);
            color: white;
            font-weight: 600;
            transform: rotateY(0deg);
        }
        
        .vocab-back {
            background: white;
            color: #333;
            transform: rotateY(180deg);
            border: 2px solid #4a6fa5;
        }
        
        .vocab-card:hover .vocab-front {
            transform: rotateY(180deg);
        }
        
        .vocab-card:hover .vocab-back {
            transform: rotateY(0deg);
        }
        
        .streak-calendar {
            text-align: center;
        }
        
        .streak-week {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .streak-day {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f1f3f5;
            color: #6c757d;
            font-weight: 600;
            position: relative;
        }
        
        .streak-day.active {
            background-color: #4a6fa5;
            color: white;
        }
        
        .streak-day.today {
            border: 2px solid #ff7043;
        }
        
        .streak-day.today:after {
            content: '';
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #ff7043;
        }
        
        #interactive-avatar-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        #interactive-avatar {
            width: 300px;
            height: auto;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        #interactive-avatar:hover {
            transform: scale(1.05);
        }
        
        .speech-bubble {
            position: absolute;
            bottom: 230px;
            right: 50px;
            width: 280px;
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .speech-bubble:after {
            content: '';
            position: absolute;
            bottom: -10px;
            right: 30px;
            width: 20px;
            height: 20px;
            background: white;
            transform: rotate(45deg);
            box-shadow: 5px 5px 10px rgba(0,0,0,0.1);
        }
        
        .speech-bubble.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .speech-bubble h4 {
            color: #4a6fa5;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .speech-bubble p {
            margin: 8px 0;
            font-size: 0.9rem;
            color: #555;
        }
        
        .speech-bubble i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
            color: #4a6fa5;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .section-row {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            #interactive-avatar {
                width: 60px;
                height: 60px;
            }
            
            .speech-bubble {
                width: 250px;
            }
        }
        
        /* Quick action buttons */
        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0;
        }
        
        .action-button {
            background: #4a6fa5;
            color: white;
            border: none;
            border-radius: 30px;
            padding: 12px 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(74, 111, 165, 0.2);
        }
        
        .action-button:hover {
            background: #3a5a8a;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(74, 111, 165, 0.3);
        }
        
        .action-button i {
            margin-right: 8px;
        }
        
        .action-button.practice {
            background: #ff7043;
            box-shadow: 0 4px 10px rgba(255, 112, 67, 0.2);
        }
        
        .action-button.practice:hover {
            background: #e8603a;
            box-shadow: 0 6px 15px rgba(255, 112, 67, 0.3);
        }
        
        .action-button.conversation {
            background: #66bb6a;
            box-shadow: 0 4px 10px rgba(102, 187, 106, 0.2);
        }
        
        .action-button.conversation:hover {
            background: #56a75a;
            box-shadow: 0 6px 15px rgba(102, 187, 106, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar ipad-style">
        <div class="container">
            <div class="logo">
                 <a href="index.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <img src="img/Generiertes Bild.jpeg" alt="CerveLingua Logo">
                    <span>CerveLingua</span>
                 </a>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="lessons.php" class="nav-link">Lessons</a>
                <a href="practice.php" class="nav-link">Practice</a>
                <a href="ai-conversation.php" class="nav-link">AI Conversation</a>
                <a href="camera-learning.php" class="nav-link">Visual Learning</a>
            </div>
            <div class="cta-buttons user-menu-container">
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

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-header">
                <br>
                <br>
                <br>
                <h1>¡Bienvenido, <?php echo htmlspecialchars($user['first_name'] ? $user['first_name'] : $user['username']); ?>!</h1>
                <p>Want to learn spanish? Well let's go then! Get started NOW!</p>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Current Level</h3>
                        <p><?php echo $level['level_name'] ?? 'Beginner'; ?> (<?php echo $level['level_code'] ?? 'A1'; ?>)</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3>XP Points</h3>
                        <p><?php echo $progress['xp_total']; ?></p>
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
                        <p><?php echo isset($progress['minutes_learned']) ? floor($progress['minutes_learned'] / 60) : 0; ?> hours</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Action Buttons -->
            <div class="quick-actions">
                <a href="lessons.php" class="action-button">
                    <i class="fas fa-book"></i> Continue Learning
                </a>
                <a href="practice.php" class="action-button practice">
                    <i class="fas fa-dumbbell"></i> Practice
                </a>
                <a href="ai-conversation.php" class="action-button conversation">
                    <i class="fas fa-comments"></i> AI Conversation
                </a>
            </div>
            
            <div class="dashboard-sections">
                <div class="section-row">
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2><i class="fas fa-language"></i> Vocabulary Review</h2>
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
                            <h2><i class="fas fa-calendar-check"></i> Learning Streak</h2>
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
                                <p>You're on a <strong><?php echo $progress['streak_days']; ?>-day streak</strong>! Keep it up!</p>
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
            <p><i class="fas fa-trophy"></i> Level: <?php echo $level['level_name'] ?? 'Beginner'; ?> (<?php echo $level['level_code'] ?? 'A1'; ?>)</p>
            <p><i class="fas fa-star"></i> XP Points: <?php echo $progress['xp_total']; ?></p>
            <p><i class="fas fa-fire"></i> Streak: <?php echo $progress['streak_days']; ?> days</p>
            <p><i class="fas fa-clock"></i> Study Time: <?php echo isset($progress['minutes_learned']) ? floor($progress['minutes_learned'] / 60) : 0; ?> hours</p>
            <p><i class="fas fa-calendar-check"></i> Last Activity: <?php echo isset($progress['last_activity_date']) && !empty($progress['last_activity_date']) ? date('M d, Y', strtotime($progress['last_activity_date'])) : date('M d, Y'); ?></p>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Avatar Interaction Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const avatar = document.getElementById('interactive-avatar');
            const speechBubble = document.getElementById('avatar-speech-bubble');
            let bubbleVisible = false;
            
            // Flip vocabulary cards on mobile
            const vocabCards = document.querySelectorAll('.vocab-card');
            vocabCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.classList.toggle('flipped');
                });
            });
            
            // Show speech bubble when avatar is clicked
            avatar.addEventListener('click', function(e) {
                e.stopPropagation();
                
                if (bubbleVisible) {
                    speechBubble.classList.remove('show');
                } else {
                    speechBubble.innerHTML = `
                        <h4>Your Learning Stats</h4>
                        <p><i class="fas fa-trophy"></i> Level: <?php echo $level['level_name'] ?? 'Beginner'; ?> (<?php echo $level['level_code'] ?? 'A1'; ?>)</p>
                        <p><i class="fas fa-star"></i> XP Points: <?php echo $progress['xp_total']; ?></p>
                        <p><i class="fas fa-fire"></i> Streak: <?php echo $progress['streak_days']; ?> days</p>
                        <p><i class="fas fa-clock"></i> Study Time: <?php echo isset($progress['minutes_learned']) ? floor($progress['minutes_learned'] / 60) : 0; ?> hours</p>
                        <p><i class="fas fa-calendar-check"></i> Last Activity: <?php echo isset($progress['last_activity_date']) && !empty($progress['last_activity_date']) ? date('M d, Y', strtotime($progress['last_activity_date'])) : date('M d, Y'); ?></p>
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
            
            // Show welcome message on page load
            setTimeout(function() {
                speechBubble.innerHTML = `
                    <h4>¡Hola, <?php echo htmlspecialchars($user['first_name'] ? $user['first_name'] : $user['username']); ?>!</h4>
                    <p><i class="fas fa-fire"></i> You're on a <?php echo $progress['streak_days']; ?>-day streak!</p>
                    <p><i class="fas fa-star"></i> You have <?php echo $progress['xp_total']; ?> XP points</p>
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
    <script src="js/user-dropdown.js"></script>
    <script src="js/dashboard.js"></script>
    <script src="js/mobile-nav.js"></script>
</body>
</html>
