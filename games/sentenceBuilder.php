<?php
/**
 * sentenceBuilder.php
 *
 * Interactive Spanish Sentence Builder Game
 * Players drag and drop words to form correct Spanish sentences
 *
 * Coding-Standards:
 *   • camelCase für Variablen/Funktionen, PascalCase für Klassen
 *   • Deutsch für selbst definierte Bezeichnungen
 *   • Einrückung: 4 Leerzeichen
 *   • Logikblöcke durch Leerzeilen trennen
 */

require_once "../includes/config.php";
require_once "../includes/functions.php";

// Prüfe, ob Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Sentence data with different difficulty levels
$sentences = [
    // Level 1 - Basic sentences
    [
        'level' => 1,
        'spanish' => 'Me gusta la comida.',
        'english' => 'I like the food.',
        'words' => ['Me', 'gusta', 'la', 'comida', '.'],
        'hint' => 'Express that you like something'
    ],
    [
        'level' => 1,
        'spanish' => 'Ella es mi hermana.',
        'english' => 'She is my sister.',
        'words' => ['Ella', 'es', 'mi', 'hermana', '.'],
        'hint' => 'Talk about family relationships'
    ],
    [
        'level' => 1,
        'spanish' => 'El perro es grande.',
        'english' => 'The dog is big.',
        'words' => ['El', 'perro', 'es', 'grande', '.'],
        'hint' => 'Describe the size of an animal'
    ],
    // Level 2 - Medium sentences
    [
        'level' => 2,
        'spanish' => 'Voy a la escuela todos los días.',
        'english' => 'I go to school every day.',
        'words' => ['Voy', 'a', 'la', 'escuela', 'todos', 'los', 'días', '.'],
        'hint' => 'Talk about daily routines'
    ],
    [
        'level' => 2,
        'spanish' => 'Mi madre cocina muy bien.',
        'english' => 'My mother cooks very well.',
        'words' => ['Mi', 'madre', 'cocina', 'muy', 'bien', '.'],
        'hint' => 'Compliment someone\'s cooking skills'
    ],
    [
        'level' => 2,
        'spanish' => 'Los niños juegan en el parque.',
        'english' => 'The children play in the park.',
        'words' => ['Los', 'niños', 'juegan', 'en', 'el', 'parque', '.'],
        'hint' => 'Describe where children play'
    ],
    // Level 3 - Advanced sentences
    [
        'level' => 3,
        'spanish' => 'Mañana viajaremos a España en avión.',
        'english' => 'Tomorrow we will travel to Spain by plane.',
        'words' => ['Mañana', 'viajaremos', 'a', 'España', 'en', 'avión', '.'],
        'hint' => 'Talk about future travel plans'
    ],
    [
        'level' => 3,
        'spanish' => 'He estudiado español durante tres años.',
        'english' => 'I have studied Spanish for three years.',
        'words' => ['He', 'estudiado', 'español', 'durante', 'tres', 'años', '.'],
        'hint' => 'Express how long you\'ve been learning'
    ],
    [
        'level' => 3,
        'spanish' => 'Si tuviera dinero, compraría una casa.',
        'english' => 'If I had money, I would buy a house.',
        'words' => ['Si', 'tuviera', 'dinero', ',', 'compraría', 'una', 'casa', '.'],
        'hint' => 'Express a hypothetical situation'
    ]
];

$page_title = "Sentence Builder Game";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CerveLingua - Sentence Builder</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="../css/styles.css" />
    <link rel="stylesheet" href="../css/dashboard.css" />
    <link rel="stylesheet" href="../css/games.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        .game-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            margin-top: 100px;
            margin-bottom: 50px;
        }

        .game-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 15px;
            color: white;
        }

        .game-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .game-header p {
            margin: 10px 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .game-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .stat-item i {
            font-size: 1.2rem;
            color: #3498db;
        }

        .level-selector {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .level-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .level-btn.active {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
        }

        .level-btn:not(.active) {
            background: #ecf0f1;
            color: #7f8c8d;
        }

        .level-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .game-area {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .sentence-info {
            text-align: center;
            margin-bottom: 25px;
        }

        .english-text {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .hint-text {
            color: #7f8c8d;
            font-style: italic;
            font-size: 1rem;
        }

        .drop-zone {
            min-height: 80px;
            background: #f8f9fa;
            border: 3px dashed #bdc3c7;
            border-radius: 12px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 20px 0;
            transition: all 0.3s ease;
        }

        .drop-zone.drag-over {
            border-color: #3498db;
            background: #ebf3fd;
            transform: scale(1.02);
        }

        .word-bank {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            padding: 25px;
            background: #f1f2f6;
            border-radius: 12px;
            margin: 20px 0;
        }

        .word-tile {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: grab;
            user-select: none;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(116, 185, 255, 0.3);
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .word-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(116, 185, 255, 0.4);
        }

        .word-tile:active {
            cursor: grabbing;
        }

        .word-tile.placed {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            margin: 5px;
            cursor: pointer;
        }

        .word-tile.placed:hover {
            background: linear-gradient(135deg, #27ae60, #229954);
        }

        .game-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .feedback {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .feedback.correct {
            background: linear-gradient(135deg, #d5e7d5, #c8e6c9);
            color: #2e7d32;
            border: 2px solid #4caf50;
        }

        .feedback.incorrect {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            color: #c62828;
            border: 2px solid #f44336;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: #ecf0f1;
            border-radius: 6px;
            overflow: hidden;
            margin: 20px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            transition: width 0.5s ease;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            color: #2c3e50;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .back-btn:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .avatar-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            z-index: 1000;
        }

        .speech-bubble {
            background: white;
            border-radius: 20px;
            padding: 15px;
            margin-right: 15px;
            max-width: 200px;
            font-size: 14px;
            color: #2c3e50;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            display: none;
        }

        .speech-bubble::after {
            content: '';
            position: absolute;
            right: -10px;
            top: 50%;
            transform: translateY(-50%);
            border: 10px solid transparent;
            border-left-color: white;
        }

        .speech-bubble.show {
            display: block;
            animation: fadeInScale 0.3s ease;
        }

        #cerve-avatar {
            width: 80px;
            height: auto;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        #cerve-avatar:hover {
            transform: scale(1.1);
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes celebration {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .celebration {
            animation: celebration 0.6s ease-in-out;
        }

        @media (max-width: 768px) {
            .game-container {
                margin: 20px;
                padding: 15px;
                margin-top: 80px;
            }

            .game-header h1 {
                font-size: 2rem;
            }

            .word-tile {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .game-stats {
                flex-direction: column;
                gap: 10px;
            }

            .level-selector {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <a href="../lessons.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Lessons
    </a>

    <div class="game-container">
        <div class="game-header">
            <h1><i class="fas fa-puzzle-piece"></i> Sentence Builder</h1>
            <p>Build correct Spanish sentences by arranging words in the proper order</p>
        </div>

        <div class="game-stats">
            <div class="stat-item">
                <i class="fas fa-trophy"></i>
                <span>Score: <span id="score">0</span></span>
            </div>
            <div class="stat-item">
                <i class="fas fa-check-circle"></i>
                <span>Correct: <span id="correct-count">0</span></span>
            </div>
            <div class="stat-item">
                <i class="fas fa-times-circle"></i>
                <span>Incorrect: <span id="incorrect-count">0</span></span>
            </div>
            <div class="stat-item">
                <i class="fas fa-clock"></i>
                <span>Time: <span id="timer">0:00</span></span>
            </div>
        </div>

        <div class="level-selector">
            <button class="level-btn active" data-level="1">
                <i class="fas fa-star"></i> Beginner
            </button>
            <button class="level-btn" data-level="2">
                <i class="fas fa-star"></i><i class="fas fa-star"></i> Intermediate
            </button>
            <button class="level-btn" data-level="3">
                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i> Advanced
            </button>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
        </div>

        <div class="game-area">
            <div class="sentence-info">
                <div class="english-text" id="english-text">Click a level to start!</div>
                <div class="hint-text" id="hint-text">Select your difficulty level above</div>
            </div>

            <div class="drop-zone" id="drop-zone">
                <p style="color: #7f8c8d; font-style: italic;">Drag words here to build the sentence</p>
            </div>

            <div class="word-bank" id="word-bank">
                <!-- Words will be populated here -->
            </div>

            <div class="game-controls">
                <button class="btn btn-warning" id="clear-btn" disabled>
                    <i class="fas fa-trash"></i> Clear
                </button>
                <button class="btn btn-primary" id="check-btn" disabled>
                    <i class="fas fa-check"></i> Check Answer
                </button>
                <button class="btn btn-success" id="next-btn" style="display: none;">
                    <i class="fas fa-arrow-right"></i> Next Sentence
                </button>
            </div>

            <div class="feedback" id="feedback" style="display: none;"></div>
        </div>
    </div>

    <!-- Avatar -->
    <div class="avatar-container">
        <div class="speech-bubble" id="cerve-speech">
            <p>¡Hola! Ready to build some Spanish sentences?</p>
        </div>
        <img src="../img/CerveLingua_Avatar.png" alt="CerveLingua Avatar" id="cerve-avatar" />
    </div>

    <script>
        // Game data and variables
        const sentences = <?php echo json_encode($sentences); ?>;
        let currentLevel = 1;
        let currentSentenceIndex = 0;
        let currentSentence = null;
        let placedWords = [];
        let score = 0;
        let correctCount = 0;
        let incorrectCount = 0;
        let startTime = Date.now();
        let gameTimer;
        let levelSentences = [];

        // DOM elements
        const levelBtns = document.querySelectorAll('.level-btn');
        const dropZone = document.getElementById('drop-zone');
        const wordBank = document.getElementById('word-bank');
        const englishText = document.getElementById('english-text');
        const hintText = document.getElementById('hint-text');
        const clearBtn = document.getElementById('clear-btn');
        const checkBtn = document.getElementById('check-btn');
        const nextBtn = document.getElementById('next-btn');
        const feedback = document.getElementById('feedback');
        const scoreElement = document.getElementById('score');
        const correctElement = document.getElementById('correct-count');
        const incorrectElement = document.getElementById('incorrect-count');
        const timerElement = document.getElementById('timer');
        const progressFill = document.getElementById('progress-fill');
        const speechBubble = document.getElementById('cerve-speech');
        const avatar = document.getElementById('cerve-avatar');

        // Avatar messages
        const avatarMessages = {
            welcome: "¡Hola! Ready to build some Spanish sentences?",
            correct: "¡Excelente! That's correct!",
            incorrect: "¡Inténtalo de nuevo! Try again!",
            levelUp: "¡Muy bien! Moving to the next level!",
            encouragement: "You're doing great! Keep going!",
            completed: "¡Fantástico! You've completed this level!"
        };

        // Initialize game
        function initGame() {
            updateTimer();
            gameTimer = setInterval(updateTimer, 1000);
            filterSentencesByLevel();
            showAvatarMessage(avatarMessages.welcome);
        }

        // Level selection
        levelBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                levelBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentLevel = parseInt(this.dataset.level);
                currentSentenceIndex = 0;
                filterSentencesByLevel();
                loadCurrentSentence();
            });
        });

        // Filter sentences by level
        function filterSentencesByLevel() {
            levelSentences = sentences.filter(s => s.level === currentLevel);
            updateProgress();
        }

        // Load current sentence
        function loadCurrentSentence() {
            if (currentSentenceIndex >= levelSentences.length) {
                completedLevel();
                return;
            }

            currentSentence = levelSentences[currentSentenceIndex];
            placedWords = [];
            
            englishText.textContent = currentSentence.english;
            hintText.textContent = `Hint: ${currentSentence.hint}`;
            
            // Clear drop zone
            dropZone.innerHTML = '<p style="color: #7f8c8d; font-style: italic;">Drag words here to build the sentence</p>';
            
            // Shuffle and display words
            const shuffledWords = [...currentSentence.words].sort(() => Math.random() - 0.5);
            displayWords(shuffledWords);
            
            // Reset buttons
            clearBtn.disabled = true;
            checkBtn.disabled = true;
            nextBtn.style.display = 'none';
            feedback.style.display = 'none';
            
            updateProgress();
        }

        // Display words in word bank
        function displayWords(words) {
            wordBank.innerHTML = '';
            words.forEach((word, index) => {
                const wordTile = document.createElement('div');
                wordTile.className = 'word-tile';
                wordTile.textContent = word;
                wordTile.draggable = true;
                wordTile.dataset.word = word;
                wordTile.dataset.index = index;
                
                wordTile.addEventListener('dragstart', handleDragStart);
                wordTile.addEventListener('click', () => addWordToSentence(word, wordTile));
                
                wordBank.appendChild(wordTile);
            });
        }

        // Drag and drop handlers
        function handleDragStart(e) {
            e.dataTransfer.setData('text/plain', e.target.dataset.word);
            e.dataTransfer.setData('text/index', e.target.dataset.index);
        }

        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            const word = e.dataTransfer.getData('text/plain');
            const sourceIndex = e.dataTransfer.getData('text/index');
            
            addWordToSentence(word, null, sourceIndex);
        });

        // Add word to sentence
        function addWordToSentence(word, wordElement, sourceIndex) {
            // Remove from word bank
            if (wordElement) {
                wordElement.remove();
            } else {
                const wordTile = wordBank.querySelector(`[data-index="${sourceIndex}"]`);
                if (wordTile) wordTile.remove();
            }
            
            // Add to drop zone
            if (placedWords.length === 0) {
                dropZone.innerHTML = '';
            }

            const placedWord = document.createElement('span');
            placedWord.className = 'word-tile placed';
            placedWord.textContent = word;
            placedWord.addEventListener('click', () => removeWordFromSentence(placedWord, word));
            
            dropZone.appendChild(placedWord);
            placedWords.push(word);
            
            // Enable buttons
            clearBtn.disabled = false;
            checkBtn.disabled = false;
        }

        // Remove word from sentence
        function removeWordFromSentence(wordElement, word) {
            wordElement.remove();
            placedWords = placedWords.filter(w => w !== word);
            
            // Add back to word bank
            const wordTile = document.createElement('div');
            wordTile.className = 'word-tile';
            wordTile.textContent = word;
            wordTile.draggable = true;
            wordTile.dataset.word = word;
            wordTile.dataset.index = Date.now(); // Unique index
            
            wordTile.addEventListener('dragstart', handleDragStart);
            wordTile.addEventListener('click', () => addWordToSentence(word, wordTile));
            
            wordBank.appendChild(wordTile);
            
            // Check if drop zone is empty
            if (placedWords.length === 0) {
                dropZone.innerHTML = '<p style="color: #7f8c8d; font-style: italic;">Drag words here to build the sentence</p>';
                clearBtn.disabled = true;
                checkBtn.disabled = true;
            }
        }

        // Clear sentence
        clearBtn.addEventListener('click', function() {
            placedWords.forEach(word => {
                const wordTile = document.createElement('div');
                wordTile.className = 'word-tile';
                wordTile.textContent = word;
                wordTile.draggable = true;
                wordTile.dataset.word = word;
                wordTile.dataset.index = Date.now() + Math.random();
                
                wordTile.addEventListener('dragstart', handleDragStart);
                wordTile.addEventListener('click', () => addWordToSentence(word, wordTile));
                
                wordBank.appendChild(wordTile);
            });
            
            placedWords = [];
            dropZone.innerHTML = '<p style="color: #7f8c8d; font-style: italic;">Drag words here to build the sentence</p>';
            this.disabled = true;
            checkBtn.disabled = true;
            feedback.style.display = 'none';
        });

        // Check answer
        checkBtn.addEventListener('click', function() {
            const userSentence = placedWords.join(' ');
            const correctSentence = currentSentence.spanish;
            
            if (userSentence === correctSentence) {
                // Correct answer
                feedback.className = 'feedback correct';
                feedback.innerHTML = `<i class="fas fa-check-circle"></i> ¡Correcto! "${correctSentence}"`;
                feedback.style.display = 'block';
                
                score += (currentLevel * 25);
                correctCount++;
                
                showAvatarMessage(avatarMessages.correct);
                dropZone.classList.add('celebration');
                setTimeout(() => dropZone.classList.remove('celebration'), 600);
                
                nextBtn.style.display = 'inline-flex';
                this.disabled = true;
            } else {
                // Incorrect answer
                feedback.className = 'feedback incorrect';
                feedback.innerHTML = `<i class="fas fa-times-circle"></i> Incorrect. Try again! The correct answer is: "${correctSentence}"`;
                feedback.style.display = 'block';
                
                incorrectCount++;
                showAvatarMessage(avatarMessages.incorrect);
            }
            
            updateStats();
        });

        // Next sentence
        nextBtn.addEventListener('click', function() {
            currentSentenceIndex++;
            loadCurrentSentence();
            showAvatarMessage(avatarMessages.encouragement);
        });

        // Update statistics
        function updateStats() {
            scoreElement.textContent = score;
            correctElement.textContent = correctCount;
            incorrectElement.textContent = incorrectCount;
        }

        // Update progress bar
        function updateProgress() {
            const progress = (currentSentenceIndex / levelSentences.length) * 100;
            progressFill.style.width = `${progress}%`;
        }

        // Update timer
        function updateTimer() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        // Show avatar message
        function showAvatarMessage(message) {
            speechBubble.querySelector('p').textContent = message;
            speechBubble.classList.add('show');
            
            setTimeout(() => {
                speechBubble.classList.remove('show');
            }, 3000);
        }

        // Avatar click handler
        avatar.addEventListener('click', function() {
            const messages = Object.values(avatarMessages);
            const randomMessage = messages[Math.floor(Math.random() * messages.length)];
            showAvatarMessage(randomMessage);
        });

        // Completed level
        function completedLevel() {
            showAvatarMessage(avatarMessages.completed);
            
            feedback.className = 'feedback correct';
            feedback.innerHTML = `
                <div style="text-align: center;">
                    <i class="fas fa-trophy" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h3>¡Nivel Completado!</h3>
                    <p>You've completed all sentences in Level ${currentLevel}!</p>
                    <p>Final Score: ${score} points</p>
                    ${currentLevel < 3 ? '<p>Try the next level for a greater challenge!</p>' : '<p>¡Felicidades! You\'ve mastered all levels!</p>'}
                </div>
            `;
            feedback.style.display = 'block';
            
            // Disable game controls
            clearBtn.disabled = true;
            checkBtn.disabled = true;
            nextBtn.style.display = 'none';
            
            // Show celebration animation
            document.querySelector('.game-container').classList.add('celebration');
            setTimeout(() => {
                document.querySelector('.game-container').classList.remove('celebration');
            }, 1000);
        }

        // Save game progress (similar to lessons)
        function saveGameProgress() {
            const gameData = {
                game_id: 7, // Sentence Builder game ID
                score: score,
                level_reached: currentLevel,
                time_spent: Math.floor((Date.now() - startTime) / 1000),
                sentences_completed: currentSentenceIndex
            };

            fetch('../save_game_progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(gameData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Game progress saved successfully');
                    if (data.xp_earned > 0) {
                        showXPNotification(data.xp_earned);
                    }
                } else {
                    console.error('Error saving game progress:', data.message);
                }
            })
            .catch(error => {
                console.error('Error saving game progress:', error);
            });
        }

        // XP notification
        function showXPNotification(xpEarned) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #f39c12, #e67e22);
                color: white;
                padding: 15px 25px;
                border-radius: 25px;
                font-weight: 600;
                z-index: 1001;
                transform: translateX(300px);
                transition: transform 0.3s ease;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            `;
            notification.innerHTML = `<i class="fas fa-star"></i> +${xpEarned} XP earned!`;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                notification.style.transform = 'translateX(300px)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Auto-save progress periodically
        setInterval(() => {
            if (score > 0) {
                saveGameProgress();
            }
        }, 30000); // Save every 30 seconds

        // Save progress when leaving page
        window.addEventListener('beforeunload', function() {
            if (score > 0) {
                saveGameProgress();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'Enter':
                    if (!checkBtn.disabled) {
                        checkBtn.click();
                    } else if (nextBtn.style.display !== 'none') {
                        nextBtn.click();
                    }
                    break;
                case 'Escape':
                    if (!clearBtn.disabled) {
                        clearBtn.click();
                    }
                    break;
                case '1':
                case '2':
                case '3':
                    if (e.ctrlKey) {
                        const levelBtn = document.querySelector(`[data-level="${e.key}"]`);
                        if (levelBtn) levelBtn.click();
                    }
                    break;
            }
        });

        // Touch support for mobile
        let touchItem = null;

        document.addEventListener('touchstart', function(e) {
            if (e.target.classList.contains('word-tile') && !e.target.classList.contains('placed')) {
                touchItem = e.target;
                e.target.style.opacity = '0.5';
            }
        });

        document.addEventListener('touchmove', function(e) {
            e.preventDefault();
        });

        document.addEventListener('touchend', function(e) {
            if (touchItem) {
                const touch = e.changedTouches[0];
                const element = document.elementFromPoint(touch.clientX, touch.clientY);
                
                if (element && (element.id === 'drop-zone' || element.closest('#drop-zone'))) {
                    addWordToSentence(touchItem.textContent, touchItem);
                }
                
                touchItem.style.opacity = '1';
                touchItem = null;
            }
        });

        // Initialize game when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initGame();
            // Auto-start with first level
            filterSentencesByLevel();
            loadCurrentSentence();
        });

        // Responsive design adjustments
        function adjustForMobile() {
            if (window.innerWidth <= 768) {
                const gameContainer = document.querySelector('.game-container');
                gameContainer.style.margin = '10px';
                gameContainer.style.marginTop = '70px';
                
                const wordTiles = document.querySelectorAll('.word-tile');
                wordTiles.forEach(tile => {
                    tile.style.padding = '8px 12px';
                    tile.style.fontSize = '0.85rem';
                });
            }
        }

    </script>
</body>
</html>