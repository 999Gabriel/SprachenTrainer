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
        if (!this.