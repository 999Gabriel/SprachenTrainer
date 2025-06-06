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
    
    // If no progress record exists, create one
    if (!$progress) {
        // Insert default progress record
        $insert_stmt = $pdo->prepare("
            INSERT INTO user_progress 
            (user_id, current_level_id, xp_total, streak_days, minutes_learned, last_activity_date) 
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
                'current_level_id' => 1,
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
    $level_stmt->bindParam(':level_id', $progress['current_level_id']);
    $level_stmt->execute();
    $level = $level_stmt->fetch(PDO::FETCH_ASSOC);
    
    // If level not found, use default
    if (!$level) {
        $level = [
            'level_id' => 1,
            'level_number' => 1,
            'xp_required' => 0,
            'title' => 'Pollito („Küken")',
            'badge_url' => '/img/pollito.png',
            'description' => 'Noch ganz am Anfang – aber voller Sprachpotenzial!',
            'emoji' => '🐣'
        ];
    }
    
    // Get next level XP requirement
    $next_level_stmt = $pdo->prepare("
        SELECT xp_required FROM levels 
        WHERE level_number > :current_level_number 
        ORDER BY level_number ASC LIMIT 1
    ");
    $next_level_stmt->bindParam(':current_level_number', $level['level_number']);
    $next_level_stmt->execute();
    $next_level = $next_level_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$next_level) {
        // If no next level (user is at max level), set a higher target
        $next_level = ['xp_required' => $level['xp_required'] + 25000];
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
    
    // Get all levels for progression display
    $all_levels_stmt = $pdo->prepare("SELECT level_id, level_number, title, emoji FROM levels ORDER BY level_number");
    $all_levels_stmt->execute();
    $all_levels = $all_levels_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Level-specific colors based on level number
    $level_colors = [
        1 => ['primary' => '#FFD166', 'secondary' => '#FFF7E6', 'accent' => '#F4B942'], // Pollito - Yellow
        2 => ['primary' => '#A7C957', 'secondary' => '#F1F7E7', 'accent' => '#6A994E'], // Torete - Green
        3 => ['primary' => '#FF8C42', 'secondary' => '#FFF1E6', 'accent' => '#E76F51'], // Zorro - Orange
        4 => ['primary' => '#577590', 'secondary' => '#E6F0F9', 'accent' => '#43AA8B'], // Lince - Blue
        5 => ['primary' => '#D62828', 'secondary' => '#FFEBEB', 'accent' => '#9E2A2B'], // Toro - Red
        6 => ['primary' => '#6D597A', 'secondary' => '#F0EBF4', 'accent' => '#355070'], // Matador - Purple
        7 => ['primary' => '#000000', 'secondary' => '#E6E6E6', 'accent' => '#495057'], // Pantera - Black
        8 => ['primary' => '#3A86FF', 'secondary' => '#E6F2FF', 'accent' => '#0077B6'], // Aguila - Blue
        9 => ['primary' => '#FCBF49', 'secondary' => '#FFF8E6', 'accent' => '#F77F00'], // Leon - Gold
        10 => ['primary' => '#D00000', 'secondary' => '#FFEBEB', 'accent' => '#9D0208']  // Toro Rojo - Red
    ];
    
    $current_level_number = $level['level_number'] ?? 1;
    $colors = $level_colors[$current_level_number] ?? $level_colors[1];
    
    // Calculate XP progress percentage
    $current_xp = $progress['xp_total'] ?? 0;
    $current_level_xp = $level['xp_required'] ?? 0;
    $next_level_xp = $next_level['xp_required'] ?? ($current_level_xp + 1000);
    
    $xp_needed = $next_level_xp - $current_xp;
    $xp_progress = $next_level_xp - $current_level_xp;
    $xp_progress_percent = 0;
    
    if ($xp_progress > 0) {
        $xp_progress_percent = min(100, max(0, (($current_xp - $current_level_xp) / $xp_progress) * 100));
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
        'xp_total' => 0,
        'streak_days' => 0,
        'minutes_learned' => 0,
        'total_study_time' => 0,
        'last_activity_date' => date('Y-m-d')
    ];
    
    $level = [
        'level_id' => 1,
        'level_number' => 1,
        'xp_required' => 0,
        'title' => 'Pollito („Küken")',
        'badge_url' => '/img/pollito.png',
        'description' => 'Noch ganz am Anfang – aber voller Sprachpotenzial!',
        'emoji' => '🐣'
    ];
    
    $next_level_xp = 1000;
    $current_xp = 0;
    $xp_needed = 1000;
    $xp_progress_percent = 0;
    
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
    
    $all_levels = [
        ['level_id' => 1, 'level_number' => 1, 'title' => 'Pollito („Küken")', 'emoji' => '🐣'],
        ['level_id' => 2, 'level_number' => 2, 'title' => 'Torete („junger Bulle")', 'emoji' => '🐮'],
        ['level_id' => 3, 'level_number' => 3, 'title' => 'Zorro Listo („Schlauer Fuchs")', 'emoji' => '🦊'],
        ['level_id' => 4, 'level_number' => 4, 'title' => 'Lince Lingüístico („Sprachluchs")', 'emoji' => '🐱‍👤'],
        ['level_id' => 5, 'level_number' => 5, 'title' => 'Toro Bravo („Kampfstier")', 'emoji' => '🐂'],
        ['level_id' => 6, 'level_number' => 6, 'title' => 'Matador de Errores („Fehler-Matador")', 'emoji' => '🗡'],
        ['level_id' => 7, 'level_number' => 7, 'title' => 'Pantera („Schwarzer Panther")', 'emoji' => '🐆'],
        ['level_id' => 8, 'level_number' => 8, 'title' => 'Águila del Español („Adler des Spanischen")', 'emoji' => '🦅'],
        ['level_id' => 9, 'level_number' => 9, 'title' => 'León de la Lengua („Löwe der Sprache")', 'emoji' => '🦁'],
        ['level_id' => 10, 'level_number' => 10, 'title' => 'El Toro Rojo („Der rote Bulle")', 'emoji' => '🔴🐃']
    ];
    
    $colors = ['primary' => '#FFD166', 'secondary' => '#FFF7E6', 'accent' => '#F4B942'];
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
        /* Dynamic level-specific styles */
        .level-card {
            background: linear-gradient(135deg, <?php echo $colors['secondary']; ?> 0%, #ffffff 100%);
            border-left: 5px solid <?php echo $colors['primary']; ?>;
        }
        .level-badge {
            border: 2px solid <?php echo $colors['primary']; ?>;
        }
        .level-icon {
            background-color: <?php echo $colors['secondary']; ?>;
            border: 3px solid <?php echo $colors['primary']; ?>;
        }
        .level-details h3 {
            color: <?php echo $colors['primary']; ?>;
        }
        .xp-fill {
            background: linear-gradient(90deg, <?php echo $colors['primary']; ?> 0%, <?php echo $colors['accent']; ?> 100%);
        }
        .current-xp, .xp-next-level {
            color: <?php echo $colors['primary']; ?>;
        }
        .level-step.current .level-number {
            background: <?php echo $colors['primary']; ?>;
            box-shadow: 0 0 0 3px <?php echo $colors['secondary']; ?>;
        }
        
        /* Dashboard theme based on level */
        .dashboard-header h1 {
            color: <?php echo $colors['primary']; ?>;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            border-top: 3px solid <?php echo $colors['primary']; ?>;
        }
        
        .stat-icon {
            color: <?php echo $colors['primary']; ?>;
            background-color: <?php echo $colors['secondary']; ?>;
        }
        
        .quick-actions .action-button {
            background: <?php echo $colors['primary']; ?>;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .quick-actions .action-button:hover {
            background: <?php echo $colors['accent']; ?>;
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
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container dashboard-container">
        <div class="dashboard-header">
            <br>
            <br>
            <br>
            <br>
            <h1>¡Hola, <?php echo htmlspecialchars($user['first_name'] ?: $user['username']); ?>!</h1>
            <p>Willkommen zurück bei deinem Spanisch-Abenteuer</p>
        </div>
        
        <!-- User Level Section -->
        <div class="dashboard-card level-card">
            <div class="level-header">
                <h2>Dein CerveLingua Level</h2>
            </div>
            
            <div class="level-info">
                <div class="level-badge">
                    <img src="<?php echo htmlspecialchars($level['badge_url']); ?>" alt="Level Icon" class="level-icon">
                    <div class="level-details">
                        <h3><?php echo htmlspecialchars($level['title']); ?> 
                            <span class="level-emoji"><?php echo $level['emoji'] ?? ''; ?></span>
                        </h3>
                        <p class="level-description"><?php echo htmlspecialchars($level['description'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="xp-progress">
                <div class="xp-bar">
                    <div class="xp-fill" style="width: <?php echo $xp_progress_percent; ?>%"></div>
                </div>
                <div class="xp-text">
                    <span class="current-xp"><?php echo number_format($current_xp); ?> XP</span>
                    <span class="next-level-xp"><?php echo number_format($next_level_xp); ?> XP</span>
                </div>
                <p class="xp-next-level">
                    <?php echo number_format($xp_needed); ?> XP bis zum nächsten Level
                </p>
            </div>
            
            <div class="level-system-info">
                <h4>🔟 CerveLingua – Dein Sprachabenteuer in 10 epischen Stufen</h4>
                <div class="level-progression">
                    <?php
                    $current_level_number = $level['level_number'] ?? 1;
                    
                    foreach ($all_levels as $lvl) {
                        $is_current = ($lvl['level_number'] == $current_level_number);
                        $is_completed = ($lvl['level_number'] < $current_level_number);
                        $class = $is_current ? 'current' : ($is_completed ? 'completed' : 'future');
                        
                        echo '<div class="level-step ' . $class . '">';
                        echo '<span class="level-number">' . $lvl['level_number'] . '</span>';
                        echo '<span class="level-emoji">' . ($lvl['emoji'] ?? '') . '</span>';
                        echo '</div>';
                        
                        if ($lvl['level_number'] < 10) {
                            echo '<div class="level-connector ' . ($is_completed ? 'completed' : '') . '"></div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-info">
                    <h3>Aktuelles Level</h3>
                    <p><?php echo htmlspecialchars($level['title']); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3>XP Gesamt</h3>
                    <p><?php echo number_format($progress['xp_total']); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-info">
                    <h3>Streak</h3>
                    <p><?php echo $progress['streak_days']; ?> Tage</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Lernzeit</h3>
                    <p><?php echo $progress['total_study_time'] ?? $progress['minutes_learned'] ?? 0; ?> Min</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="camera-learning.php" class="action-button">
                <i class="fas fa-book"></i> Lernen mit Kamera
            </a>
            <a href="practice.php" class="action-button practice">
                <i class="fas fa-dumbbell"></i> Üben
            </a>
            <a href="ai-conversation.php" class="action-button conversation">
                <i class="fas fa-comments"></i> Konversation
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        </footer>
        
        <script>
            // Toggle user dropdown menu
            document.addEventListener('DOMContentLoaded', function() {
                const userProfile = document.querySelector('.user-profile');
                if (userProfile) {
                    userProfile.addEventListener('click', function(e) {
                        e.stopPropagation();
                        this.classList.toggle('active');
                        const dropdownMenu = this.querySelector('.dropdown-menu');
                        if (dropdownMenu) {
                            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
                        }
                    });
                    
                    // Close dropdown when clicking outside
                    document.addEventListener('click', function() {
                        const dropdownMenu = document.querySelector('.dropdown-menu');
                        if (dropdownMenu) {
                            dropdownMenu.style.display = 'none';
                        }
                        userProfile.classList.remove('active');
                    });
                }
                
                // Prevent dropdown from closing when clicking inside it
                const dropdownMenu = document.querySelector('.dropdown-menu');
                if (dropdownMenu) {
                    dropdownMenu.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                }
            });
        </script>
    </body>
    </html>
    <script>
        // Vocabulary card flip functionality
        document.querySelectorAll('.vocab-card').forEach(card => {
            card.addEventListener('click', () => {
                card.classList.toggle('flipped');
            });
        });
    </script>
</body>
</html>