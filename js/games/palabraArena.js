/**
 * Palabra Arena - A word matching game for Spanish vocabulary practice
 * Part of AntwortenTrainer Spanish Learning Platform
 */

class PalabraArena extends GameEngine {
    constructor(containerId, difficulty = 'A1', gameId = 1) {
        super(containerId, {
            gameId: gameId,
            difficulty: difficulty,
            gameTime: 90 // Longer game time for battles
        });
        
        // Game-specific properties
        this.vocabulary = [];
        this.currentWord = null;
        this.playerHealth = 100;
        this.enemyHealth = 100;
        this.enemyTypes = ['translation', 'conjugation', 'gender'];
        this.currentEnemyType = '';
        this.level = 1;
        this.enemiesDefeated = 0;
        
        // Load vocabulary manager
        this.vocabManager = new VocabularyManager(difficulty);
        
        // Load audio manager
        this.audioManager = new AudioManager();
        
        // Bind additional methods
        this.checkAnswer = this.checkAnswer.bind(this);
        this.attackEnemy = this.attackEnemy.bind(this);
        this.enemyAttack = this.enemyAttack.bind(this);
    }
    
    async init() {
        // Call parent init
        super.init();
        
        // Create game UI
        this.createGameUI();
        
        // Load vocabulary
        await this.vocabManager.loadAll();
        this.vocabulary = {
            verbs: this.vocabManager.verbs,
            nouns: this.vocabManager.nouns,
            adjectives: this.vocabManager.adjectives
        };
    }
    
    createGameUI() {
        // Create the main game area
        this.container.innerHTML = `
            <div class="battle-arena">
                <div class="player-character">
                    <div class="health-bar">
                        <div class="health-fill" id="player-health" style="width: 100%"></div>
                    </div>
                    <div class="character-sprite player"></div>
                </div>
                
                <div class="enemy-character">
                    <div class="health-bar">
                        <div class="health-fill" id="enemy-health" style="width: 100%"></div>
                    </div>
                    <div class="character-sprite enemy"></div>
                    <div class="enemy-type" id="enemy-type"></div>
                </div>
            </div>
            
            <div class="word-challenge">
                <div class="challenge-prompt" id="challenge-prompt"></div>
                <div class="word-display" id="word-display"></div>
                
                <div class="answer-input">
                    <input type="text" id="word-input" placeholder="Type your answer here..." autocomplete="off">
                    <button id="submit-answer" class="btn btn-primary">Attack!</button>
                </div>
            </div>
            
            <div class="battle-stats">
                <div class="stat">Level: <span id="level-display">1</span></div>
                <div class="stat">Enemies Defeated: <span id="enemies-defeated">0</span></div>
            </div>
        `;
    }
    
    async loadVocabulary() {
        try {
            const response = await fetch('/get_vocabulary.php');
            const data = await response.json();
            
            if (data.success && data.vocabulary) {
                // Filter words by difficulty if needed
                this.words = data.vocabulary.filter(word => 
                    word.difficulty_level === this.difficulty || 
                    this.difficulty === 'all'
                );
                
                // Shuffle words
                this.words = this.shuffleArray(this.words);
            } else {
                console.error('Failed to load vocabulary');
                this.showError('Failed to load vocabulary words. Please try again.');
            }
        } catch (error) {
            console.error('Error loading vocabulary:', error);
            this.showError('Network error. Please check your connection and try again.');
        }
    }
    
    startGame() {
        this.score = 0;
        this.currentRound = 0;
        this.updateScore();
        this.nextRound();
    }
    
    nextRound() {
        // Clear any existing timer
        if (this.timer) {
            clearInterval(this.timer);
        }
        
        // Check if game is over
        if (this.currentRound >= this.totalRounds || this.words.length < 4) {
            this.endGame();
            return;
        }
        
        // Get current word and options
        const currentWord = this.words[this.currentRound];
        const options = this.getOptions(currentWord);
        
        // Display word and options
        this.wordElement.textContent = currentWord.spanish_word;
        this.optionsElement.innerHTML = '';
        
        options.forEach(option => {
            const button = document.createElement('button');
            button.className = 'word-option';
            button.textContent = option.english_translation;
            button.dataset.wordId = option.word_id;
            button.addEventListener('click', () => this.selectOption(option.word_id, currentWord.word_id));
            this.optionsElement.appendChild(button);
        });
        
        // Reset and start timer
        this.timeRemaining = this.timePerRound;
        this.updateTimerDisplay();
        this.timer = setInterval(this.updateTimer, 1000);
        
        // Increment round counter
        this.currentRound++;
    }
    
    getOptions(correctWord) {
        // Create array with correct answer
        const options = [correctWord];
        
        // Add 3 random incorrect options
        const incorrectWords = this.words.filter(word => word.word_id !== correctWord.word_id);
        const shuffled = this.shuffleArray(incorrectWords);
        
        for (let i = 0; i < 3 && i < shuffled.length; i++) {
            options.push(shuffled[i]);
        }
        
        // Shuffle options
        return this.shuffleArray(options);
    }
    
    selectOption(selectedId, correctId) {
        // Clear timer
        if (this.timer) {
            clearInterval(this.timer);
        }
        
        // Check if answer is correct
        const isCorrect = selectedId === correctId;
        
        // Update score
        if (isCorrect) {
            this.score += Math.ceil(100 * (this.timeRemaining / this.timePerRound));
            this.updateScore();
        }
        
        // Highlight correct and incorrect answers
        const options = this.optionsElement.querySelectorAll('.word-option');
        options.forEach(option => {
            if (option.dataset.wordId == correctId) {
                option.classList.add('correct');
            } else if (option.dataset.wordId == selectedId && !isCorrect) {
                option.classList.add('incorrect');
            }
            
            // Disable all options
            option.disabled = true;
        });
        
        // Show next button
        document.getElementById('palabra-next').style.display = 'block';
    }
    
    updateTimer() {
        this.timeRemaining--;
        this.updateTimerDisplay();
        
        if (this.timeRemaining <= 0) {
            clearInterval(this.timer);
            
            // Auto-select wrong answer (timeout)
            const currentWord = this.words[this.currentRound - 1];
            this.selectOption(-1, currentWord.word_id);
        }
    }
    
    updateTimerDisplay() {
        this.timerElement.textContent = this.timeRemaining;
    }
    
    updateScore() {
        this.scoreElement.textContent = this.score;
    }
    
    async endGame() {
        // Calculate XP gained (10% of score)
        this.xpGained = Math.ceil(this.score / 10);
        
        // Show game over screen
        this.container.innerHTML = `
            <div class="game-over">
                <h2>Game Over!</h2>
                <div class="final-score">Final Score: ${this.score}</div>
                <div class="xp-gained">XP Gained: ${this.xpGained}</div>
                <button id="play-again" class="btn btn-primary">Play Again</button>
            </div>
        `;
        
        // Add event listener for play again button
        document.getElementById('play-again').addEventListener('click', () => {
            this.init();
        });
        
        // Save game progress
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
                        rounds_completed: this.currentRound
                    }
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                console.error('Failed to save game progress:', data.error);
            }
        } catch (error) {
            console.error('Error saving game progress:', error);
        }
    }
    
    showError(message) {
        this.container.innerHTML = `
            <div class="game-error">
                <p>${message}</p>
                <button id="retry-game" class="btn btn-primary">Retry</button>
            </div>
        `;
        
        document.getElementById('retry-game').addEventListener('click', () => {
            this.init();
        });
    }
    
    // Utility function to shuffle an array
    shuffleArray(array) {
        const newArray = [...array];
        for (let i = newArray.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
        }
        return newArray;
    }
}

// Initialize game when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the game page
    const gameContainer = document.getElementById('game-container');
    if (gameContainer && gameContainer.dataset.game === 'palabraArena') {
        const difficulty = document.body.getAttribute('data-user-level') || 'A1';
        const gameId = parseInt(gameContainer.dataset.gameId) || 1;
        new PalabraArena('game-container', difficulty, gameId);
    }
});