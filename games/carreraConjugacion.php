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
    <!-- Game canvas will be created here by JavaScript -->
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
            <span id="player-score">0</span> Points
        </div>
        <div class="stat-item">
            <i class="fas fa-clock"></i>
            <span id="game-timer">60</span>s
        </div>
    </div>
</div>

<!-- Game instructions -->
<div class="game-instructions" id="game-instructions">
    <h3>How to Play</h3>
    <p>Race against time by quickly conjugating Spanish verbs. Different tracks represent different tenses, and obstacles require specific vocabulary.</p>
    <ul>
        <li>Type the correct conjugation of the verb shown</li>
        <li>The faster you answer, the more points you earn</li>
        <li>Watch out for special challenges that appear during the race</li>
    </ul>
    <button id="start-game-btn" class="btn btn-primary">Start Race</button>
</div>

<!-- Game over screen (hidden initially) -->
<div class="game-over-screen" id="game-over-screen" style="display: none;">
    <h2>Race Complete!</h2>
    <div class="final-stats">
        <div class="stat-item large">
            <i class="fas fa-trophy"></i>
            <span id="final-score">0</span> Points
        </div>
        <div class="stat-item large">
            <i class="fas fa-star"></i>
            <span id="final-xp">0</span> XP Earned
        </div>
    </div>
    <div class="action-buttons">
        <button id="play-again-btn" class="btn btn-primary">Race Again</button>
        <a href="../lessons.php" class="btn btn-secondary">Back to Lessons</a>
    </div>
</div>

<!-- Load game scripts -->
<script src="../js/games/carreraConjugacion.js"></script>