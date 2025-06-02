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
    
    // Fetch user's current chapter and unit information
    $chapter_stmt = $pdo->prepare("SELECT c.chapter_name, u.unit_name 
                                FROM user_progress up 
                                JOIN chapters c ON up.current_chapter_id = c.chapter_id 
                                JOIN units u ON up.current_unit_id = u.unit_id 
                                WHERE up.user_id = :user_id");
    $chapter_stmt->bindParam(':user_id', $user_id);
    $chapter_stmt->execute();
    $current_position = $chapter_stmt->fetch();
    
    if (!$current_position) {
        $current_position = [
            'chapter_name' => 'Einführung',
            'unit_name' => 'Grundlagen'
        ];
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
    
    $current_position = [
        'chapter_name' => 'Kapitel 1: Grundlagen – Menschen & Identität',
        'unit_name' => 'Nomen für Personen'
    ];
}

// Set page title
$page_title = "Vokabeltraining";
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - Vokabeltraining</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/practice.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <!-- Practice Content -->
    <div class="practice-container">
        <div class="container">
            <!-- Hauptkästchen mit Kapitel und Einheit -->
            <div class="main-box">
                <div class="chapter-info">
                    <h2><?php echo htmlspecialchars($current_position['chapter_name']); ?>, <?php echo htmlspecialchars($current_position['unit_name']); ?></h2>
                </div>
                
                <div class="logo-container">
                    <div class="round-logo">
                        <img src="img/CerveLingua_Avatar.png" alt="CerveLingua Logo">
                    </div>
                </div>
                
                <div class="start-button">
                    <a href="/games/sentenceBuilder.php" class="btn btn-primary">Start</a>
                </div>
                
                <div class="chapter-description">
                    <p>Informationen über das jetzige Kapitel</p>
                </div>
            </div>
            
            <!-- Unteres Kästchen mit Streak und Vokabelwiederholung -->
            <div class="streak-box">
                <div class="streak-info">
                    <div class="streak-count">
                        <i class="fas fa-fire"></i>
                        <span><?php echo $progress['streak_days']; ?> Tage Streak</span>
                    </div>
                    
                    <div class="vocabulary-review">
                        <a href="practice-review.php" class="btn btn-secondary">Gelernte Vokabeln wiederholen</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="js/dark-mode.js"></script>
    <script src="js/user-dropdown.js"></script>
    <script>
        // Hier würde später der Code kommen, um die Vokabelstatistiken zu laden
        document.addEventListener('DOMContentLoaded', function() {
            // Lade Vokabelstatistiken vom Server
            fetch('get_vocabulary.php')
                .then(response => response.json())
                .then(data => {
                    // Aktualisiere die Anzeige wenn nötig
                    console.log('Vokabelstatistiken geladen:', data);
                })
                .catch(error => console.error('Fehler beim Laden der Vokabelstatistiken:', error));
        });
    </script>
</body>
</html>