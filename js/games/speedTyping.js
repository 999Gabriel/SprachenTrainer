/**
 * Speed Typing Challenge Game for AntwortenTrainer
 */
class SpeedTypingGame extends GameEngine {
    constructor(containerId, options = {}) {
        super(containerId, options);
        
        // Game specific properties
        this.wordsTyped = 0;
        this.correctWords = 0;
        this.currentWord = null;
        this.startTime = 0;
        this.wpm = 0;
        this.accuracy = 0;
        this.challengeLevel = 1;
        this.wordList = [];
        
        // Game elements
        this.wordToTypeElement = document.getElementById('word-to-type');
        this.wordTranslationElement = document.getElementById('word-translation');
        this.typingInputElement = document.getElementById('typing-input');
        this.submitButton = document.getElementById('submit-answer');
        this.wpmElement = document.getElementById('wpm-counter');
        this.accuracyElement = document.getElementById('accuracy-counter');
        this.finalWpmElement = document.getElementById('final-wpm');
        this.finalAccuracyElement = document.getElementById('final-accuracy');
        this.gamePlayArea = document.getElementById('game-play-area');
        
        // Initialize vocabulary manager
        this.vocabularyManager = new VocabularyManager(this.difficulty);
        
        // Initialize audio manager
        this.audioManager = new AudioManager();
        
        // Bind methods
        this.submitAnswer = this.submitAnswer.bind(this);
        this.handleKeyPress = this.handleKeyPress.bind(this);
        
        // Add event listeners
        if (this.submitButton) {
            this.submitButton.addEventListener('click', this.submitAnswer);
        }
        
        if (this.typingInputElement) {
            this.typingInputElement.addEventListener('keypress', this.handleKeyPress);
        }
    }
    
    async init() {
        super.init();
        
        // Load vocabulary
        await this.vocabularyManager.loadAll();
        
        // Prepare word list based on difficulty
        this.prepareWordList();
    }
    
    prepareWordList() {
        // Combine words from different categories based on difficulty
        const nouns = this.vocabularyManager.nouns.filter(n => n.difficulty === this.difficulty);
        const verbs = this.vocabularyManager.verbs.filter(v => v.difficulty === this.difficulty);
        const phrases = this.vocabularyManager.phrases.filter(p => p.difficulty === this.difficulty);
        
        this.wordList = [
            ...nouns.map(n => ({ text: n.spanish, translation: n.translation, type: 'noun' })),
            ...verbs.map(v => ({ text: v.infinitive, translation: v.translation, type: 'verb' })),
            ...phrases.map(p => ({ text: p.spanish, translation: p.translation, type: 'phrase' }))
        ];
        
        // Shuffle the word list
        this.shuffleArray(this.wordList);
    }
    
    shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    }
    
    startGame() {
        super.startGame();
        
        // Reset game-specific stats
        this.wordsTyped = 0;
        this.correctWords = 0;
        this.wpm = 0;
        this.accuracy = 0;
        this.challengeLevel = 1;
        this.startTime = Date.now();
        
        // Show game play area
        if (this.gamePlayArea) {
            this.gamePlayArea.style.display = 'block';
        }
        
        // Focus on input field
        if (this.typingInputElement) {
            this.typingInputElement.value = '';
            this.typingInputElement.focus();
        }
        
        // Start with first word
        this.nextWord();
    }
    
    nextWord() {
        // Get a word from the list or recycle if we've used them all
        if (this.wordList.length === 0) {
            this.prepareWordList();
            this.challengeLevel++; // Increase difficulty
        }
        
        this.currentWord = this.wordList.pop();
        
        // Display the word
        if (this.wordToTypeElement) {
            this.wordToTypeElement.textContent = this.currentWord.text;
        }
        
        // Display translation based on challenge level
        if (this.wordTranslationElement) {
            // Show translation for lower levels, hide for higher levels
            if (this.challengeLevel <= 2) {
                this.wordTranslationElement.textContent = this.currentWord.translation;
                this.wordTranslationElement.style.display = 'block';
            } else {
                this.wordTranslationElement.style.display = 'none';
            }
        }
        
        // Clear input field
        if (this.typingInputElement) {
            this.typingInputElement.value = '';
            this.typingInputElement.focus();
        }
        
        // Speak the word for audio learners
        this.audioManager.speakText(this.currentWord.text);
    }
    
    handleKeyPress(event) {
        // Submit on Enter key
        if (event.key === 'Enter') {
            event.preventDefault();
            this.submitAnswer();
        }
    }
    
    submitAnswer() {
        if (!this.gameActive || !this.currentWord) return;

        const typedWord = this.typingInputElement.value.trim();
        this.wordsTyped++;

        if (typedWord === this.currentWord.text) {
            this.correctWords++;
            this.score += 10; // Or some other scoring logic
            this.audioManager.playSound('correct');
            // Add visual feedback for correct answer if desired
        } else {
            this.audioManager.playSound('incorrect');
            // Add visual feedback for incorrect answer if desired
        }

        this.updateStats();

        // For this game, we might want to continue until a certain time limit or number of words
        // Let's assume the game continues for a set duration managed by GameEngine's timer
        if (this.gameActive) { // gameActive might be set to false by updateTimer
            this.nextWord();
        } else {
            // If gameActive became false (e.g. timer ran out), endGame would have been called by GameEngine
            // Or, if we want to end based on words typed:
            // if (this.wordsTyped >= MAX_WORDS_PER_GAME) { this.endGame(); }
        }
    }

    updateStats() {
        const currentTime = Date.now();
        const timeElapsedInMinutes = (currentTime - this.startTime) / 60000;

        if (timeElapsedInMinutes > 0) {
            this.wpm = Math.round(this.correctWords / timeElapsedInMinutes);
        } else {
            this.wpm = 0;
        }

        if (this.wordsTyped > 0) {
            this.accuracy = Math.round((this.correctWords / this.wordsTyped) * 100);
        } else {
            this.accuracy = 0;
        }

        if (this.wpmElement) {
            this.wpmElement.textContent = this.wpm;
        }
        if (this.accuracyElement) {
            this.accuracyElement.textContent = `${this.accuracy}%`;
        }
    }

    // Override endGame to display final stats for this specific game
    endGame() {
        super.endGame(); // This will handle saving progress via API

        // Display final stats
        if (this.finalWpmElement) {
            this.finalWpmElement.textContent = this.wpm;
        }
        if (this.finalAccuracyElement) {
            this.finalAccuracyElement.textContent = `${this.accuracy}%`;
        }

        // Hide game play area, show game over screen (which should contain final stats elements)
        if (this.gamePlayArea) {
            this.gamePlayArea.style.display = 'none';
        }
        // The GameEngine's endGame method already shows the game over screen
        // We just need to make sure the final stats are populated before it's shown
        // or update them if it's already visible.
    }

    // GameEngine's updateTimer will call endGame when time is up.
    // We can add specific logic here if needed, but for now, GameEngine's default behavior is fine.
    // updateTimer(currentTime) {
    //     super.updateTimer(currentTime);
    //     // Additional logic for SpeedTypingGame if necessary
    // }
}

// Ensure the class is available globally or instantiated appropriately when the game is selected.
// Example: let currentGame = new SpeedTypingGame('game-container-id', { difficulty: 'easy', gameTimeLimit: 60 });
// currentGame.init().then(() => currentGame.startGame());