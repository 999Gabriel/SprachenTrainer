<?php
/**
 * lessons.php
 *
 * Anzeige der Spanisch-Lektionen und interaktiven Lernspiele.
 * Beim Klick auf “Play” öffnet sich ein MVP-Modal mit Spielbeschreibung,
 * Avatar und einem Button, der zum eigentlichen Spiel weiterleitet.
 *
 * Coding-Standards:
 *   • camelCase für Variablen/Funktionen, PascalCase für Klassen
 *   • Deutsch für selbst definierte Bezeichnungen
 *   • Einrückung: 4 Leerzeichen
 *   • Logikblöcke durch Leerzeilen trennen
 */

require_once "includes/config.php";
require_once "includes/functions.php";

// Prüfe, ob Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header("Location: login.php?error=invalid_user");
        exit;
    }

    // Fetch user_progress
    $progressStmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :user_id");
    $progressStmt->bindParam(':user_id', $user_id);
    $progressStmt->execute();
    $progress = $progressStmt->fetch();

    if (!$progress) {
        $progress = [
            'current_level_id'   => 2,
            'xp_points'          => 0,
            'streak_days'        => 0,
            'total_study_time'   => 0,
            'last_activity_date' => date('Y-m-d'),
        ];
    }

    // Fetch current level
    $levelStmt = $pdo->prepare("
        SELECT pl.* 
        FROM proficiency_levels pl
        JOIN user_progress up ON pl.level_id = up.current_level_id
        WHERE up.user_id = :user_id
    ");
    $levelStmt->bindParam(':user_id', $user_id);
    $levelStmt->execute();
    $currentLevel = $levelStmt->fetch();

    if (!$currentLevel) {
        $levelStmt = $pdo->prepare("SELECT * FROM proficiency_levels WHERE level_code = 'A1'");
        $levelStmt->execute();
        $currentLevel = $levelStmt->fetch();
    }

    // Fetch lessons for user's level
    $lessonsStmt = $pdo->prepare("
        SELECT l.*,
               (SELECT COUNT(*) FROM lesson_completions lc 
                WHERE lc.lesson_id = l.lesson_id 
                  AND lc.user_id = :user_id) AS completed
        FROM lessons l
        WHERE l.level_id = :level_id
        ORDER BY l.lesson_order
    ");
    $lessonsStmt->bindParam(':user_id', $user_id);
    $lessonsStmt->bindParam(':level_id', $currentLevel['level_id']);
    $lessonsStmt->execute();
    $lessons = $lessonsStmt->fetchAll();

    // Fetch games
    try {
        $gamesStmt = $pdo->prepare("SELECT * FROM minigames");
        $gamesStmt->execute();
        $games = $gamesStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($games)) {
            foreach ($games as $key => $game) {
                $countStmt = $pdo->prepare("
                    SELECT COUNT(*) AS played_count
                    FROM user_game_progress
                    WHERE game_id = :game_id
                      AND user_id  = :user_id
                ");
                $countStmt->bindParam(':game_id', $game['game_id']);
                $countStmt->bindParam(':user_id', $user_id);
                $countStmt->execute();
                $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
                $games[$key]['played_count'] = $countRow['played_count'] ?? 0;
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching games: " . $e->getMessage());
        $games = [];
    }

} catch (PDOException $e) {
    error_log("Lessons page error: " . $e->getMessage());
    // Default fallback
    $user = [
        'username'     => $_SESSION['username'] ?? 'User',
        'first_name'   => '',
        'profile_image'=> ''
    ];

    $progress = [
        'current_level_id' => 2,
        'xp_points'        => 0,
        'streak_days'      => 0,
        'total_study_time' => 0
    ];

    $currentLevel = [
        'level_id'   => 2,
        'level_code' => 'A1',
        'level_name' => 'Beginner'
    ];

    $lessons = [];
    $games   = [];
}

$page_title = "Spanish Lessons";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CerveLingua - Spanish Lessons</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="css/dashboard.css" />
    <link rel="stylesheet" href="css/lessons.css" />
    <link rel="stylesheet" href="css/games.css" />
    <link rel="stylesheet" href="css/game-cards.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <!-- Zusätzliche CSS-Regeln für Modal & Avatar -->
    <style>
        /* Modal-Grundstruktur */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fff;
            margin: 60px auto;
            border-radius: 8px;
            padding: 20px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: relative;
        }

        .close-modal {
            color: #333;
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-header {
            border-bottom: 1px solid #e2e2e2;
            margin-bottom: 16px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }

        .modal-body {
            max-height: 400px;
            overflow-y: auto;
        }

        .loading-spinner {
            text-align: center;
            margin: 40px 0;
        }

        .loading-spinner i {
            font-size: 32px;
            color: #888;
        }

        /* Avatar-Container im Modal */
        .avatar-container {
            position: absolute;
            bottom: 16px;
            right: 16px;
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .speech-bubble {
            background: #f1f1f1;
            border-radius: 10px;
            padding: 10px;
            margin-right: 8px;
            max-width: 160px;
            font-size: 14px;
            color: #333;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .speech-bubble p {
            margin: 0;
        }

        #cerve-avatar {
            width: 60px;
            height: auto;
        }

        /* Spiel-Details */
        .game-details {
            margin-bottom: 20px;
        }

        .game-details h3 {
            margin-top: 0;
            color: #34495e;
        }

        .game-details p {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
        }

        .play-now-btn {
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include 'includes/nav.php'; ?>

    <!-- Lessons Content -->
    <div class="lessons-container">
        <div class="container">
            <div class="page-header">
                <br /><br /><br /><br />
                <h1>Spanish Lessons</h1>
                <p>
                    Level:
                    <?php
                        echo htmlspecialchars(
                            $currentLevel['level_code'] . ' - ' . $currentLevel['level_name']
                        );
                    ?>
                </p>
            </div>

            <div class="level-progress">
                <div class="progress-bar">
                    <?php
                        $completedCount      = 0;
                        foreach ($lessons as $lesson) {
                            if ($lesson['completed'] > 0) {
                                $completedCount++;
                            }
                        }
                        $totalLessons        = count($lessons);
                        $completionPercent   = $totalLessons > 0
                            ? ($completedCount / $totalLessons) * 100
                            : 0;
                    ?>
                    <div
                      class="progress-fill"
                      style="width: <?php echo $completionPercent; ?>%"
                    ></div>
                </div>
                <div class="progress-stats">
                    <span><?php echo $completedCount; ?>/<?php echo $totalLessons; ?> lessons completed</span>
                    <span><?php echo round($completionPercent); ?>% complete</span>
                </div>
            </div>

            <!-- Games Section -->
            <div class="games-section">
                <h2>Immersive Spanish Learning Games</h2>
                <p>Practice your Spanish with these engaging interactive games</p>

                <div class="games-grid">
                    <!-- Speed Typing Challenge -->
                    <div class="game-card" id="game-6">
                        <div class="game-card-inner">
                            <div class="game-image-container">
                                <img
                                  src="img/games/speedTyping.webp"
                                  alt="Speed Typing Challenge"
                                  class="game-image"
                                />
                                <div class="game-difficulty">
                                    <span>Level 2</span>
                                </div>
                            </div>
                            <div class="game-content">
                                <div class="game-info">
                                    <h3>Speed Typing Challenge</h3>
                                    <p>
                                        Test your Spanish typing speed and accuracy! Type Spanish words and phrases as quickly as possible.
                                    </p>
                                </div>
                                <div class="game-footer">
                                    <span class="xp-badge"
                                      ><i class="fas fa-star"></i> 100 XP</span
                                    >
                                    <!-- Öffnet das Modal -->
                                    <button
                                      class="btn btn-primary start-game"
                                      data-game="speedTyping"
                                    >
                                        <i class="fas fa-gamepad"></i> Play
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sentence Builder -->
                    <div class="game-card" id="game-7">
                        <div class="game-card-inner">
                            <div class="game-image-container">
                                <img
                                  src="img/games/scentenceBuilder.webp"
                                  alt="Sentence Builder"
                                  class="game-image"
                                />
                                <div class="game-difficulty">
                                    <span>Level 3</span>
                                </div>
                            </div>
                            <div class="game-content">
                                <div class="game-info">
                                    <h3>Sentence Builder</h3>
                                    <p>
                                        Build correct Spanish sentences by arranging words in the proper order. Test your understanding of Spanish grammar!
                                    </p>
                                </div>
                                <div class="game-footer">
                                    <span class="xp-badge"
                                      ><i class="fas fa-star"></i> 125 XP</span
                                    >
                                    <!-- Öffnet das Modal -->
                                    <button
                                      class="btn btn-primary start-game"
                                      data-game="sentenceBuilder"
                                    >
                                        <i class="fas fa-gamepad"></i> Play
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
                        <div
                          class="lesson-card <?php echo ($lesson['completed'] > 0) ? 'completed' : ''; ?>
                                             <?php echo ($index === 0 || $lessons[$index - 1]['completed'] > 0) ? 'unlocked' : 'locked'; ?>"
                        >
                            <div class="lesson-status">
                                <?php if ($lesson['completed'] > 0): ?>
                                <i class="fas fa-check-circle"></i>
                                <?php elseif ($index === 0 || $lessons[$index - 1]['completed'] > 0): ?>
                                <i class="fas fa-unlock"></i>
                                <?php else: ?>
                                <i class="fas fa-lock"></i>
                                <?php endif; ?>
                            </div>
                            <div class="lesson-info">
                                <h3><?php echo htmlspecialchars($lesson['lesson_title']); ?></h3>
                                <p><?php echo htmlspecialchars($lesson['description']); ?></p>
                                <div class="lesson-meta">
                                    <span
                                      ><i class="fas fa-clock"></i>
                                      <?php echo htmlspecialchars($lesson['estimated_time'] ?? '10'); ?> min
                                    </span>
                                    <span
                                      ><i class="fas fa-star"></i>
                                      <?php echo htmlspecialchars($lesson['xp_reward'] ?? '50'); ?> XP
                                    </span>
                                </div>
                            </div>
                            <div class="lesson-action">
                                <?php if ($lesson['completed'] > 0): ?>
                                <button
                                  class="btn btn-secondary start-lesson"
                                  data-lesson="<?php echo htmlspecialchars($lesson['lesson_id']); ?>"
                                >
                                    Review
                                </button>
                                <?php elseif ($index === 0 || $lessons[$index - 1]['completed'] > 0): ?>
                                <button
                                  class="btn btn-primary start-lesson"
                                  data-lesson="<?php echo htmlspecialchars($lesson['lesson_id']); ?>"
                                >
                                    Start
                                </button>
                                <?php else: ?>
                                <button class="btn btn-disabled" disabled>
                                    Locked
                                </button>
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
                <!-- MVP-Inhalt: Spielbeschreibung + Avatar + Button -->
                <div class="game-details">
                    <h3 id="modalGameName">Spielname</h3>
                    <p id="modalGameDescription">
                        Hier erscheint eine kurze Beschreibung des Spiels als Platzhalter.
                    </p>
                </div>
                <button id="playNowBtn" class="btn btn-primary play-now-btn">
                    <i class="fas fa-play"></i> Play Now
                </button>
                <!-- Avatar im Modal -->
                <div class="avatar-container">
                    <div class="speech-bubble" id="cerve-speech">
                        <p>Let's practice your Spanish!</p>
                    </div>
                    <img src="img/CerveLingua_Avatar.png" alt="CerveLingua Avatar" id="cerve-avatar" />
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
        // Elemente für Game-Modal
        const gameButtons = document.querySelectorAll('.start-game');
        const gameModal   = document.getElementById('game-modal');
        const gameTitle   = document.getElementById('game-title');
        const modalGameName        = document.getElementById('modalGameName');
        const modalGameDescription = document.getElementById('modalGameDescription');
        const playNowBtn           = document.getElementById('playNowBtn');

        // Elemente für Lesson-Modal
        const lessonButtons = document.querySelectorAll('.start-lesson');
        const lessonModal   = document.getElementById('lesson-modal');
        const lessonTitle   = document.getElementById('lesson-title');
        const lessonContainer = document.getElementById('lesson-container');

        // Schließen-Buttons in beiden Modals
        const closeButtons = document.querySelectorAll('.close-modal');

        let selectedGameCode = '';  // Speichert den aktuellen gameCode für "Play Now"-Button

        // Öffnet das Game-Modal mit MVP-Inhalt
        gameButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const gameCode = this.getAttribute('data-game');
                const gameName = this.closest('.game-card').querySelector('h3').textContent;

                // Setze Titel und Beschreibung
                gameTitle.textContent        = gameName;
                modalGameName.textContent    = gameName;
                modalGameDescription.textContent =
                    "Dies ist eine Kurzbeschreibung des Spiels \"" + gameName + "\". " +
                    "Weitere Details folgen später.";

                // Speichere gameCode, damit "Play Now" weiß, wohin verlinkt werden soll
                selectedGameCode = gameCode;

                // Zeige Modal an
                gameModal.style.display = 'block';
            });
        });

        // Klick auf "Play Now" leitet zum eigentlichen Spiel weiter
        playNowBtn.addEventListener('click', function() {
            if (selectedGameCode) {
                window.location.href = `games/${selectedGameCode}.php`;
            }
        });

        // Öffnet das Lesson-Modal per AJAX
        lessonButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const lessonId   = this.getAttribute('data-lesson');
                const lessonName = this.closest('.lesson-card').querySelector('h3').textContent;

                lessonTitle.textContent = lessonName;
                lessonModal.style.display = 'block';

                fetch(`get_lesson.php?lesson_id=${lessonId}`)
                    .then(response => response.text())
                    .then(html => {
                        lessonContainer.innerHTML = html;
                        sessionStorage.setItem('lessonStartTime', Date.now());
                        sessionStorage.setItem('currentLessonId', lessonId);
                    })
                    .catch(error => {
                        console.error('Error loading lesson:', error);
                        lessonContainer.innerHTML = `
                            <div class="error-message">
                                <p>Error loading lesson. Please try again later.</p>
                            </div>
                        `;
                    });
            });
        });

        // Schließ-Logik für beide Modals
        closeButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                modal.style.display = 'none';

                // Falls Lesson-Modal geschlossen wird, speichere Progress
                if (modal.id === 'lesson-modal') {
                    const lessonId        = sessionStorage.getItem('currentLessonId');
                    const lessonStartTime = sessionStorage.getItem('lessonStartTime');
                    if (lessonId && lessonStartTime) {
                        const timeSpent = Math.floor((Date.now() - lessonStartTime) / 1000);
                        if (timeSpent > 10) {
                            saveLessonProgress(lessonId, timeSpent);
                        }
                        sessionStorage.removeItem('currentLessonId');
                        sessionStorage.removeItem('lessonStartTime');
                    }
                }
            });
        });

        // Schließe Modals, wenn außerhalb geklickt wird
        window.addEventListener('click', function(event) {
            if (event.target === gameModal) {
                gameModal.style.display = 'none';
            }
            if (event.target === lessonModal) {
                lessonModal.style.display = 'none';
            }
        });

        // Save-Progress-Funktion für Lessons (bleibt unverändert)
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

        // XP-Benachrichtigung (bleibt unverändert)
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
    </script>
</body>
</html>
