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
    
    // Fetch games from the database with error handling
    try {
        // Simplified query to fetch all games
        $games_stmt = $pdo->prepare("
            SELECT * FROM minigames
        ");
        $games_stmt->execute();
        $games = $games_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Now fetch play count for each game
        if (!empty($games)) {
            foreach ($games as $key => $game) {
                $play_count_stmt = $pdo->prepare("
                    SELECT COUNT(*) as played_count 
                    FROM user_game_progress 
                    WHERE game_id = :game_id AND user_id = :user_id
                ");
                $play_count_stmt->bindParam(':game_id', $game['game_id']);
                $play_count_stmt->bindParam(':user_id', $user_id);
                $play_count_stmt->execute();
                $play_count = $play_count_stmt->fetch(PDO::FETCH_ASSOC);
                
                $games[$key]['played_count'] = $play_count['played_count'] ?? 0;
            }
        }
        
        // Debug information
        error_log("Games query executed. Found " . count($games) . " games.");
        if (count($games) > 0) {
            error_log("First game: " . print_r($games[0], true));
        }
    } catch (PDOException $e) {
        error_log("Error fetching games: " . $e->getMessage());
        $games = [];
    }
    
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
    $games = [];
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
    <link rel="stylesheet" href="css/games.css">
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
            
            <!-- Games Section with Enhanced Design -->
            <div class="games-section">
                <h2>Immersive Spanish Learning Games</h2>
                <p>Practice your Spanish with these engaging interactive games</p>
                
                <div class="games-grid">
                    <!-- Palabra Arena -->
                    <div class="game-card" id="game-1">
                        <div class="game-card-inner">
                            <div class="game-image-container">
                                <img src="img/games/2_palabra_arena.webp" alt="Palabra Arena" class="game-image">
                                <div class="game-difficulty">
                                    <span>Level 2</span>
                                </div>
                            </div>
                            <div class="game-info">
                                <h3>Palabra Arena</h3>
                                <p>Battle enemies by correctly translating or conjugating Spanish words. Different enemy types require different language skills to defeat.</p>
                                <div class="game-meta">
                                    <span class="xp-badge"><i class="fas fa-star"></i> 75 XP</span>
                                </div>
                            </div>
                            <div class="game-action">
                                <button class="btn btn-primary start-game" data-game="palabraArena" data-id="1">
                                    <i class="fas fa-gamepad"></i> Play Now
                                </button>
                            </div>
                        </div>
                        
                        <!-- Carrera de Conjugación -->
                        <div class="game-card" id="game-2">
                            <div class="game-card-inner">
                                <div class="game-image-container">
                                    <img src="img/games/carrera_de_conjugaccón.png" alt="Carrera de Conjugación" class="game-image">
                                    <div class="game-difficulty">
                                        <span>Level 3</span>
                                    </div>
                                </div>
                                <div class="game-info">
                                    <h3>Carrera de Conjugación</h3>
                                    <p>Race against time by quickly conjugating verbs. Different tracks represent different tenses, and obstacles require specific vocabulary.</p>
                                    <div class="game-meta">
                                        <span class="xp-badge"><i class="fas fa-star"></i> 100 XP</span>
                                    </div>
                                </div>
                                <div class="game-action">
                                    <button class="btn btn-primary start-game" data-game="carreraConjugacion" data-id="2">
                                        <i class="fas fa-gamepad"></i> Play Now
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- El Camino del Toro -->
                        <div class="game-card" id="game-3">
                            <div class="game-card-inner">
                                <div class="game-image-container">
                                    <img src="img/games/el_camino_del_toro.png" alt="El Camino del Toro" class="game-image">
                                    <div class="game-difficulty">
                                        <span>Level 4</span>
                                    </div>
                                </div>
                                <div class="game-info">
                                    <h3>El Camino del Toro</h3>
                                    <p>Navigate through a Spanish town, completing quests by speaking with NPCs in Spanish. Each interaction requires understanding and responding in Spanish.</p>
                                    <div class="game-meta">
                                        <span class="xp-badge"><i class="fas fa-star"></i> 150 XP</span>
                                    </div>
                                </div>
                                <div class="game-action">
                                    <button class="btn btn-primary start-game" data-game="caminoToro" data-id="3">
                                        <i class="fas fa-gamepad"></i> Play Now
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Isla de Verbos -->
                        <div class="game-card" id="game-4">
                            <div class="game-card-inner">
                                <div class="game-image-container">
                                    <img src="img/games/isla_de_verbos.webp" alt="Isla de Verbos" class="game-image">
                                    <div class="game-difficulty">
                                        <span>Level 3</span>
                                    </div>
                                </div>
                                <div class="game-info">
                                    <h3>Isla de Verbos</h3>
                                    <p>Build a civilization on an island where each building requires correctly conjugated verbs. Interact with island inhabitants using contextual Spanish conversation.</p>
                                    <div class="game-meta">
                                        <span class="xp-badge"><i class="fas fa-star"></i> 125 XP</span>
                                    </div>
                                </div>
                                <div class="game-action">
                                    <button class="btn btn-primary start-game" data-game="islaVerbos" data-id="4">
                                        <i class="fas fa-gamepad"></i> Play Now
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mercado Misterioso -->
                        <div class="game-card" id="game-5">
                            <div class="game-card-inner">
                                <div class="game-image-container">
                                    <img src="img/games/mercadO_misterioso.png" alt="Mercado Misterioso" class="game-image">
                                    <div class="game-difficulty">
                                        <span>Level 2</span>
                                    </div>
                                </div>
                                <div class="game-info">
                                    <h3>Mercado Misterioso</h3>
                                    <p>Navigate a Spanish market, haggling with vendors and solving mysteries by using correct Spanish vocabulary and phrases.</p>
                                    <div class="game-meta">
                                        <span class="xp-badge"><i class="fas fa-star"></i> 100 XP</span>
                                    </div>
                                </div>
                                <div class="game-action">
                                    <button class="btn btn-primary start-game" data-game="mercadoMisterioso" data-id="5">
                                        <i class="fas fa-gamepad"></i> Play Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lessons Section -->
            <div class="lessons-section">
                <h2>Available Lessons</h2>
                <p>Master Spanish with our structured lessons</p>
                
                <div class="lessons-grid">
                    <?php if (empty($lessons)): ?>
                    <div class="no-lessons-message">
                        <p>No lessons available for your current level. Please contact support.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($lessons as $index => $lesson): ?>
                        <div class="lesson-card <?php echo $lesson['completed'] > 0 ? 'completed' : ''; ?> <?php echo $index === 0 || $lessons[$index-1]['completed'] > 0 ? 'unlocked' : 'locked'; ?>">
                            <div class="lesson-status">
                                <?php if ($lesson['completed'] > 0): ?>
                                <i class="fas fa-check-circle"></i>
                                <?php elseif ($index === 0 || $lessons[$index-1]['completed'] > 0): ?>
                                <i class="fas fa-unlock"></i>
                                <?php else: ?>
                                <i class="fas fa-lock"></i>
                                <?php endif; ?>
                            </div>
                            <div class="lesson-info">
                                <h3><?php echo htmlspecialchars($lesson['lesson_title']); ?></h3>
                                <p><?php echo htmlspecialchars($lesson['description']); ?></p>
                                <div class="lesson-meta">
                                    <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($lesson['estimated_time'] ?? '10'); ?> min</span>
                                    <span><i class="fas fa-star"></i> <?php echo htmlspecialchars($lesson['xp_reward'] ?? '50'); ?> XP</span>
                                </div>
                            </div>
                            <div class="lesson-action">
                                <?php if ($lesson['completed'] > 0): ?>
                                <button class="btn btn-secondary start-lesson" data-lesson="<?php echo htmlspecialchars($lesson['lesson_id']); ?>">Review</button>
                                <?php elseif ($index === 0 || $lessons[$index-1]['completed'] > 0): ?>
                                <button class="btn btn-primary start-lesson" data-lesson="<?php echo htmlspecialchars($lesson['lesson_id']); ?>">Start</button>
                                <?php else: ?>
                                <button class="btn btn-disabled" disabled>Locked</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Game Modal -->
    <div class="modal" id="game-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h2 id="game-title">Game Title</h2>
            </div>
            <div class="modal-body" id="game-container">
                <!-- Game content will be loaded here -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading game...</p>
                </div>
                <!-- Avatar container for CerveLingua character -->
                <div class="avatar-container">
                    <div class="speech-bubble" id="cerve-speech">
                        <p>Ready to play? Let's practice your Spanish!</p>
                    </div>
                    <img src="img/CerveLingua_Avatar.png" alt="CerveLingua Avatar" id="cerve-avatar">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lesson Modal -->
    <div class="modal" id="lesson-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h2 id="lesson-title">Lesson Title</h2>
            </div>
            <div class="modal-body" id="lesson-container">
                <!-- Lesson content will be loaded here -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading lesson...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Game buttons
        const gameButtons = document.querySelectorAll('.start-game');
        const gameModal = document.getElementById('game-modal');
        const gameTitle = document.getElementById('game-title');
        const gameContainer = document.getElementById('game-container');
        
        // Lesson buttons
        const lessonButtons = document.querySelectorAll('.start-lesson');
        const lessonModal = document.getElementById('lesson-modal');
        const lessonTitle = document.getElementById('lesson-title');
        const lessonContainer = document.getElementById('lesson-container');
        
        // Close buttons
        const closeButtons = document.querySelectorAll('.close-modal');
        
        // Open game modal
        gameButtons.forEach(button => {
            button.addEventListener('click', function() {
                const gameCode = this.getAttribute('data-game');
                const gameId = this.getAttribute('data-id');
                const gameName = this.closest('.game-card').querySelector('h3').textContent;
                
                gameTitle.textContent = gameName;
                gameModal.style.display = 'block';
                
                // Load game content
                fetch(`games/${gameCode}.php?game_id=${gameId}`)
                    .then(response => response.text())
                    .then(html => {
                        gameContainer.innerHTML = html;
                        
                        // Add CerveLingua avatar back after loading game content
                        const avatarContainer = document.createElement('div');
                        avatarContainer.className = 'avatar-container';
                        avatarContainer.innerHTML = `
                            <div class="speech-bubble" id="cerve-speech">
                                <p>Let's practice your Spanish with this fun game!</p>
                            </div>
                            <img src="img/CerveLingua_Avatar.png" alt="CerveLingua Avatar" id="cerve-avatar">
                        `;
                        gameContainer.appendChild(avatarContainer);
                        
                        // Start tracking time for XP calculation
                        sessionStorage.setItem('gameStartTime', Date.now());
                        sessionStorage.setItem('currentGameId', gameId);
                    })
                    .catch(error => {
                        console.error('Error loading game:', error);
                        gameContainer.innerHTML = `
                            <div class="error-message">
                                <p>Error loading game. Please try again later.</p>
                            </div>
                            <div class="avatar-container">
                                <div class="speech-bubble" id="cerve-speech">
                                    <p>Oops! Something went wrong. Let's try again later.</p>
                                </div>
                                <img src="img/CerveLingua_Avatar.png" alt="CerveLingua Avatar" id="cerve-avatar">
                            </div>
                        `;
                    });
            });
        });
        
        // Open lesson modal
        lessonButtons.forEach(button => {
            button.addEventListener('click', function() {
                const lessonId = this.getAttribute('data-lesson');
                const lessonName = this.closest('.lesson-card').querySelector('h3').textContent;
                
                lessonTitle.textContent = lessonName;
                lessonModal.style.display = 'block';
                
                // Load lesson content
                fetch(`get_lesson.php?lesson_id=${lessonId}`)
                    .then(response => response.text())
                    .then(html => {
                        lessonContainer.innerHTML = html;
                        
                        // Start tracking time for XP calculation
                        sessionStorage.setItem('lessonStartTime', Date.now());
                        sessionStorage.setItem('currentLessonId', lessonId);
                    })
                    .catch(error => {
                        console.error('Error loading lesson:', error);
                        lessonContainer.innerHTML = '<div class="error-message"><p>Error loading lesson. Please try again later.</p></div>';
                    });
            });
        });
        
        // Close modals
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                modal.style.display = 'none';
                
                // Save progress when closing
                const gameId = sessionStorage.getItem('currentGameId');
                const lessonId = sessionStorage.getItem('currentLessonId');
                const gameStartTime = sessionStorage.getItem('gameStartTime');
                const lessonStartTime = sessionStorage.getItem('lessonStartTime');
                
                if (gameId && gameStartTime) {
                    const timeSpent = Math.floor((Date.now() - gameStartTime) / 1000);
                    if (timeSpent > 10) { // Only save if spent more than 10 seconds
                        saveGameProgress(gameId, timeSpent);
                    }
                    sessionStorage.removeItem('currentGameId');
                    sessionStorage.removeItem('gameStartTime');
                }
                
                if (lessonId && lessonStartTime) {
                    const timeSpent = Math.floor((Date.now() - lessonStartTime) / 1000);
                    if (timeSpent > 10) { // Only save if spent more than 10 seconds
                        saveLessonProgress(lessonId, timeSpent);
                    }
                    sessionStorage.removeItem('currentLessonId');
                    sessionStorage.removeItem('lessonStartTime');
                }
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === gameModal) {
                gameModal.style.display = 'none';
            }
            if (event.target === lessonModal) {
                lessonModal.style.display = 'none';
            }
        });
        
        // Save game progress
        function saveGameProgress(gameId, timeSpent) {
            fetch('save_game_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `game_id=${gameId}&time_spent=${timeSpent}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Game progress saved successfully');
                    if (data.xp_earned > 0) {
                        showXPNotification(data.xp_earned);
                    }
                } else {
                    console.error('Error saving game progress:', data.message);
                }
            })
            .catch(error => {
                console.error('Error saving game progress:', error);
            });
        }
        
        // Save lesson progress
        function saveLessonProgress(lessonId, timeSpent) {
            fetch('save_lesson_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `lesson_id=${lessonId}&time_spent=${timeSpent}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Lesson progress saved successfully');
                    if (data.xp_earned > 0) {
                        showXPNotification(data.xp_earned);
                    }
                } else {
                    console.error('Error saving lesson progress:', data.message);
                }
            })
            .catch(error => {
                console.error('Error saving lesson progress:', error);
            });
        }
        
        // Show XP notification
        function showXPNotification(xpEarned) {
            const notification = document.createElement('div');
            notification.className = 'xp-notification';
            notification.innerHTML = `<p>+${xpEarned} XP earned!</p>`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 3000);
        }
    });
    // Book navigation
document.addEventListener('DOMContentLoaded', function() {
    const bookPages = document.querySelector('.book-pages');
    const prevBtn = document.querySelector('.prev-page');
    const nextBtn = document.querySelector('.next-page');
    
    if (bookPages && prevBtn && nextBtn) {
        // Next page button
        nextBtn.addEventListener('click', function() {
            bookPages.scrollBy({
                left: 330,
                behavior: 'smooth'
            });
        });
        
        // Previous page button
        prevBtn.addEventListener('click', function() {
            bookPages.scrollBy({
                left: -330,
                behavior: 'smooth'
            });
        });
    }
});
    </script>
</body>
</html>