/**
 * Palabra Arena Game
 * A battle game where players defeat enemies by correctly translating or conjugating Spanish words
 */

const palabraArena = {
    // Game configuration
    config: {
        width: 800,
        height: 600,
        backgroundColor: '#1a1a2e',
        parent: 'game-container',
        physics: {
            default: 'arcade',
            arcade: {
                gravity: { y: 0 },
                debug: false
            }
        },
        scene: {
            preload: preload,
            create: create,
            update: update
        }
    },
    
    // Game state
    state: {
        score: 0,
        level: 1,
        health: 100,
        enemies: [],
        currentEnemy: null,
        currentWord: null,
        gameOver: false,
        paused: false
    },
    
    // Game instance
    game: null,
    
    // Game assets
    assets: {
        player: null,
        enemies: [],
        background: null,
        healthBar: null,
        scoreText: null,
        levelText: null,
        wordPanel: null
    },
    
    // Word database for the game
    wordDatabase: [
        // Level 1 - Simple translations
        {
            spanish: 'casa',
            english: 'house',
            level: 1,
            type: 'noun'
        },
        {
            spanish: 'perro',
            english: 'dog',
            level: 1,
            type: 'noun'
        },
        {
            spanish: 'gato',
            english: 'cat',
            level: 1,
            type: 'noun'
        },
        {
            spanish: 'libro',
            english: 'book',
            level: 1,
            type: 'noun'
        },
        {
            spanish: 'agua',
            english: 'water',
            level: 1,
            type: 'noun'
        },
        // Level 2 - More vocabulary
        {
            spanish: 'ciudad',
            english: 'city',
            level: 2,
            type: 'noun'
        },
        {
            spanish: 'tiempo',
            english: 'time',
            level: 2,
            type: 'noun'
        },
        {
            spanish: 'amigo',
            english: 'friend',
            level: 2,
            type: 'noun'
        },
        // Level 3 - Simple verbs
        {
            spanish: 'hablar',
            english: 'to speak',
            level: 3,
            type: 'verb'
        },
        {
            spanish: 'comer',
            english: 'to eat',
            level: 3,
            type: 'verb'
        },
        {
            spanish: 'vivir',
            english: 'to live',
            level: 3,
            type: 'verb'
        },
        // Level 4 - Verb conjugations
        {
            spanish: 'yo hablo',
            english: 'I speak',
            level: 4,
            type: 'conjugation'
        },
        {
            spanish: 'tú hablas',
            english: 'you speak',
            level: 4,
            type: 'conjugation'
        },
        {
            spanish: 'él come',
            english: 'he eats',
            level: 4,
            type: 'conjugation'
        }
    ],
    
    // Enemy types
    enemyTypes: [
        {
            name: 'Noun Monster',
            health: 50,
            damage: 10,
            speed: 100,
            wordType: 'noun',
            sprite: 'enemy1'
        },
        {
            name: 'Verb Beast',
            health: 75,
            damage: 15,
            speed: 80,
            wordType: 'verb',
            sprite: 'enemy2'
        },
        {
            name: 'Conjugation Demon',
            health: 100,
            damage: 20,
            speed: 60,
            wordType: 'conjugation',
            sprite: 'enemy3'
        }
    ],
    
    // Initialize the game
    init: function() {
        // Create Phaser game instance
        this.game = new Phaser.Game(this.config);
        
        // Set up game UI
        this.setupUI();
        
        // Show instructions
        CerveLinguaGames.ui.showCerveMessage('¡Bienvenido a Palabra Arena! Defeat enemies by translating Spanish words correctly.');
    },
    
    // Set up game UI elements
    setupUI: function() {
        // Create word input form
        const gameContainer = document.getElementById('game-container');
        
        // Create UI container
        const uiContainer = document.createElement('div');
        uiContainer.className = 'palabra-arena-ui';
        uiContainer.innerHTML = `
            <div class="word-challenge-container">
                <div class="word-display">
                    <h3>Translate:</h3>
                    <p id="challenge-word">Loading...</p>
                </div>
                <form id="word-form">
                    <input type="text" id="word-input" placeholder="Type translation here" autocomplete="off">
                    <button type="submit" class="game-ui-button">Submit</button>
                </form>
            </div>
        `;
        gameContainer.appendChild(uiContainer);
        
        // Add event listener for form submission
        const wordForm = document.getElementById('word-form');
        wordForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const input = document.getElementById('word-input');
            const answer = input.value.trim().toLowerCase();
            
            if (this.state.currentWord && !this.state.gameOver) {
                this.checkAnswer(answer);
            }
            
            input.value = '';
            input.focus();
        });
        
        // Add game-specific styles
        const gameStyles = document.createElement('style');
        gameStyles.textContent = `
            .palabra-arena-ui {
                position: absolute;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                width: 80%;
                background-color: rgba(0, 0, 0, 0.7);
                border-radius: 10px;
                padding: 15px;
                color: white;
                font-family: 'Poppins', sans-serif;
                z-index: 100;
            }
            
            .word-challenge-container {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .word-display {
                text-align: center;
                margin-bottom: 10px;
            }
            
            .word-display h3 {
                margin: 0;
                font-size: 1.2rem;
                color: #f6c23e;
            }
            
            .word-display p {
                margin: 5px 0;
                font-size: 1.5rem;
                font-weight: bold;
            }
            
            #word-form {
                display: flex;
                width: 100%;
                max-width: 400px;
            }
            
            #word-input {
                flex-grow: 1;
                padding: 10px;
                border: none;
                border-radius: 5px 0 0 5px;
                font-family: 'Poppins', sans-serif;
                font-size: 1rem;
            }
            
            #word-form button {
                border-radius: 0 5px 5px 0;
            }
            
            .feedback-correct {
                color: #1cc88a;
                font-weight: bold;
                animation: pulse 0.5s;
            }
            
            .feedback-incorrect {
                color: #e74a3b;
                font-weight: bold;
                animation: shake 0.5s;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(gameStyles);
    },
    
    // Check player's answer
    checkAnswer: function(answer) {
        if (!this.state.currentWord) return;
        
        const isCorrect = answer.toLowerCase() === this.state.currentWord.english.toLowerCase();
        const wordDisplay = document.getElementById('challenge-word');
        
        if (isCorrect) {
            // Show correct feedback
            wordDisplay.textContent = '¡Correcto!';
            wordDisplay.className = 'feedback-correct';
            
            // Damage enemy
            if (this.state.currentEnemy) {
                this.damageEnemy(this.state.currentEnemy, 25);
            }
            
            // Award points
            this.state.score += 10 * this.state.level;
            
            // Update score display
            if (this.assets.scoreText) {
                this.assets.scoreText.setText(`Score: ${this.state.score}`);
            }
            
            // Get next word after delay
            setTimeout(() => {
                this.getNextWord();
                wordDisplay.className = '';
            }, 1000);
        } else {
            // Show incorrect feedback
            wordDisplay.textContent = `Incorrect! The answer was: ${this.state.currentWord.english}`;
            wordDisplay.className = 'feedback-incorrect';
            
            // Take damage
            this.takeDamage(10);
            
            // Get next word after delay
            setTimeout(() => {
                this.getNextWord();
                wordDisplay.className = '';
            }, 2000);
        }
    },
    
    // Get next word challenge
    getNextWord: function() {
        // Filter words by current level and enemy type preference
        let availableWords = this.wordDatabase.filter(word => {
            return word.level <= this.state.level && 
                  (!this.state.currentEnemy || word.type === this.state.currentEnemy.wordType);
        });
        
        // If no words match the criteria, use any words for the current level
        if (availableWords.length === 0) {
            availableWords = this.wordDatabase.filter(word => word.level <= this.state.level);
        }
        
        // Still no words? Use all words
        if (availableWords.length === 0) {
            availableWords = this.wordDatabase;
        }
        
        // Select a random word
        const randomIndex = Math.floor(Math.random() * availableWords.length);
        this.state.currentWord = availableWords[randomIndex];
        
        // Update display
        const wordDisplay = document.getElementById('challenge-word');
        if (wordDisplay) {
            wordDisplay.textContent = this.state.currentWord.spanish;
            wordDisplay.className = '';
        }
        
        // Focus on input
        const wordInput = document.getElementById('word-input');
        if (wordInput) {
            wordInput.focus();
        }
    },
    
    // Damage enemy
    damageEnemy: function(enemy, amount) {
        enemy.health -= amount;
        
        // Update health bar
        if (enemy.healthBar) {
            const healthPercent = Math.max(0, enemy.health / enemy.maxHealth);
            enemy.healthBar.clear();
            enemy.healthBar.fillStyle(0xff0000, 1);
            enemy.healthBar.fillRect(0, 0, 100 * healthPercent, 10);
        }
        
        // Check if enemy is defeated
        if (enemy.health <= 0) {
            this.defeatEnemy(enemy);
        }
    },
    
    // Defeat enemy
    defeatEnemy: function(enemy) {
        // Remove enemy from game
        if (enemy.sprite) {
            enemy.sprite.destroy();
        }
        if (enemy.healthBar) {
            enemy.healthBar.destroy();
        }
        
        // Remove from enemies array
        const index = this.state.enemies.indexOf(enemy);
        if (index > -1) {
            this.state.enemies.splice(index, 1);
        }
        
        // Award XP
        const xpGained = 25 * this.state.level;
        CerveLinguaGames.progress.awardXP(xpGained);
        
        // Show message
        CerveLinguaGames.ui.showCerveMessage(`¡Excelente! You defeated the ${enemy.name}!`);
        
        // Check if level complete
        if (this.state.enemies.length === 0) {
            this.completeLevel();
        } else {
            // Set next enemy as current
            this.state.currentEnemy = this.state.enemies[0];
            this.getNextWord();
        }
    },
    
    // Complete level
    completeLevel: function() {
        this.state.level++;
        
        // Update level text
        if (this.assets.levelText) {
            this.assets.levelText.setText(`Level: ${this.state.level}`);
        }
        
        // Show level complete message
        const gameContainer = document.getElementById('game-container');
        const levelCompleteOverlay = document.createElement('div');
        levelCompleteOverlay.className = 'game-overlay';
        levelCompleteOverlay.innerHTML = `
            <h2>¡Nivel Completado!</h2>
            <p>You've completed level ${this.state.level - 1}!</p>
            <p>Score: ${this.state.score}</p>
            <div class="buttons">
                <button id="next-level-btn" class="game-ui-button">Next Level</button>
                <button id="quit-game-btn" class="game-ui-button secondary">Quit</button>
            </div>
        `;
        gameContainer.appendChild(levelCompleteOverlay);
        
        // Add event listeners
        document.getElementById('next-level-btn').addEventListener('click', () => {
            gameContainer.removeChild(levelCompleteOverlay);
            this.startLevel(this.state.level);
        });
        
        document.getElementById('quit-game-btn').addEventListener('click', () => {
            // Save progress
            CerveLinguaGames.progress.saveGameProgress(1, this.state.score, {
                level_reached: this.state.level - 1,
                words_translated: this.state.score / 10
            });
            
            // Close game modal
            const gameModal = document.getElementById('game-modal');
            if (gameModal) {
                gameModal.style.display = 'none';
            }
        });
    },
    
    // Take damage
    takeDamage: function(amount) {
        this.state.health -= amount;
        
        // Update health bar
        if (this.assets.healthBar) {
            const healthPercent = Math.max(0, this.state.health / 100);
            this.assets.healthBar.clear();
            this.assets.healthBar.fillStyle(0x00ff00, 1);
            this.assets.healthBar.fillRect(0, 0, 200 * healthPercent, 20);
        }
        
        // Check if game over
        if (this.state.health <= 0) {
            this.gameOver();
        }
    },
    
    // Game over
    gameOver: function() {
        this.state.gameOver = true;
        
        // Show game over message
        const gameContainer = document.getElementById('game-container');
        const gameOverOverlay = document.createElement('div');
        gameOverOverlay.className = 'game-overlay';
        gameOverOverlay.innerHTML = `
            <h2>Game Over</h2>
            <p>You reached level ${this.state.level}</p>
            <p>Final score: ${this.state.score}</p>
            <div class="buttons">
                <button id="retry-btn" class="game-ui-button">Try Again</button>
                <button id="quit-game-btn" class="game-ui-button secondary">Quit</button>
            </div>
        `;
        gameContainer.appendChild(gameOverOverlay);
        
        // Add event listeners
        document.getElementById('retry-btn').addEventListener('click', () => {
            gameContainer.removeChild(gameOverOverlay);
            this.resetGame();
        });
        
        document.getElementById('quit-game-btn').addEventListener('click', () => {
            // Save progress
            CerveLinguaGames.progress.saveGameProgress(1, this.state.score, {
                level_reached: this.state.level,
                words_translated: this.state.score / 10
            });
            
            // Close game modal
            const gameModal = document.getElementById('game-modal');
            if (gameModal) {
                gameModal.style.display = 'none';
            }
        });
    },
    
    // Reset game
    resetGame: function() {
        // Reset game state
        this.state.score = 0;
        this.state.level = 1;
        this.state.health = 100;
        this.state.enemies = [];
        this.state.currentEnemy = null;
        this.state.currentWord = null;
        this.state.gameOver = false;
        
        // Restart game
        if (this.game) {
            this.game.destroy(true);
            this.game = new Phaser.Game(this.config);
        }
    },
    
    // Start a level
    startLevel: function(level) {
        // Clear existing enemies
        this.state.enemies.forEach(enemy => {
            if (enemy.sprite) {
                enemy.sprite.destroy();
            }
            if (enemy.healthBar) {
                enemy.healthBar.destroy();
            }
        });
        
        this.state.enemies = [];
        
        // Create new enemies for the level
        const enemyCount = Math.min(level + 1, 5); // Max 5 enemies per level
        
        for (let i = 0; i < enemyCount; i++) {
            // Select enemy type based on level
            let enemyTypeIndex = 0;
            if (level >= 3) {
                enemyTypeIndex = Math.floor(Math.random() * this.enemyTypes.length);
            } else if (level >= 2) {
                enemyTypeIndex = Math.floor(Math.random() * 2); // Only first two types
            }
            
            const enemyType = this.enemyTypes[enemyTypeIndex];
            
            // Create enemy
            const enemy = {
                ...enemyType,
                health: enemyType.health + (level - 1) * 25, // Increase health with level
                maxHealth: enemyType.health + (level - 1) * 25,
                sprite: null,
                healthBar: null
            };
            
            this.state.enemies.push(enemy);
        }
        
        // Set current enemy
        this.state.currentEnemy = this.state.enemies[0];
        
        // Get first word
        this.getNextWord();
    }
};

// Phaser scene functions
function preload() {
    // Load assets
    this.load.image('background', 'img/games/palabra_arena.png');
    this.load.image('player', 'img/CerveLingua_Avatar.png');
    this.load.image('enemy1', 'img/pollito.png');
    this.load.image('enemy2', 'img/torete.png');
    this.load.image('enemy3', 'img/zoroListo.png');
}

function create() {
    // Create background
    this.add.image(400, 300, 'background');
    
    // Create player
    palabraArena.assets.player = this.add.image(150, 400, 'player').setScale(0.2);
    
    // Create UI elements
    palabraArena.assets.scoreText = this.add.text(20, 20, 'Score: 0', {
        fontFamily: 'Poppins',
        fontSize: '24px',
        fill: '#fff'
    });
    
    palabraArena.assets.levelText = this.add.text(20, 60, 'Level: 1', {
        fontFamily: 'Poppins',
        fontSize: '24px',
        fill: '#fff'
    });
    
    // Create health bar
    const healthBarBg = this.add.graphics();
    healthBarBg.fillStyle(0x333333, 1);
    healthBarBg.fillRect(20, 100, 200, 20);
    
    palabraArena.assets.healthBar = this.add.graphics();
    palabraArena.assets.healthBar.fillStyle(0x00ff00, 1);
    palabraArena.assets.healthBar.fillRect(20, 100, 200, 1);
    
    // Start first level
    palabraArena.startLevel(1);
    
    // Create enemies on screen
    palabraArena.state.enemies.forEach((enemy, index) => {
        // Create enemy sprite
        enemy.sprite = this.add.image(600, 200 + index * 100, enemy.sprite).setScale(0.15);
        
        // Create enemy health bar
        const enemyHealthBarBg = this.add.graphics();
        enemyHealthBarBg.fillStyle(0x333333, 1);
        enemyHealthBarBg.fillRect(enemy.sprite.x - 50, enemy.sprite.y - 50, 100, 10);
        
        enemy.healthBar = this.add.graphics();
        enemy.healthBar.fillStyle(0xff0000, 1);
        enemy.healthBar.fillRect(enemy.sprite.x - 50, enemy.sprite.y - 50, 100, 10);
    });
}

function update() {
    // Game loop updates
    if (palabraArena.state.gameOver) return;
    
    // Animate player
    if (palabraArena.assets.player) {
        palabraArena.assets.player.y = 400 + Math.sin(this.time.now / 500) * 10;
    }
    
    // Animate enemies
    palabraArena.state.enemies.forEach(enemy => {
        if (enemy.sprite) {
            enemy.sprite.x = 600 + Math.sin(this.time.now / 1000 + palabraArena.state.enemies.indexOf(enemy)) * 20;
        }
    });
}