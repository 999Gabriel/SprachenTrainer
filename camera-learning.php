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
    
    // Fetch user progress
    $progress_stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :user_id");
    $progress_stmt->bindParam(':user_id', $user_id);
    $progress_stmt->execute();
    $progress = $progress_stmt->fetch();
    
    if (!$progress) {
        // Default progress values
        $progress = [
            'current_level_id' => 1,
            'xp_points' => 0,
            'streak_days' => 0
        ];
    }
    
} catch (PDOException $e) {
    // Log error and continue with default values
    error_log("Camera page error: " . $e->getMessage());
    
    // Set default values
    $user = [
        'username' => $_SESSION['username'] ?? 'User',
        'first_name' => '',
        'profile_image' => ''
    ];
    
    $progress = [
        'current_level_id' => 1,
        'xp_points' => 0,
        'streak_days' => 0
    ];
}

// Set page title
$page_title = "Camera Learning";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - Camera Learning</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/camera.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- TensorFlow.js and COCO-SSD model -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd"></script>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>


    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
            <br>
            <br>
            <br>
            <br>    
            <h1>Camera Learning</h1>
                <p>Point your camera at objects to learn their Spanish names</p>
            </div>
            
            <div class="camera-container">
                <div class="camera-wrapper">
                    <video id="webcam" autoplay muted playsinline></video>
                    <canvas id="canvas" class="detection-canvas"></canvas>
                    <div class="camera-controls">
                        <button id="startCamera" class="btn btn-primary"><i class="fas fa-video"></i> Start Camera</button>
                        <button id="captureImage" class="btn btn-accent" disabled><i class="fas fa-camera"></i> Capture</button>
                        <button id="switchCamera" class="btn btn-outline" disabled><i class="fas fa-sync"></i> Switch Camera</button>
                    </div>
                </div>
                
                <div class="detection-results">
                    <div class="result-header">
                        <h3>Detected Objects</h3>
                        <div class="xp-indicator">
                            <i class="fas fa-star"></i> <span id="xpEarned">0</span> XP earned
                        </div>
                    </div>
                    
                    <div class="detected-objects" id="detectedObjects">
                        <div class="empty-state">
                            <i class="fas fa-camera-retro"></i>
                            <p>Start the camera and point it at objects to detect them</p>
                        </div>
                    </div>
                    
                    <div class="learning-challenge" id="learningChallenge" style="display: none;">
                        <h3>What's this in Spanish?</h3>
                        <div class="challenge-word" id="challengeWord">Chair</div>
                        <div class="answer-input">
                            <input type="text" id="userAnswer" placeholder="Type the Spanish word...">
                            <button id="checkAnswer" class="btn btn-primary">Check</button>
                        </div>
                        <div class="hint-container">
                            <button id="getHint" class="btn btn-text"><i class="fas fa-lightbulb"></i> Get a hint</button>
                            <div id="hintText" class="hint-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="camera-info">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="info-content">
                        <h3>How it works</h3>
                        <p>Our AI will identify objects in your camera view. When you capture an image, we'll challenge you to name the objects in Spanish. Get it right to earn XP!</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Privacy First</h3>
                        <p>All image processing happens directly in your browser. No images are uploaded or stored on our servers.</p>
                    </div>
                </div>
            </div>
            
            <div class="recently-learned">
                <h2>Recently Learned Words</h2>
                <div class="word-cards" id="recentlyLearned">
                    <!-- Words will be added here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/camera.js"></script>
</body>
</html>

// Find the AJAX request that handles saving progress
// This is likely in a JavaScript section that looks something like this:
<script>
    function markWordAsLearned(word) {
        // Add error logging to see what's happening
        console.log('Marking word as learned:', word);
        
        $.ajax({
            url: 'api/save-word-progress.php',
            type: 'POST',
            data: {
                word: word,
                action: 'mark_learned'
            },
            success: function(response) {
                console.log('Save progress response:', response);
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        showNotification('Word marked as learned! +' + data.xp + ' XP', 'success');
                        updateXpCounter(data.xp);
                    } else {
                        console.error('Error details:', data.error);
                        showNotification('Error saving progress: ' + data.error, 'error');
                    }
                } catch (e) {
                    console.error('Invalid JSON response:', response, e);
                    showNotification('Error saving progress. Please try again.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error, xhr.responseText);
                showNotification('Error saving progress. Please check your connection.', 'error');
            }
        });
    }
</script>