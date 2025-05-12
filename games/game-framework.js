/**
 * CerveLingua Game Framework
 * A modular framework for Spanish learning games using Phaser.js
 */

// Main Game Framework
const CerveLinguaGames = {
    // Configuration settings
    config: {
        defaultWidth: 800,
        defaultHeight: 600,
        backgroundColor: '#f8f9fa',
        parent: 'game-container',
        fontFamily: 'Poppins, sans-serif',
        primaryColor: '#4e73df',
        secondaryColor: '#f6c23e',
        successColor: '#1cc88a',
        dangerColor: '#e74a3b'
    },
    
    // Current game instance
    currentGame: null,
    
    // User data
    userData: {
        userId: null,
        username: null,
        level: null,
        xpPoints: 0
    },
    
    // Vocabulary database - will be populated from server
    vocabulary: {
        words: [],
        phrases: [],
        verbs: []
    },
    
    // Audio manager
    audio: {
        sounds: {},
        music: {},
        
        // Play a sound effect
        playSound: function(key, volume = 1) {
            if (this.sounds[key]) {
                this.sounds[key].volume = volume;
                this.sounds[key].play();
            }
        },
        
        // Play background music
        playMusic: function(key, volume = 0.5, loop = true) {
            if (this.music[key]) {
                this.music[key].volume = volume;
                this.music[key].loop = loop;
                this.music[key].play();
            }
        },
        
        // Stop all music
        stopMusic: function() {
            for (const key in this.music) {
                if (this.music[key].playing()) {
                    this.music[key].stop();
                }
            }
        },
        
        // Load a sound
        loadSound: function(key, url) {
            this.sounds[key] = new Audio(url);
        },
        
        // Load music
        loadMusic: function(key, url) {
            this.music[key] = new Audio(url);
        }
    },
    
    // Progress tracking
    progress: {
        saveGameProgress: function(gameId, score, data = {}) {
            // Save game progress to server
            return fetch('api/save-game-progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    game_id: gameId,
                    user_id: CerveLinguaGames.userData.userId,
                    score: score,
                    data: data
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Progress saved:', data);
                return data;
            })
            .catch(error => {
                console.error('Error saving progress:', error);
                return null;
            });
        },
        
        // Award XP to the user
        awardXP: function(amount) {
            CerveLinguaGames.userData.xpPoints += amount;
            
            // Update UI
            const xpElement = document.getElementById('player-xp');
            if (xpElement) {
                xpElement.textContent = CerveLinguaGames.userData.xpPoints;
            }
            
            // Show XP gain animation
            CerveLinguaGames.ui.showXPGain(amount);
            
            return this.saveGameProgress(CerveLinguaGames.currentGame.gameId, 0, {
                xp_gained: amount,
                total_xp: CerveLinguaGames.userData.xpPoints
            });
        }
    },
    
    // UI utilities
    ui: {
        // Show XP gain animation
        showXPGain: function(amount) {
            const gameContainer = document.getElementById('game-container');
            const xpGainElement = document.createElement('div');
            xpGainElement.className = 'xp-gain';
            xpGainElement.textContent = `+${amount} XP`;
            gameContainer.appendChild(xpGainElement);
            
            // Animate and remove
            setTimeout(() => {
                xpGainElement.classList.add('fade-out');
                setTimeout(() => {
                    gameContainer.removeChild(xpGainElement);
                }, 1000);
            }, 1500);
        },
        
        // Show message from CerveLingua avatar
        showCerveMessage: function(message, duration = 5000) {
            const speechBubble = document.getElementById('cerve-speech');
            if (speechBubble) {
                const messageElement = speechBubble.querySelector('p');
                if (messageElement) {
                    messageElement.textContent = message;
                    speechBubble.style.display = 'block';
                    
                    if (duration > 0) {
                        setTimeout(() => {
                            speechBubble.style.display = 'none';
                        }, duration);
                    }
                }
            }
        }
    },
    
    // Initialize the game framework
    init: function(gameId, userId, username, level) {
        // Set user data
        this.userData.userId = userId;
        this.userData.username = username;
        this.userData.level = level;
        
        // Load Phaser if not already loaded
        if (typeof Phaser === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/phaser@3.55.2/dist/phaser.min.js';
            script.onload = () => this.initializeGame(gameId);
            document.head.appendChild(script);
        } else {
            this.initializeGame(gameId);
        }
    },
    
    // Initialize the specific game
    initializeGame: function(gameId) {
        // Clear any existing game
        if (this.currentGame && this.currentGame.instance) {
            this.currentGame.instance.destroy(true);
            this.currentGame = null;
        }
        
        // Load the specific game module
        switch(gameId) {
            case 'palabraArena':
                this.loadGame('palabraArena');
                break;
            case 'carreraConjugacion':
                this.loadGame('carreraConjugacion');
                break;
            case 'caminoToro':
                this.loadGame('caminoToro');
                break;
            case 'islaVerbos':
                this.loadGame('islaVerbos');
                break;
            case 'mercadoMisterioso':
                this.loadGame('mercadoMisterioso');
                break;
            default:
                console.error('Unknown game ID:', gameId);
                this.ui.showCerveMessage('Lo siento, no puedo encontrar ese juego.');
        }
    },
    
    // Load a specific game module
    loadGame: function(gameId) {
        const script = document.createElement('script');
        script.src = `games/${gameId}.js`;
        script.onload = () => {
            if (window[gameId]) {
                this.currentGame = {
                    id: gameId,
                    module: window[gameId],
                    instance: null
                };
                
                // Initialize the game
                this.currentGame.module.init();
            } else {
                console.error(`Game module ${gameId} not found after loading`);
                this.ui.showCerveMessage('Lo siento, hubo un problema cargando el juego.');
            }
        };
        script.onerror = () => {
            console.error(`Failed to load game module: ${gameId}`);
            this.ui.showCerveMessage('Lo siento, no pude cargar el juego.');
        };
        document.head.appendChild(script);
    }
};

// Add CSS for game UI elements
const gameStyles = document.createElement('style');
gameStyles.textContent = `
    .xp-gain {
        position: absolute;
        top: 50px;
        right: 50px;
        background-color: ${CerveLinguaGames.config.successColor};
        color: white;
        padding: 10px 15px;
        border-radius: 20px;
        font-family: ${CerveLinguaGames.config.fontFamily};
        font-weight: bold;
        animation: float-up 2s ease-out;
        z-index: 1000;
    }
    
    @keyframes float-up {
        0% { opacity: 0; transform: translateY(20px); }
        10% { opacity: 1; }
        80% { opacity: 1; }
        100% { opacity: 0; transform: translateY(-30px); }
    }
    
    .fade-out {
        opacity: 0;
        transition: opacity 1s ease-out;
    }
    
    .game-ui-button {
        background-color: ${CerveLinguaGames.config.primaryColor};
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 15px;
        font-family: ${CerveLinguaGames.config.fontFamily};
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    
    .game-ui-button:hover {
        background-color: #375ad3;
    }
    
    .game-ui-button.secondary {
        background-color: ${CerveLinguaGames.config.secondaryColor};
    }
    
    .game-ui-button.secondary:hover {
        background-color: #e0b035;
    }
    
    .game-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        color: white;
        font-family: ${CerveLinguaGames.config.fontFamily};
    }
    
    .game-overlay h2 {
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    
    .game-overlay p {
        font-size: 1.2rem;
        margin-bottom: 2rem;
    }
    
    .game-overlay .buttons {
        display: flex;
        gap: 1rem;
    }
`;
document.head.appendChild(gameStyles);