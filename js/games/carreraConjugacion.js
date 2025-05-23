/**
 * Carrera de Conjugación - A verb conjugation racing game
 * Part of AntwortenTrainer Spanish Learning Platform
 */

class CarreraConjugacion extends GameEngine {
    constructor(containerId, difficulty = 'A1', gameId = 2) {
        super(containerId, {
            gameId: gameId,
            difficulty: difficulty,
            gameTime: 60
        });
        
        // Game-specific properties
        this.verbs = [];
        this.currentVerb = null;
        this.correctAnswers = 0;
        this.totalAnswers = 0;
        this.carPosition = 0;
        this.maxPosition = 100;
        
        // Load vocabulary manager
        this.vocabManager = new VocabularyManager(difficulty);
        
        // Load audio manager
        this.audioManager = new AudioManager();
        
        // Bind additional methods
        this.checkAnswer = this.checkAnswer.bind(this);
    }
    
    async init() {
        // Call parent init
        super.init();
        
        // Create game UI
        this.createGameUI();
        
        // Load verbs
        await this.vocabManager.loadVerbs();
        this.verbs = this.vocabManager.verbs;
    }
    
    createGameUI() {
        // Create the main game area
        this.container.innerHTML = `
            <div class="race-track">
                <div class="car" id="player-car"></div>
                <div class="finish-line"></div>
            </div>
            <div class="conjugation-area">
                <div class="verb-display">
                    <span class="verb-prompt">Conjugate: </span>
                    <span id="verb-infinitive"></span>
                    <span class="verb-tense" id="verb-tense"></span>
                    <span class="verb-pronoun" id="verb-pronoun"></span>
                </div>
                <div class="answer-input">
                    <input type="text" id="conjugation-input" placeholder="Type conjugation here..." autocomplete="off">
                    <button id="submit-answer" class="btn btn-primary">Submit</button>
                </div>
            </div>
        `;
    }
    
    startGame() {
        // Call parent startGame
        super.startGame();
        
        // Show first verb
        this.showNextVerb();
        
        // Add event listeners for gameplay
        document.getElementById('submit-answer').addEventListener('click', this.checkAnswer);
        document.getElementById('conjugation-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.checkAnswer();
            }
        });
        
        // Focus on input
        document.getElementById('conjugation-input').focus();
    }
    
    resetGame() {
        // Call parent resetGame
        super.resetGame();
        
        // Reset game-specific state
        this.correctAnswers = 0;
        this.totalAnswers = 0;
        this.carPosition = 0;
        
        // Update car position
        this.updateCarPosition();
    }
    
    showNextVerb() {
        // Get a random verb
        const verbIndex = Math.floor(Math.random() * this.verbs.length);
        const verb = this.verbs[verbIndex];
        
        // Get a random tense
        const tenses = Object.keys(verb.tenses);
        const tenseIndex = Math.floor(Math.random() * tenses.length);
        const tense = tenses[tenseIndex];
        
        // Get a random pronoun
        const pronouns = Object.keys(verb.tenses[tense]);
        const pronounIndex = Math.floor(Math.random() * pronouns.length);
        const pronoun = pronouns[pronounIndex];
        
        // Set current verb
        this.currentVerb = {
            infinitive: verb.infinitive,
            tense: tense,
            pronoun: pronoun,
            correctAnswer: verb.tenses[tense][pronoun]
        };
        
        // Update UI
        document.getElementById('verb-infinitive').textContent = verb.infinitive;
        document.getElementById('verb-tense').textContent = tense;
        document.getElementById('verb-pronoun').textContent = pronoun;
        document.getElementById('conjugation-input').value = '';
        document.getElementById('conjugation-input').focus();
        
        // Speak the infinitive
        this.audioManager.speakText(verb.infinitive);
    }
    
    checkAnswer() {
        // Get user's answer
        const userAnswer = document.getElementById('conjugation-input').value.trim().toLowerCase();
        
        // Check if answer is correct
        const isCorrect = userAnswer === this.currentVerb.correctAnswer.toLowerCase();
        
        // Update stats
        this.totalAnswers++;
        
        if (isCorrect) {
            this.correctAnswers++;
            
            // Calculate points based on time remaining
            const points = Math.ceil(100 * (this.timeRemaining / this.gameTime)) + 50;
            this.score += points;
            
            // Update car position
            this.carPosition += 10;
            if (this.carPosition > this.maxPosition) {
                this.carPosition = this.maxPosition;
            }
            
            // Play sound
            this.audioManager.play('correct');
            
            // Show feedback
            this.showFeedback(true, '¡Correcto!');
        } else {
            // Play sound
            this.audioManager.play('incorrect');
            
            // Show feedback
            this.showFeedback(false, `Incorrect. The correct answer is: ${this.currentVerb.correctAnswer}`);
        }
        
        // Update UI
        this.updateScore();
        this.updateCarPosition();
        
        // Check if race is complete
        if (this.carPosition >= this.maxPosition) {
            this.endGame();
            return;
        }
        
        // Show next verb
        this.showNextVerb();
    }
    
    updateCarPosition() {
        const carElement = document.getElementById('player-car');
        carElement.style.left = `${this.carPosition}%`;
    }
    
    async endGame() {
        // Calculate XP gained (10% of score + bonus for accuracy)
        const accuracyBonus = this.totalAnswers > 0 ? Math.floor(50 * (this.correctAnswers / this.totalAnswers)) : 0;
        this.xpGained = Math.ceil(this.score / 10) + accuracyBonus;
        
        // Play game over sound
        this.audioManager.play('gameover');
        
        // Call parent endGame
        await super.endGame();
    }
    
    getGameSpecificData() {
        return {
            correct_answers: this.correctAnswers,
            total_answers: this.totalAnswers,
            accuracy: this.totalAnswers > 0 ? (this.correctAnswers / this.totalAnswers) : 0
        };
    }
}

// Initialize game when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the game page
    const gameContainer = document.getElementById('game-container');
    if (gameContainer && gameContainer.dataset.game === 'carreraConjugacion') {
        const difficulty = document.body.getAttribute('data-user-level') || 'A1';
        const gameId = parseInt(gameContainer.dataset.gameId) || 2;
        new CarreraConjugacion('game-container', difficulty, gameId);
    }
});