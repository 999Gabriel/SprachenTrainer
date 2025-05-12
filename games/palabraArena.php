<?php
// Include configuration
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo "<p class='error'>You must be logged in to play games.</p>";
    exit;
}

// Get game ID from URL parameter
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;

// Get user data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Get user's current level
try {
    $level_stmt = $pdo->prepare("
        SELECT pl.level_code FROM proficiency_levels pl
        JOIN user_progress up ON pl.level_id = up.current_level_id
        WHERE up.user_id = :user_id
    ");
    $level_stmt->bindParam(':user_id', $user_id);
    $level_stmt->execute();
    $user_level = $level_stmt->fetchColumn() ?: 'A1';
} catch (PDOException $e) {
    $user_level = 'A1'; // Default to A1 if error
}

// Game container
?>

<div class="game-area">
    <!-- Game canvas will be created here by Phaser -->
</div>

<!-- Game UI elements -->
<div class="game-controls">
    <div class="game-stats">
        <div class="stat-item">
            <i class="fas fa-star"></i>
            <span id="player-xp">0</span> XP
        </div>
        <div class="stat-item">
            <i class="fas fa-trophy"></i>
            <span id="player-level"><?php echo htmlspecialchars($user_level); ?></span>
        </div>
    </div>
</div>

<!-- Load Phaser.js -->
<script src="https://cdn.jsdelivr.net/npm/phaser@3.55.2/dist/phaser.min.js"></script>

<!-- Load game framework -->
<script src="game-framework.js"></script>

<!-- Load and initialize the game -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the game framework with user data
        CerveLinguaGames.init(
            'palabraArena',
            <?php echo $user_id; ?>,
            '<?php echo htmlspecialchars($username); ?>',
            '<?php echo htmlspecialchars($user_level); ?>'
        );
    });
</script>

<style>
    .game-area {
        width: 100%;
        height: 500px;
        position: relative;
        background-color: #1a1a2e;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .game-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .game-stats {
        display: flex;
        gap: 20px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: bold;
        color: #4e73df;
    }
    
    .stat-item i {
        color: #f6c23e;
    }
    
    /* Ensure the avatar container is properly positioned */
    .avatar-container {
        position: absolute;
        bottom: 20px;
        right: 20px;
        z-index: 10;
    }
    
    /* Make sure the speech bubble is visible */
    .speech-bubble {
        background-color: white;
        border-radius: 10px;
        padding: 10px 15px;
        position: relative;
        max-width: 250px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .speech-bubble:after {
        content: '';
        position: absolute;
        bottom: -10px;
        right: 20px;
        border-width: 10px 10px 0;
        border-style: solid;
        border-color: white transparent;
    }
    
    #cerve-avatar {
        width: 80px;
        height: auto;
    }
</style>