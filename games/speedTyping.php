<?php
// Include configuration
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo "<div class='error-message'>Please log in to play this game.</div>";
    exit;
}

// Get game ID from query string
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 1;
$user_id = $_SESSION['user_id'];

// Get user level
try {
    $stmt = $pdo->prepare("SELECT current_level_id FROM user_progress WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_level = $stmt->fetchColumn();
    
    if (!$user_level) {
        $user_level = 1; // Default to level 1 if not found
    }
} catch (PDOException $e) {
    error_log("Error fetching user level: " . $e->getMessage());
    $user_level = 1; // Default to level 1 on error
}
?>

<div class="game-container" id="speed-typing-game" data-game-id="<?php echo $game_id; ?>">
    <!-- Game Instructions -->
    <div id="game-instructions" class="game-section">
        <h3>Speed Typing Challenge</h3>
        <p>Test your Spanish typing speed and accuracy! Type the Spanish words or phrases as quickly and accurately as possible.</p>
        
        <div class="game-rules">
            <h4>How to Play:</h4>
            <ul>
                <li>Spanish words or phrases will appear on the screen</li>
                <li>Type them correctly as quickly as possible</li>
                <li>You'll earn points based on speed and accuracy</li>
                <li>The game gets progressively more challenging</li>
            </ul>
        </div>
        
        <div class="difficulty-selection">
            <p>Current difficulty: <span class="difficulty-level">Level <?php echo $user_level; ?></span></p>
        </div>
        
        <button id="start-game-btn" class="btn btn-primary">Start Typing!</button>
    </div>
    
    <!-- Game Play Area -->
    <div id="game-play-area" class="game-section" style="display: none;">
        <div class="game-stats">
            <div class="stat-item">
                <span class="stat-label">Score:</span>
                <span id="player-score" class="stat-value">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Time:</span>
                <span id="game-timer" class="stat-value">60</span>s
            </div>
            <div class="stat-item">
                <span class="stat-label">XP:</span>
                <span id="player-xp" class="stat-value">0</span>
            </div>
        </div>
        
        <div class="typing-challenge">
            <div class="challenge-word">
                <h3 id="word-to-type">Ready...</h3>
                <p id="word-translation" class="translation"></p>
            </div>
            
            <div class="typing-input-container">
                <input type="text" id="typing-input" class="typing-input" placeholder="Type here..." autocomplete="off">
                <button id="submit-answer" class="btn btn-primary">Submit</button>
            </div>
            
            <div class="typing-stats">
                <div class="stat-item">
                    <span class="stat-label">WPM:</span>
                    <span id="wpm-counter" class="stat-value">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Accuracy:</span>
                    <span id="accuracy-counter" class="stat-value">0%</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Game Over Screen -->
    <div id="game-over-screen" class="game-section" style="display: none;">
        <h3>Game Over!</h3>
        
        <div class="final-stats">
            <div class="stat-item">
                <span class="stat-label">Final Score:</span>
                <span id="final-score" class="stat-value">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">XP Earned:</span>
                <span id="final-xp" class="stat-value">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Words Per Minute:</span>
                <span id="final-wpm" class="stat-value">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Accuracy:</span>
                <span id="final-accuracy" class="stat-value">0%</span>
            </div>
        </div>
        
        <div class="game-over-actions">
            <button id="play-again-btn" class="btn btn-primary">Play Again</button>
            <button id="back-to-games-btn" class="btn btn-secondary close-modal">Back to Games</button>
        </div>
    </div>
</div>

<!-- Load Game Scripts -->
<script src="/js/games/core/AudioManager.js"></script>
<script src="/js/games/core/VocabularyManager.js"></script>
<script src="/js/games/core/GameEngine.js"></script>
<script src="/js/games/speedTyping.js"></script>