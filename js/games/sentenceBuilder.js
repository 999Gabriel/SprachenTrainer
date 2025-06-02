/**
 * Sentence Builder Game for AntwortenTrainer
 */
class SentenceBuilderGame extends GameEngine {
    constructor(containerId, options = {}) {
        super(containerId, options);
        
        // Game specific properties
        this.sentencesCompleted = 0;
        this.correctSentences = 0;
        this.currentSentence = null;
        this.currentWords = [];
        this.userSentence = [];
        this.accuracy = 0;
        this.challengeLevel = 1;
        this.sentenceList = [];
        
        // Game elements
        this.sentenceTranslationElement = document.getElementById('sentence-translation');
        this.wordBankElement = document.getElementById('word-bank');
        this.constructionAreaElement = document.getElementById('sentence-construction-area');
        this.resetButton = document.getElementById('reset-sentence');
        this.submitButton = document.getElementById('submit-answer');
        this.finalSentencesElement = document.getElementById('final-sentences');
        this.finalAccuracyElement = document.getElementById('final-accuracy');
        this.gamePlayArea = document.getElementById('game-play-area');
        
        // Initialize vocabulary manager
        this.vocabularyManager = new VocabularyManager(this.difficulty);
        
        // Initialize audio manager
        this.audioManager = new AudioManager();
        
        // Bind methods
        this.submitAnswer = this.submitAnswer.bind(this);
        this.resetSentence = this.resetSentence.bind(this);
        this.handleWordClick = this.handleWordClick.bind(this);
        this.handleDragStart = this.handleDragStart.bind(this);
        this.handleDragOver = this.handleDragOver.bind(this);
        this.handleDrop = this.handleDrop.bind(this);
        
        // Add event listeners
        if (this.submitButton) {
            this.submitButton.addEventListener('click', this.submitAnswer);
        }
        
        if (this.resetButton) {
            this.resetButton.addEventListener('click', this.resetSentence);
        }
    }
    
    async init() {
        super.init();
        
        // Load vocabulary
        await this.vocabularyManager.loadAll();
        
        // Prepare sentence list based on difficulty
        this.prepareSentenceList();
    }
    
    prepareSentenceList() {
        // In a real implementation, you would generate sentences based on vocabulary
        // For now, use sample sentences
        const basicSentences = [
            { spanish: "Me gusta el café", translation: "I like coffee", difficulty: "A1" },
            { spanish: "¿Cómo estás hoy?", translation: "How are you today?", difficulty: "A1" },
            { spanish: "Voy a la playa", translation: "I am going to the beach", difficulty: "A1" }
        ];
        
        const intermediateSentences = [
            { spanish: "Necesito practicar mi español todos los días", translation: "I need to practice my Spanish every day", difficulty: "A2" },
            { spanish: "¿Puedes ayudarme con mi tarea?", translation: "Can you help me with my homework?", difficulty: "A2" },
            { spanish: "Me encanta viajar a países hispanohablantes", translation: "I love traveling to Spanish-speaking countries", difficulty: "A2" }
        ];
        
        const advancedSentences = [
            { spanish: "Si hubiera estudiado más, habría aprobado el examen", translation: "If I had studied more, I would have passed the exam", difficulty: "B1" },
            { spanish: "Es importante que practiquemos el subjuntivo", translation: "It is important that we practice the subjunctive", difficulty: "B1" },
            { spanish: "Antes de que llegues, habré terminado de cocinar", translation: "Before you arrive, I will have finished cooking", difficulty: "B1" }
        ];
        
        // Filter sentences based on difficulty
        let filteredSentences = [];
        switch(this.difficulty) {
            case 'A1':
                filteredSentences = basicSentences;
                break;
            case 'A2':
                filteredSentences = [...basicSentences, ...intermediateSentences];
                break;
            default: // B1 and above
                filteredSentences = [...basicSentences, ...intermediateSentences, ...advancedSentences];
                break;
        }
        
        this.sentenceList = filteredSentences.map(sentence => ({
            text: sentence.spanish,
            translation: sentence.translation,
            difficulty: sentence.difficulty
        }));
        
        // Shuffle the sentence list
        this.shuffleArray(this.sentenceList);
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
        this.sentencesCompleted = 0;
        this.correctSentences = 0;
        this.accuracy = 0;
        this.challengeLevel = 1;
        this.userSentence = [];
        
        // Show game play area
        if (this.gamePlayArea) {
            this.gamePlayArea.style.display = 'block';
        }
        
        // Start with first sentence
        this.nextSentence();
    }
    
    nextSentence() {
        // Get a sentence from the list or recycle if we've used them all
        if (this.sentenceList.length === 0) {
            this.prepareSentenceList();
            this.challengeLevel++; // Increase difficulty
        }
        
        this.currentSentence = this.sentenceList.pop();
        
        // Split the sentence into words and shuffle them
        this.currentWords = this.currentSentence.text.split(/\s+/);
        const shuffledWords = [...this.currentWords];
        this.shuffleArray(shuffledWords);
        
        // Display the translation
        if (this.sentenceTranslationElement) {
            this.sentenceTranslationElement.textContent = this.currentSentence.translation;
        }
        
        // Clear the construction area
        if (this.constructionAreaElement) {
            this.constructionAreaElement.innerHTML = '';
        }
        
        // Reset user sentence
        this.userSentence = [];
        
        // Populate the word bank with shuffled words
        this.populateWordBank(shuffledWords);
        
        // Set up drag and drop listeners
        this.setupDragAndDrop();
    }
    
    populateWordBank(words) {
        if (!this.wordBankElement) return;
        
        this.wordBankElement.innerHTML = '';
        
        words.forEach((word, index) => {
            const wordElement = document.createElement('div');
            wordElement.className = 'word-tile';
            wordElement.textContent = word;
            wordElement.setAttribute('draggable', 'true');
            wordElement.setAttribute('data-word', word);
            wordElement.setAttribute('data-index', index);
            
            wordElement.addEventListener('dragstart', this.handleDragStart);
            wordElement.addEventListener('click', this.handleWordClick);
            
            this.wordBankElement.appendChild(wordElement);
        });
    }
    
    setupDragAndDrop() {
        if (!this.constructionAreaElement) return;
        
        this.constructionAreaElement.addEventListener('dragover', this.handleDragOver);
        this.constructionAreaElement.addEventListener('drop', this.handleDrop);
    }
    
    handleDragStart(event) {
        event.dataTransfer.setData('text/plain', event.target.getAttribute('data-word'));
        event.dataTransfer.setData('source-id', event.target.getAttribute('data-index'));
    }
    
    handleDragOver(event) {
        event.preventDefault(); // Allow drop
    }
    
    handleDrop(event) {
        event.preventDefault();
        const word = event.dataTransfer.getData('text/plain');
        const sourceId = event.dataTransfer.getData('source-id');
        
        // Remove the word from the word bank
        const sourceElement = document.querySelector(`[data-index="${sourceId}"]`);
        if (sourceElement) {
            sourceElement.remove();
        }
        
        // Add the word to the construction area
        this.addWordToConstruction(word);
    }
    
    handleWordClick(event) {
        const word = event.target.getAttribute('data-word');
        event.target.remove();
        this.addWordToConstruction(word);
    }
    
    addWordToConstruction(word) {
        if (!this.constructionAreaElement) return;
        
        const wordElement = document.createElement('div');
        wordElement.className = 'word-tile in-sentence';
        wordElement.textContent = word;
        wordElement.setAttribute('data-word', word);
        
        // Add click event to remove from construction and put back in word bank
        wordElement.addEventListener('click', (e) => {
            const word = e.target.getAttribute('data-word');
            e.target.remove();
            this.userSentence = this.userSentence.filter(w => w !== word);
            
            // Add back to word bank
            const wordBankElement = document.createElement('div');
            wordBankElement.className = 'word-tile';
            wordBankElement.textContent = word;
            wordBankElement.setAttribute('draggable', 'true');
            wordBankElement.setAttribute('data-word', word);
            wordBankElement.setAttribute('data-index', Date.now()); // Unique ID
            
            wordBankElement.addEventListener('dragstart', this.handleDragStart);
            wordBankElement.addEventListener('click', this.handleWordClick);
            
            this.wordBankElement.appendChild(wordBankElement);
        });
        
        this.constructionAreaElement.appendChild(wordElement);
        this.userSentence.push(word);
    }
    
    resetSentence() {
        // Clear construction area and rebuild word bank
        if (this.constructionAreaElement) {
            this.constructionAreaElement.innerHTML = '';
        }
        
        this.userSentence = [];
        
        // Repopulate word bank with shuffled words
        const shuffledWords = [...this.currentWords];
        this.shuffleArray(shuffledWords);
        this.populateWordBank(shuffledWords);
    }
    
    submitAnswer() {
        if (!this.gameActive || !this.currentSentence) return;

        this.sentencesCompleted++;
        
        // Join the user's sentence and compare with the correct sentence
        const userSentenceText = this.userSentence.join(' ');
        const isCorrect = userSentenceText === this.currentSentence.text;
        
        if (isCorrect) {
            this.correctSentences++;
            this.score += 20; // Or some other scoring logic
            this.audioManager.playSound('correct');
            // Add visual feedback for correct answer
            this.constructionAreaElement.classList.add('correct-answer');
            setTimeout(() => {
                this.constructionAreaElement.classList.remove('correct-answer');
                this.nextSentence();
            }, 1000);
        } else {
            this.audioManager.playSound('incorrect');
            // Add visual feedback for incorrect answer
            this.constructionAreaElement.classList.add('incorrect-answer');
            setTimeout(() => {
                this.constructionAreaElement.classList.remove('incorrect-answer');
                // Optionally show the correct answer
                this.showCorrectAnswer();
            }, 1000);
        }
        
        this.updateStats();
        
        // Continue game if active
        if (!isCorrect) {
            // For incorrect answers, we'll wait for the user to see the correct answer
            // before moving to the next sentence
            setTimeout(() => {
                if (this.gameActive) {
                    this.nextSentence();
                }
            }, 3000);
        }
    }
    
    showCorrectAnswer() {
        if (!this.constructionAreaElement || !this.currentSentence) return;
        
        this.constructionAreaElement.innerHTML = '';
        this.userSentence = [];
        
        // Display the correct sentence
        const correctSentenceElement = document.createElement('div');
        correctSentenceElement.className = 'correct-sentence';
        correctSentenceElement.textContent = `Correct: ${this.currentSentence.text}`;
        
        this.constructionAreaElement.appendChild(correctSentenceElement);
    }
    
    updateStats() {
        if (this.sentencesCompleted > 0) {
            this.accuracy = Math.round((this.correctSentences / this.sentencesCompleted) * 100);
        } else {
            this.accuracy = 0;
        }
        
        // Update XP based on score
        this.xpGained = Math.floor(this.score / 2);
        
        // Update UI elements
        if (this.scoreElement) {
            this.scoreElement.textContent = this.score;
        }
        if (this.xpElement) {
            this.xpElement.textContent = this.xpGained;
        }
    }
    
    endGame() {
        super.endGame(); // This will handle saving progress via API

        // Display final stats
        if (this.finalSentencesElement) {
            this.finalSentencesElement.textContent = this.correctSentences;
        }
        if (this.finalAccuracyElement) {
            this.finalAccuracyElement.textContent = `${this.accuracy}%`;
        }

        // Hide game play area
        if (this.gamePlayArea) {
            this.gamePlayArea.style.display = 'none';
        }
    }
}

// Initialize the game when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const gameContainer = document.getElementById('sentence-builder-game');
    if (gameContainer) {
        const gameId = gameContainer.getAttribute('data-game-id');
        const game = new SentenceBuilderGame('sentence-builder-game', { gameId: gameId });
        game.init();
    }
});