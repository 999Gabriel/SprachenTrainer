<?php
/**
 * speedTyping.php
 *
 * Interactive Spanish Speed Typing Game
 * Players type Spanish words and phrases as quickly as possible
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

// Words and phrases for typing challenge at different difficulty levels
$typingChallenges = [
    // Level 1 - Basic words
    [
        'level' => 1,
        'content' => 'hola',
        'translation' => 'hello',
        'category' => 'greeting'
    ],
    [
        'level' => 1,
        'content' => 'gracias',
        'translation' => 'thank you',
        'category' => 'courtesy'
    ],
    [
        'level' => 1,
        'content' => 'buenos días',
        'translation' => 'good morning',
        'category' => 'greeting'
    ],
    [
        'level' => 1,
        'content' => 'adiós',
        'translation' => 'goodbye',
        'category' => 'greeting'
    ],
    [
        'level' => 1,
        'content' => 'por favor',
        'translation' => 'please',
        'category' => 'courtesy'
    ],
    // Level 2 - Medium phrases
    [
        'level' => 2,
        'content' => 'me gusta leer libros',
        'translation' => 'I like reading books',
        'category' => 'hobby'
    ],
    [
        'level' => 2,
        'content' => '¿cómo estás hoy?',
        'translation' => 'how are you today?',
        'category' => 'conversation'
    ],
    [
        'level' => 2,
        'content' => 'tengo hambre',
        'translation' => 'I am hungry',
        'category' => 'feelings'
    ],
    [
        'level' => 2,
        'content' => 'hace buen tiempo',
        'translation' => 'the weather is nice',
        'category' => 'weather'
    ],
    [
        'level' => 2,
        'content' => '¿dónde está el baño?',
        'translation' => 'where is the bathroom?',
        'category' => 'question'
    ],
    // Level 3 - Complex sentences
    [
        'level' => 3,
        'content' => 'me encantaría viajar a España el próximo verano',
        'translation' => 'I would love to travel to Spain next summer',
        'category' => 'future plans'
    ],
    [
        'level' => 3,
        'content' => 'nunca he visto una película tan emocionante como ésta',
        'translation' => 'I have never seen a movie as exciting as this one',
        'category' => 'opinion'
    ],
    [
        'level' => 3,
        'content' => 'si tuviera más tiempo, estudiaría otro idioma',
        'translation' => 'if I had more time, I would study another language',
        'category' => 'conditional'
    ],
    [
        'level' => 3,
        'content' => '¿podrías decirme cómo llegar a la estación de tren?',
        'translation' => 'could you tell me how to get to the train station?',
        'category' => 'direction'
    ],
    [
        'level' => 3,
        'content' => 'es importante que practiques español todos los días',
        'translation' => 'it is important that you practice Spanish every day',
        'category' => 'advice'
    ]
];

$page_title = "Speed Typing Challenge";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CerveLingua - Speed Typing Challenge</title>

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
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
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
            background: linear-gradient(135deg, #00b4db, #0083b0);
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

        .type-challenge {
            text-align: center;
            margin-bottom: 30px;
        }

        .translation-text {
            font-size: 1.1rem;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-style: italic;
        }

        .challenge-text {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .typing-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .typing-input {
            width: 100%;
            max-width: 600px;
            padding: 15px 20px;
            font-size: 1.2rem;
            border: 2px solid #dfe6e9;
            border-radius: 12px;
            background: #f8f9fa;
            transition: all 0.3s ease;
            text-align: center;
        }

        .typing-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 15px rgba(52, 152, 219, 0.2);
        }

        .typing-input.correct {
            border-color: #2ecc71;
            background-color: rgba(46, 204, 113, 0.1);
        }

        .typing-input.incorrect {
            border-color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
        }

        .timer-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
        }

        .timer-bar {
            width: 100%;
            max-width: 600px;
            height: 10px;
            background: #ecf0f1;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .timer-fill {
            height: 100%;
            background: linear-gradient(90deg, #2ecc71, #27ae60);
            width: 100%;
            transition: width linear;
        }

        .timer-fill.warning {
            background: linear-gradient(90deg, #f39c12, #e67e22);
        }

        .timer-fill.danger {
            background: linear-gradient(90deg, #e74c3c, #c0392b);
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

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
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

        .result-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-top: 30px;
            text-align: center;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .character {
            display: inline-block;
            padding: 5px;
            transition: all 0.2s ease;
            border-radius: 4px;
        }

        .character.correct {
            color: #27ae60;
            font-weight: 600;
        }

        .character.incorrect {
            color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
            font-weight: 600;
        }

        .character.current {
            background-color: #3498db;
            color: white;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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

            .game-stats {
                flex-direction: column;
                gap: 10px;
            }

            .level-selector {
                flex-direction: column;
                align-items: center;
            }

            .challenge-text {
                font-size: 1.5rem;
            }
        }

        .game-switcher {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(5px);
        }

        .game-switch-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .game-switch-btn.active {
            background: linear-gradient(135deg, #00b4db, #0083b0);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 179, 219, 0.4);
        }

        .game-switch-btn:not(.active) {
            background: rgba(255, 255, 255, 0.7);
            color: #2c3e50;
        }

        .game-switch-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .accuracy-indicator {
            display: flex;
            justify-content: center;
            margin: 10px 0;
            font-size: 1.1rem;
        }

        .wpm-display {
            font-size: 1.2rem;
            font-weight: 600;
            text-align: center;
            margin: 15px 0;
            color: #2c3e50;
        }
    </style>
</head>
<body>
<a href="../practice.php" class="back-btn">
    <i class="fas fa-arrow-left"></i> Back to Practice
</a>

<div class="game-container">
    <!-- Game Switcher -->
    <div class="game-switcher">
            <span class="game-switch-btn active">
                <i class="fas fa-keyboard"></i> Speed Typing
            </span>
        <a href="sentenceBuilder.php" class="game-switch-btn">
            <i class="fas fa-puzzle-piece"></i> Sentence Builder
        </a>
    </div>

    <div class="game-header">
        <h1><i class="fas fa-keyboard"></i> Speed Typing Challenge</h1>
        <p>Test your Spanish typing speed and accuracy!</p>
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
        <div class="type-challenge">
            <div class="translation-text" id="translation-text">English translation</div>
            <div class="challenge-text" id="challenge-text">Click a level to start!</div>
            <div class="category-text" id="category-text" style="color: #7f8c8d; font-size: 0.9rem;">Category: N/A</div>
        </div>

        <div class="timer-container">
            <div class="timer-bar">
                <div class="timer-fill" id="timer-bar"></div>
            </div>
        </div>

        <div class="wpm-display" id="wpm-display">Type the Spanish text above</div>

        <div class="typing-area">
            <input type="text" class="typing-input" id="typing-input" placeholder="Type here..." autocomplete="off" disabled>
            <div class="accuracy-indicator" id="accuracy-indicator"></div>
        </div>

        <div class="game-controls">
            <button class="btn btn-primary" id="start-btn">
                <i class="fas fa-play"></i> Start Challenge
            </button>
            <button class="btn btn-secondary" id="next-btn" style="display: none;">
                <i class="fas fa-arrow-right"></i> Next Challenge
            </button>
            <button class="btn btn-danger" id="skip-btn" style="display: none;">
                <i class="fas fa-forward"></i> Skip
            </button>
        </div>

        <div class="feedback" id="feedback" style="display: none;"></div>
    </div>
</div>

<!-- Avatar -->
<div class="avatar-container">
    <div class="speech-bubble" id="cerve-speech">
        <p>¡Hola! Ready to test your Spanish typing skills?</p>
    </div>
    <img src="../img/CerveLingua_Avatar.png" alt="CerveLingua Avatar" id="cerve-avatar" />
</div>

<script>
    // Game data and variables
    const typingChallenges = <?php echo json_encode($typingChallenges); ?>;
    let currentLevel = 1;
    let currentChallengeIndex = 0;
    let currentChallenge = null;
    let score = 0;
    let correctCount = 0;
    let incorrectCount = 0;
    let startTime = Date.now();
    let challengeStartTime = 0;
    let gameTimer;
    let challengeTimer;
    let levelChallenges = [];
    let gameStarted = false;
    let timerValue = 100; // Percentage
    let typingStarted = false;
    let typingFinished = false;
    let wordsPerMinute = 0;
    let accuracy = 100;

    // DOM elements
    const levelBtns = document.querySelectorAll('.level-btn');
    const challengeText = document.getElementById('challenge-text');
    const translationText = document.getElementById('translation-text');
    const categoryText = document.getElementById('category-text');
    const typingInput = document.getElementById('typing-input');
    const timerBar = document.getElementById('timer-bar');
    const startBtn = document.getElementById('start-btn');
    const nextBtn = document.getElementById('next-btn');
    const skipBtn = document.getElementById('skip-btn');
    const feedback = document.getElementById('feedback');
    const scoreElement = document.getElementById('score');
    const correctElement = document.getElementById('correct-count');
    const incorrectElement = document.getElementById('incorrect-count');
    const timerElement = document.getElementById('timer');
    const progressFill = document.getElementById('progress-fill');
    const speechBubble = document.getElementById('cerve-speech');
    const avatar = document.getElementById('cerve-avatar');
    const accuracyIndicator = document.getElementById('accuracy-indicator');
    const wpmDisplay = document.getElementById('wpm-display');

    // Avatar messages
    const avatarMessages = {
        welcome: "¡Hola! Ready to test your Spanish typing skills?",
        correct: "¡Excelente! Perfect typing!",
        fast: "¡Increíble! That was super fast!",
        accurate: "¡Muy bien! Great accuracy!",
        slow: "¡Bien! Keep practicing to get faster!",
        encouragement: "You can do it! Focus on accuracy first, speed will come.",
        completed: "¡Fantástico! You've completed this level!"
    };

    // Initialize game
    function initGame() {
        updateTimer();
        gameTimer = setInterval(updateTimer, 1000);
        filterChallengesByLevel();
        showAvatarMessage(avatarMessages.welcome);
    }

    // Level selection
    levelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            levelBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentLevel = parseInt(this.dataset.level);
            currentChallengeIndex = 0;
            filterChallengesByLevel();
            resetGame();
            loadCurrentChallenge();
        });
    });

    // Filter challenges by level
    function filterChallengesByLevel() {
        levelChallenges = typingChallenges.filter(c => c.level === currentLevel);
        updateProgress();
    }

    // Load current challenge
    function loadCurrentChallenge() {
        if (currentChallengeIndex >= levelChallenges.length) {
            completedLevel();
            return;
        }

        currentChallenge = levelChallenges[currentChallengeIndex];
        typingInput.value = '';
        typingInput.disabled = true;
        typingStarted = false;
        typingFinished = false;

        translationText.textContent = currentChallenge.translation;

        // Display challenge text character by character with spans
        const text = currentChallenge.content;
        let htmlContent = '';
        for (let i = 0; i < text.length; i++) {
            htmlContent += `<span class="character" data-index="${i}">${text[i]}</span>`;
        }
        challengeText.innerHTML = htmlContent;

        categoryText.textContent = `Category: ${currentChallenge.category}`;

        // Reset buttons
        startBtn.style.display = 'inline-flex';
        nextBtn.style.display = 'none';
        skipBtn.style.display = 'none';
        feedback.style.display = 'none';

        // Reset timer bar
        timerValue = 100;
        timerBar.style.width = '100%';
        timerBar.className = 'timer-fill';

        // Update display
        wpmDisplay.textContent = 'Type the Spanish text above';
        accuracyIndicator.innerHTML = '';

        updateProgress();
    }

    // Start challenge
    startBtn.addEventListener('click', function() {
        startChallenge();
    });

    function startChallenge() {
        gameStarted = true;
        typingInput.disabled = false;
        typingInput.focus();
        startBtn.style.display = 'none';
        skipBtn.style.display = 'inline-flex';

        // Reset timing
        challengeStartTime = Date.now();
        typingStarted = false;

        // Add class to current character
        document.querySelector('.character[data-index="0"]').classList.add('current');

        // Start timer countdown
        timerValue = 100;
        startChallengeTimer();

        showAvatarMessage(avatarMessages.encouragement);
    }

    // Timer for individual challenge
    function startChallengeTimer() {
        // Clear existing timer if any
        if (challengeTimer) clearInterval(challengeTimer);

        // Set time based on difficulty and text length
        const timeLimit = calculateTimeLimit();
        const timeStep = 100 / (timeLimit / 100); // Decrease by percentage points every 100ms

        challengeTimer = setInterval(() => {
            timerValue -= timeStep;
            timerBar.style.width = timerValue + '%';

            // Warning colors
            if (timerValue <= 30) {
                timerBar.className = 'timer-fill danger';
            } else if (timerValue <= 60) {
                timerBar.className = 'timer-fill warning';
            }

            // Time's up
            if (timerValue <= 0) {
                clearInterval(challengeTimer);
                handleTimeUp();
            }
        }, 100);
    }

    // Calculate time limit based on level and text length
    function calculateTimeLimit() {
        const baseTime = 5000; // Base 5 seconds
        const charTime = 300; // 300ms per character
        const levelMultiplier = [1, 0.8, 0.6][currentLevel - 1]; // Less time for higher levels

        return (baseTime + (currentChallenge.content.length * charTime)) * levelMultiplier;
    }

    // Handle time up
    function handleTimeUp() {
        typingInput.disabled = true;
        typingFinished = true;
        skipBtn.style.display = 'none';
        nextBtn.style.display = 'inline-flex';

        feedback.className = 'feedback incorrect';
        feedback.innerHTML = `<i class="fas fa-clock"></i> Time's up! The correct text was: "${currentChallenge.content}"`;
        feedback.style.display = 'block';

        incorrectCount++;
        showAvatarMessage(avatarMessages.slow);
        updateStats();
    }

    // Next challenge
    nextBtn.addEventListener('click', function() {
        currentChallengeIndex++;
        loadCurrentChallenge();
    });

    // Skip challenge
    skipBtn.addEventListener('click', function() {
        clearInterval(challengeTimer);
        incorrectCount++;
        updateStats();
        currentChallengeIndex++;
        loadCurrentChallenge();
    });

    // Handle typing
    typingInput.addEventListener('input', function(e) {
        if (!gameStarted || typingFinished) return;

        const inputText = e.target.value;
        const targetText = currentChallenge.content;

        // Mark as started on first keystroke
        if (!typingStarted && inputText.length > 0) {
            typingStarted = true;
            challengeStartTime = Date.now();
        }

        // Compare entered text with target text character by character
        for (let i = 0; i < targetText.length; i++) {
            const charSpan = document.querySelector(`.character[data-index="${i}"]`);
            charSpan.classList.remove('current', 'correct', 'incorrect');

            if (i < inputText.length) {
                if (inputText[i] === targetText[i]) {
                    charSpan.classList.add('correct');
                } else {
                    charSpan.classList.add('incorrect');
                }
            } else if (i === inputText.length) {
                charSpan.classList.add('current');
            }
        }

        // Calculate accuracy
        let correctChars = 0;
        for (let i = 0; i < inputText.length; i++) {
            if (i < targetText.length && inputText[i] === targetText[i]) {
                correctChars++;
            }
        }

        accuracy = inputText.length > 0 ? Math.round((correctChars / inputText.length) * 100) : 100;
        accuracyIndicator.textContent = `Accuracy: ${accuracy}%`;

        // Calculate WPM (Word Per Minute)
        const elapsedMinutes = (Date.now() - challengeStartTime) / 60000;
        if (elapsedMinutes > 0) {
            // Using 5 characters as average word length
            const words = inputText.length / 5;
            wordsPerMinute = Math.round(words / elapsedMinutes);
            wpmDisplay.textContent = `Speed: ${wordsPerMinute} WPM`;
        }

        // Check if completed correctly
        if (inputText === targetText) {
            handleCorrectTyping();
        }
    });

    // Handle correct typing
    function handleCorrectTyping() {
        clearInterval(challengeTimer);
        typingFinished = true;
        typingInput.disabled = true;
        skipBtn.style.display = 'none';
        nextBtn.style.display = 'inline-flex';

        // Calculate time taken and points
        const timeTaken = (Date.now() - challengeStartTime) / 1000; // in seconds
        const basePoints = currentLevel * 25;
        const timeBonus = Math.max(0, Math.round(basePoints * (1 - timeTaken / calculateTimeLimit() * 1000)));
        const accuracyBonus = Math.round(basePoints * (accuracy / 100));
        const totalPoints = basePoints + timeBonus + accuracyBonus;

        score += totalPoints;
        correctCount++;

        // Show feedback
        feedback.className = 'feedback correct';
        feedback.innerHTML = `
                <i class="fas fa-check-circle"></i> ¡Perfecto!
                <div style="margin-top: 10px; font-size: 0.9rem;">
                    Time: ${timeTaken.toFixed(2)}s | WPM: ${wordsPerMinute} | Accuracy: ${accuracy}%
                    <br>
                    Points: ${basePoints} base + ${timeBonus} time bonus + ${accuracyBonus} accuracy bonus = ${totalPoints}
                </div>
            `;
        feedback.style.display = 'block';

        // Show appropriate message
        if (wordsPerMinute > 40) {
            showAvatarMessage(avatarMessages.fast);
        } else if (accuracy > 95) {
            showAvatarMessage(avatarMessages.accurate);
        } else {
            showAvatarMessage(avatarMessages.correct);
        }

        updateStats();
    }

    // Update statistics
    function updateStats() {
        scoreElement.textContent = score;
        correctElement.textContent = correctCount;
        incorrectElement.textContent = incorrectCount;
    }

    // Update progress bar
    function updateProgress() {
        const progress = (currentChallengeIndex / levelChallenges.length) * 100;
        progressFill.style.width = `${progress}%`;
    }

    // Update game timer
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
                    <p>You've completed all challenges in Level ${currentLevel}!</p>
                    <p>Final Score: ${score} points</p>
                    <div class="result-stats">
                        <div class="stat-box">
                            <div class="stat-value">${correctCount}</div>
                            <div class="stat-label">Correct</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">${incorrectCount}</div>
                            <div class="stat-label">Incorrect</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">${Math.round((correctCount / (correctCount + incorrectCount || 1)) * 100)}%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                    </div>
                    ${currentLevel < 3 ? '<p>Try the next level for a greater challenge!</p>' : '<p>¡Felicidades! You\'ve mastered all levels!</p>'}
                </div>
            `;
        feedback.style.display = 'block';

        // Reset for new game
        resetGame();

        // Save progress
        saveGameProgress();
    }

    // Reset game
    function resetGame() {
        if (challengeTimer) clearInterval(challengeTimer);
        gameStarted = false;
        typingInput.disabled = true;
        startBtn.style.display = 'inline-flex';
        nextBtn.style.display = 'none';
        skipBtn.style.display = 'none';
        feedback.style.display = 'none';
    }

    // Save game progress
    function saveGameProgress() {
        const gameData = {
            game_id: 6, // Speed Typing game ID
            score: score,
            level_reached: currentLevel,
            time_spent: Math.floor((Date.now() - startTime) / 1000),
            challenges_completed: correctCount,
            accuracy: Math.round((correctCount / (correctCount + incorrectCount || 1)) * 100)
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

    // Initialize game when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initGame();
        // Auto-filter first level
        filterChallengesByLevel();
        loadCurrentChallenge();
    });

    // Special keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (!gameStarted && e.key === 'Enter') {
            startBtn.click(); // Start when pressing Enter
        }

        if (typingFinished && e.key === 'Enter') {
            nextBtn.click(); // Next challenge when finished
        }

        if (e.key === 'Escape' && gameStarted && !typingFinished) {
            skipBtn.click(); // Skip challenge with Escape
        }
    });
</script>
</body>
</html>