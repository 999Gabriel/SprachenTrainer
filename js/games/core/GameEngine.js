/**
 * Core Game Engine for AntwortenTrainer Spanish Learning Games
 */
class GameEngine {
    constructor(containerId, options = {}) {
        // Core properties
        this.container = document.getElementById(containerId);
        this.gameId = options.gameId || 1;
        this.userId = document.body.getAttribute('data-user-id');
        this.difficulty = options.difficulty || document.body.getAttribute('data-user-level') || 'A1';
        this.score = 0;
        this.xpGained = 0;
        this.gameTime = options.gameTime || 60; // seconds
        this.timeRemaining = this.gameTime;
        this.timer = null;
        this.gameStarted = false;
        this.gameOver = false;
        
        // UI Elements
        this.instructionsElement = document.getElementById('game-instructions');
        this.gameOverElement = document.getElementById('game-over-screen');
        this.scoreElement = document.getElementById('player-score');
        this.xpElement = document.getElementById('player-xp');
        this.timerElement = document.getElementById('game-timer');
        this.finalScoreElement = document.getElementById('final-score');
        this.finalXpElement = document.getElementById('final-xp');
        
        // Bind methods
        this.startGame = this.startGame.bind(this);
        this.updateTimer = this.updateTimer.bind(this);
        this.endGame = this.endGame.bind(this);
        
        // Initialize
        this.init();
    }
    
    init() {
        // Add event listeners for common buttons
        if (document.getElementById('start-game-btn')) {
            document.getElementById('start-game-btn').addEventListener('click', this.startGame);
        }
        
        if (document.getElementById('play-again-btn')) {
            document.getElementById('play-again-btn').addEventListener('click', () => {
                this.gameOverElement.style.display = 'none';
                this.instructionsElement.style.display = 'block';
                this.resetGame();
            });
        }
    }
    
    createGameUI() {
        // To be implemented by child classes
        console.warn('createGameUI method should be implemented by child class');
    }
    
    startGame() {
        // Hide instructions
        if (this.instructionsElement) {
            this.instructionsElement.style.display = 'none';
        }
        
        // Reset game state
        this.resetGame();
        
        // Start timer
        this.timer = setInterval(this.updateTimer, 1000);
        
        // Mark game as started
        this.gameStarted = true;
        this.gameOver = false;
    }
    
    resetGame() {
        // Reset game state
        this.score = 0;
        this.timeRemaining = this.gameTime;
        this.xpGained = 0;
        
        // Update UI
        this.updateScore();
        this.updateXP();
        this.updateTimerDisplay();
        
        // Clear any existing timer
        if (this.timer) {
            clearInterval(this.timer);
        }
    }
    
    updateTimer() {
        this.timeRemaining--;
        this.updateTimerDisplay();
        
        if (this.timeRemaining <= 0) {
            clearInterval(this.timer);
            this.endGame();
        }
    }
    
    updateTimerDisplay() {
        if (this.timerElement) {
            this.timerElement.textContent = this.timeRemaining;
        }
    }
    
    updateScore() {
        if (this.scoreElement) {
            this.scoreElement.textContent = this.score;
        }
    }
    
    updateXP() {
        if (this.xpElement) {
            this.xpElement.textContent = this.xpGained;
        }
    }
    
    showFeedback(isCorrect, message = '') {
        const feedbackElement = document.createElement('div');
        feedbackElement.className = isCorrect ? 'feedback correct' : 'feedback incorrect';
        feedbackElement.textContent = message || (isCorrect ? '¡Correcto!' : 'Incorrecto');
        
        // Add to container
        this.container.appendChild(feedbackElement);
        
        // Remove after 2 seconds
        setTimeout(() => {
            if (this.container.contains(feedbackElement)) {
                this.container.removeChild(feedbackElement);
            }
        }, 2000);
    }
    
    async endGame() {
        // Clear timer
        if (this.timer) {
            clearInterval(this.timer);
        }
        
        // Mark game as over
        this.gameStarted = false;
        this.gameOver = true;
        
        // Update final stats
        if (this.finalScoreElement) {
            this.finalScoreElement.textContent = this.score;
        }
        
        if (this.finalXpElement) {
            this.finalXpElement.textContent = this.xpGained;
        }
        
        // Show game over screen
        if (this.gameOverElement) {
            this.gameOverElement.style.display = 'block';
        }
        
        // Save progress to server
        await this.saveProgress();
    }
    
    async saveProgress() {
        try {
            const response = await fetch('/api/save-game-progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    game_id: this.gameId,
                    user_id: this.userId,
                    score: this.score,
                    data: {
                        xp_gained: this.xpGained,
                        difficulty: this.difficulty,
                        time_played: this.gameTime - this.timeRemaining
                    }
                })
            });
            
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('Error saving game progress:', error);
            return { success: false, error: error.message };
        }
    }
}