<?php
/**
 * sentenceBuilder.php
 *
 * Interactive Spanish Sentence Builder Game
 * Players arrange words in the correct order to build proper Spanish sentences
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

// Sentences for building challenge at different difficulty levels
$sentenceBuilderChallenges = [
    // Level 1 - Basic sentences
    [
        'level' => 1,
        'words' => ['Me', 'llamo', 'Ana'],
        'solution' => 'Me llamo Ana',
        'translation' => 'My name is Ana',
        'grammar_concept' => 'Basic introduction'
    ],
    [
        'level' => 1,
        'words' => ['Hoy', 'es', 'lunes'],
        'solution' => 'Hoy es lunes',
        'translation' => 'Today is Monday',
        'grammar_concept' => 'Days of the week'
    ],
    [
        'level' => 1,
        'words' => ['Me', 'gusta', 'la', 'música'],
        'solution' => 'Me gusta la música',
        'translation' => 'I like music',
        'grammar_concept' => 'Expressing likes'
    ],
    [
        'level' => 1,
        'words' => ['Vivo', 'en', 'España'],
        'solution' => 'Vivo en España',
        'translation' => 'I live in Spain',
        'grammar_concept' => 'Expressing location'
    ],
    [
        'level' => 1,
        'words' => ['¿Cómo', 'estás', 'tú', '?'],
        'solution' => '¿Cómo estás tú?',
        'translation' => 'How are you?',
        'grammar_concept' => 'Question formation'
    ],
    // Level 2 - Medium complexity sentences
    [
        'level' => 2,
        'words' => ['Mañana', 'voy', 'a', 'la', 'playa', 'con', 'mis', 'amigos'],
        'solution' => 'Mañana voy a la playa con mis amigos',
        'translation' => 'Tomorrow I am going to the beach with my friends',
        'grammar_concept' => 'Future with "ir a + infinitive"'
    ],
    [
        'level' => 2,
        'words' => ['No', 'he', 'terminado', 'mi', 'trabajo', 'todavía'],
        'solution' => 'No he terminado mi trabajo todavía',
        'translation' => 'I haven\'t finished my work yet',
        'grammar_concept' => 'Present perfect tense'
    ],
    [
        'level' => 2,
        'words' => ['¿Qué', 'hiciste', 'el', 'fin', 'de', 'semana', 'pasado', '?'],
        'solution' => '¿Qué hiciste el fin de semana pasado?',
        'translation' => 'What did you do last weekend?',
        'grammar_concept' => 'Past tense question'
    ],
    [
        'level' => 2,
        'words' => ['Siempre', 'como', 'frutas', 'y', 'verduras', 'frescas'],
        'solution' => 'Siempre como frutas y verduras frescas',
        'translation' => 'I always eat fresh fruits and vegetables',
        'grammar_concept' => 'Adverbs of frequency'
    ],
    [
        'level' => 2,
        'words' => ['Me', 'duele', 'la', 'cabeza', 'cuando', 'estudio', 'mucho'],
        'solution' => 'Me duele la cabeza cuando estudio mucho',
        'translation' => 'My head hurts when I study a lot',
        'grammar_concept' => 'Body reflexives and temporal clauses'
    ],
    // Level 3 - Complex sentences
    [
        'level' => 3,
        'words' => ['Si', 'hubiera', 'estudiado', 'más', ',', 'habría', 'aprobado', 'el', 'examen'],
        'solution' => 'Si hubiera estudiado más, habría aprobado el examen',
        'translation' => 'If I had studied more, I would have passed the exam',
        'grammar_concept' => 'Third conditional'
    ],
    [
        'level' => 3,
        'words' => ['Le', 'dije', 'que', 'me', 'llamara', 'cuando', 'llegara', 'a', 'casa'],
        'solution' => 'Le dije que me llamara cuando llegara a casa',
        'translation' => 'I told him to call me when he arrived home',
        'grammar_concept' => 'Reported speech with subjunctive'
    ],
    [
        'level' => 3,
        'words' => ['A', 'pesar', 'de', 'que', 'llovía', ',', 'decidimos', 'salir', 'a', 'pasear'],
        'solution' => 'A pesar de que llovía, decidimos salir a pasear',
        'translation' => 'Despite the fact that it was raining, we decided to go for a walk',
        'grammar_concept' => 'Concessive clauses'
    ],
    [
        'level' => 3,
        'words' => ['Tanto', 'los', 'estudiantes', 'como', 'los', 'profesores', 'estaban', 'preocupados', 'por', 'el', 'resultado'],
        'solution' => 'Tanto los estudiantes como los profesores estaban preocupados por el resultado',
        'translation' => 'Both the students and the teachers were worried about the result',
        'grammar_concept' => 'Correlative conjunctions'
    ],
    [
        'level' => 3,
        'words' => ['Habiendo', 'terminado', 'sus', 'deberes', ',', 'pudo', 'salir', 'con', 'sus', 'amigos'],
        'solution' => 'Habiendo terminado sus deberes, pudo salir con sus amigos',
        'translation' => 'Having finished his homework, he was able to go out with his friends',
        'grammar_concept' => 'Gerund with perfect aspect'
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
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
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
            background: linear-gradient(135deg, #4b6cb7, #182848);
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

        .sentence-challenge {
            text-align: center;
            margin-bottom: 30px;
        }

        .translation-text {
            font-size: 1.1rem;
            color: #7f8c8d;
            margin-bottom: 15px;
            font-style: italic;
        }

        .grammar-concept {
            display: inline-block;
            background: #edf2f7;
            color: #4a5568;
            font-size: 0.9rem;
            padding: 5px 12px;
            border-radius: 15px;
            margin-top: 10px;
        }

        .word-bank {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            min-height: 60px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .word-card {
            padding: 10px 15px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            cursor: grab;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            user-select: none;
        }

        .word-card:hover {
            background: #edf2f7;
            transform: translateY(-2px);
        }

        .word-card.selected {
            background: #4b6cb7;
            color: white;
            border-color: #4b6cb7;
        }

        .sentence-area {
            min-height: 60px;
            padding: 20px;
            background: #f0f5fa;
            border: 2px dashed #a9c1e7;
            border-radius: 10px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .sentence-area.highlight {
            border-color: #4b6cb7;
            background-color: #edf2ff;
        }

        .sentence-word {
            padding: 10px 15px;
            background: #4b6cb7;
            color: white;
            border-radius: 8px;
            font-size: 1rem;
            cursor: grab;
            box-shadow: 0 2px 8px rgba(75, 108, 183, 0.3);
            user-select: none;
        }

        .timer-container {
            margin: 20px 0;
        }

        .timer-bar {
            width: 100%;
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

        .solution-display {
            font-size: 1.2rem;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            color: #2c3e50;
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
            background: linear-gradient(135deg, #4b6cb7, #182848);
            color: white;
            box-shadow: 0 4px 15px rgba(75, 108, 183, 0.4);
        }

        .game-switch-btn:not(.active) {
            background: rgba(255, 255, 255, 0.7);
            color: #2c3e50;
        }

        .game-switch-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
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
        }

        .hint-button {
            background: transparent;
            border: none;
            color: #3498db;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px auto 0;
            transition: all 0.2s ease;
        }

        .hint-button:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .hint-text {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 8px;
            text-align: center;
            display: none;
        }

        .hint-text.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        <a href="speedTyping.php" class="game-switch-btn">
            <i class="fas fa-keyboard"></i> Speed Typing
        </a>
        <span class="game-switch-btn active">
            <i class="fas fa-puzzle-piece"></i> Sentence Builder
        </span>
    </div>

    <div class="game-header">
        <h1><i class="fas fa-puzzle-piece"></i> Sentence Builder</h1>
        <p>Build correct Spanish sentences by arranging words!</p>
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
        <div class="sentence-challenge">
            <div class="translation-text" id="translation-text">English translation will appear here</div>
            <span class="grammar-concept" id="grammar-concept">Grammar concept</span>
        </div>

        <div class="timer-container">
            <div class="timer-bar">
                <div class="timer-fill" id="timer-bar"></div>
            </div>
        </div>

        <div class="word-bank" id="word-bank">
            <!-- Words will be loaded here -->
        </div>

        <div class="sentence-area" id="sentence-area">
            <!-- Built sentence will appear here -->
            <div id="empty-text" style="color: #a0aec0; font-style: italic;">Drag words here to build your sentence</div>
        </div>

        <button class="hint-button" id="hint-button">
            <i class="fas fa-lightbulb"></i> Need a hint?
        </button>
        <div class="hint-text" id="hint-text"></div>

        <div class="game-controls">
            <button class="btn btn-primary" id="start-btn">
                <i class="fas fa-play"></i> Start Challenge
            </button>
            <button class="btn btn-success" id="check-btn" style="display: none;">
                <i class="fas fa-check"></i> Check Sentence
            </button>
            <button class="btn btn-warning" id="reset-btn" style="display: none;">
                <i class="fas fa-sync-alt"></i> Reset Words
            </button>
            <button class="btn btn-secondary" id="next-btn" style="display: none;">
                <i class="fas fa-arrow-right"></i> Next Challenge
            </button>
        </div>

        <div class="feedback" id="feedback" style="display: none;"></div>
        <div class="solution-display" id="solution-display" style="display: none;"></div>
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
    const sentenceChallenges = <?php echo json_encode($sentenceBuilderChallenges); ?>;
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
    let attempts = 0;
    let draggedElement = null;

    // DOM elements
    const levelBtns = document.querySelectorAll('.level-btn');
    const translationText = document.getElementById('translation-text');
    const grammarConcept = document.getElementById('grammar-concept');
    const wordBank = document.getElementById('word-bank');
    const sentenceArea = document.getElementById('sentence-area');
    const emptyText = document.getElementById('empty-text');
    const timerBar = document.getElementById('timer-bar');
    const startBtn = document.getElementById('start-btn');
    const checkBtn = document.getElementById('check-btn');
    const resetBtn = document.getElementById('reset-btn');
    const nextBtn = document.getElementById('next-btn');
    const feedback = document.getElementById('feedback');
    const solutionDisplay = document.getElementById('solution-display');
    const scoreElement = document.getElementById('score');
    const correctElement = document.getElementById('correct-count');
    const incorrectElement = document.getElementById('incorrect-count');
    const timerElement = document.getElementById('timer');
    const progressFill = document.getElementById('progress-fill');
    const speechBubble = document.getElementById('cerve-speech');
    const avatar = document.getElementById('cerve-avatar');
    const hintButton = document.getElementById('hint-button');
    const hintText = document.getElementById('hint-text');

    // Avatar messages
    const avatarMessages = {
        welcome: "¡Hola! Ready to build some Spanish sentences?",
        correct: "¡Excelente! That's the correct order!",
        almost: "¡Casi! You're very close! Try again.",
        wrong: "Not quite right. Remember word order in Spanish!",
        hint: "Look at the English translation and the grammar concept!",
        encouragement: "You can do it! Think about the sentence structure.",
        completed: "¡Fantástico! You've completed this level!"
    };

    // Initialize game
    function initGame() {
        updateTimer();
        gameTimer = setInterval(updateTimer, 1000);
        filterChallengesByLevel();
        showAvatarMessage(avatarMessages.welcome);
        setupDragAndDrop();
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
            updateInterface();
        });
    });

    // Filter challenges by level
    function filterChallengesByLevel() {
        levelChallenges = sentenceChallenges.filter(c => c.level === currentLevel);
        updateProgress();
    }

    // Update interface with current challenge info
    function updateInterface() {
        if (currentChallengeIndex >= levelChallenges.length) {
            completedLevel();
            return;
        }

        currentChallenge = levelChallenges[currentChallengeIndex];
        translationText.textContent = currentChallenge.translation;
        grammarConcept.textContent = currentChallenge.grammar_concept;

        hintText.textContent = getHint();
        hintText.classList.remove('show');

        // Clear sentence area
        while (sentenceArea.firstChild) {
            sentenceArea.removeChild(sentenceArea.firstChild);
        }
        sentenceArea.appendChild(emptyText);

        // Reset game state
        gameStarted = false;
        attempts = 0;

        // Update buttons
        startBtn.style.display = 'inline-flex';
        checkBtn.style.display = 'none';
        resetBtn.style.display = 'none';
        nextBtn.style.display = 'none';
        feedback.style.display = 'none';
        solutionDisplay.style.display = 'none';

        // Reset timer bar
        timerValue = 100;
        timerBar.style.width = '100%';
        timerBar.className = 'timer-fill';

        // Clear word bank
        wordBank.innerHTML = '';

        updateProgress();
    }

    // Get hint based on current challenge
    function getHint() {
        const level = currentChallenge.level;

        if (level === 1) {
            return "Remember: Spanish sentences usually follow Subject-Verb-Object order, just like in English.";
        } else if (level === 2) {
            return `Focus on the ${currentChallenge.grammar_concept}. Pay attention to word placement!`;
        } else {
            return "Look for connecting words and proper punctuation. Complex Spanish sentences often have subjunctive forms.";
        }
    }

    // Start challenge
    startBtn.addEventListener('click', function() {
        startChallenge();
    });

    function startChallenge() {
        gameStarted = true;
        challengeStartTime = Date.now();

        // Load words into word bank in random order
        const words = [...currentChallenge.words];
        shuffleArray(words);

        wordBank.innerHTML = '';
        words.forEach(word => {
            const wordElement = document.createElement('div');
            wordElement.className = 'word-card';
            wordElement.textContent = word;
            wordElement.setAttribute('draggable', 'true');
            wordBank.appendChild(wordElement);
        });

        // Update buttons
        startBtn.style.display = 'none';
        checkBtn.style.display = 'inline-flex';
        resetBtn.style.display = 'inline-flex';

        // Start timer countdown
        timerValue = 100;
        startChallengeTimer();

        showAvatarMessage(avatarMessages.encouragement);
    }

    // Setup drag and drop functionality
    function setupDragAndDrop() {
        document.addEventListener('dragstart', function(e) {
            if (e.target.classList.contains('word-card') || e.target.classList.contains('sentence-word')) {
                draggedElement = e.target;
                setTimeout(() => {
                    e.target.style.opacity = '0.4';
                }, 0);
            }
        });

        document.addEventListener('dragend', function(e) {
            if (e.target.classList.contains('word-card') || e.target.classList.contains('sentence-word')) {
                e.target.style.opacity = '1';
            }
        });

        wordBank.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('highlight');
        });

        wordBank.addEventListener('dragleave', function() {
            this.classList.remove('highlight');
        });

        wordBank.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('highlight');

            if (draggedElement && draggedElement.classList.contains('sentence-word')) {
                // Move from sentence area to word bank
                const newWord = document.createElement('div');
                newWord.className = 'word-card';
                newWord.textContent = draggedElement.textContent;
                newWord.setAttribute('draggable', 'true');
                wordBank.appendChild(newWord);

                // Remove from sentence area
                draggedElement.remove();

                // Show or hide empty text
                toggleEmptyText();
            }
        });

        sentenceArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('highlight');
        });

        sentenceArea.addEventListener('dragleave', function() {
            this.classList.remove('highlight');
        });

        sentenceArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('highlight');

            if (draggedElement && draggedElement.classList.contains('word-card')) {
                // Move from word bank to sentence area
                const newWord = document.createElement('div');
                newWord.className = 'sentence-word';
                newWord.textContent = draggedElement.textContent;
                newWord.setAttribute('draggable', 'true');
                sentenceArea.appendChild(newWord);

                // Remove from word bank
                draggedElement.remove();

                // Hide empty text
                toggleEmptyText();
            }
        });
    }

    // Toggle empty text based on sentence area content
    function toggleEmptyText() {
        const sentenceWords = sentenceArea.querySelectorAll('.sentence-word');
        if (sentenceWords.length > 0) {
            emptyText.style.display = 'none';
        } else {
            emptyText.style.display = 'block';
        }
    }

    // Check sentence
    checkBtn.addEventListener('click', function() {
        checkSentence();
    });

    function checkSentence() {
        const sentenceWords = sentenceArea.querySelectorAll('.sentence-word');
        let userSentence = '';

        // Build user's sentence from word elements
        sentenceWords.forEach((word, index) => {
            userSentence += word.textContent;
            if (index < sentenceWords.length - 1) {
                userSentence += ' ';
            }
        });

        // Check if correct
        if (userSentence === currentChallenge.solution) {
            handleCorrectSentence();
        } else {
            handleIncorrectSentence(userSentence);
        }

        attempts++;
    }

    // Handle correct sentence
    function handleCorrectSentence() {
        clearInterval(challengeTimer);
        checkBtn.style.display = 'none';
        resetBtn.style.display = 'none';
        nextBtn.style.display = 'inline-flex';

        // Calculate time taken and points
        const timeTaken = (Date.now() - challengeStartTime) / 1000; // in seconds
        const basePoints = currentLevel * 30;
        const timeBonus = Math.max(0, Math.round(basePoints * (1 - timeTaken / calculateTimeLimit() * 1000)));
        const attemptPenalty = attempts > 1 ? Math.round(basePoints * 0.25 * (attempts - 1)) : 0;
        const totalPoints = basePoints + timeBonus - attemptPenalty;

        score += totalPoints;
        correctCount++;

        // Show feedback
        feedback.className = 'feedback correct';
        feedback.innerHTML = `
            <i class="fas fa-check-circle"></i> ¡Correcto!
            <div style="margin-top: 10px; font-size: 0.9rem;">
                Time: ${timeTaken.toFixed(2)}s | Attempts: ${attempts}
                <br>
                Points: ${basePoints} base + ${timeBonus} time bonus - ${attemptPenalty} attempt penalty = ${totalPoints}
            </div>
        `;
        feedback.style.display = 'block';

        showAvatarMessage(avatarMessages.correct);
        updateStats();
    }

    // Handle incorrect sentence
    function handleIncorrectSentence(userSentence) {
        feedback.className = 'feedback incorrect';

        // Check how similar the user's sentence is to the correct one
        const similarity = calculateSimilarity(userSentence, currentChallenge.solution);

        if (similarity > 0.7) {
            feedback.innerHTML = `<i class="fas fa-exclamation-circle"></i> Almost there! Try again.`;
            showAvatarMessage(avatarMessages.almost);
        } else {
            feedback.innerHTML = `<i class="fas fa-times-circle"></i> That's not quite right. Check the word order.`;
            showAvatarMessage(avatarMessages.wrong);
        }

        feedback.style.display = 'block';

        // After 3 attempts, show the solution
        if (attempts >= 2) {
            solutionDisplay.textContent = `The correct order is: "${currentChallenge.solution}"`;
            solutionDisplay.style.display = 'block';

            // Update buttons
            checkBtn.style.display = 'none';
            nextBtn.style.display = 'inline-flex';

            // Update stats
            incorrectCount++;
            updateStats();
        }
    }

    // Calculate similarity between two strings (basic implementation)
    function calculateSimilarity(str1, str2) {
        const words1 = str1.split(' ');
        const words2 = str2.split(' ');

        let matches = 0;
        for (let i = 0; i < words1.length; i++) {
            if (i < words2.length && words1[i] === words2[i]) {
                matches++;
            }
        }

        return matches / Math.max(words1.length, words2.length);
    }

    // Reset words
    resetBtn.addEventListener('click', function() {
        resetWords();
    });

    function resetWords() {
        // Move all words back to word bank
        const sentenceWords = sentenceArea.querySelectorAll('.sentence-word');
        sentenceWords.forEach(word => {
            const newWord = document.createElement('div');
            newWord.className = 'word-card';
            newWord.textContent = word.textContent;
            newWord.setAttribute('draggable', 'true');
            wordBank.appendChild(newWord);
        });

        // Clear sentence area
        while (sentenceArea.firstChild) {
            if (sentenceArea.firstChild !== emptyText) {
                sentenceArea.removeChild(sentenceArea.firstChild);
            }
        }

        // Show empty text
        emptyText.style.display = 'block';
    }

    // Next challenge
    nextBtn.addEventListener('click', function() {
        currentChallengeIndex++;
        updateInterface();
    });

    // Timer for individual challenge
    function startChallengeTimer() {
        // Clear existing timer if any
        if (challengeTimer) clearInterval(challengeTimer);

        // Set time based on difficulty and solution length
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

    // Calculate time limit based on level and solution length
    function calculateTimeLimit() {
        const baseTime = 10000; // Base 10 seconds
        const wordTime = 1500; // 1.5 seconds per word
        const levelMultiplier = [1, 0.8, 0.7][currentLevel - 1]; // Less time for higher levels

        return (baseTime + (currentChallenge.words.length * wordTime)) * levelMultiplier;
    }

    // Handle time up
    function handleTimeUp() {
        checkBtn.style.display = 'none';
        resetBtn.style.display = 'none';
        nextBtn.style.display = 'inline-flex';

        solutionDisplay.textContent = `Time's up! The correct sentence is: "${currentChallenge.solution}"`;
        solutionDisplay.style.display = 'block';

        feedback.className = 'feedback incorrect';
        feedback.innerHTML = `<i class="fas fa-clock"></i> You ran out of time!`;
        feedback.style.display = 'block';

        incorrectCount++;
        showAvatarMessage(avatarMessages.wrong);
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
        }, 4000);
    }

    // Avatar click handler
    avatar.addEventListener('click', function() {
        const messages = Object.values(avatarMessages);
        const randomMessage = messages[Math.floor(Math.random() * messages.length)];
        showAvatarMessage(randomMessage);
    });

    // Hint button handler
    hintButton.addEventListener('click', function() {
        hintText.classList.toggle('show');
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
    }

    // Shuffle array (Fisher-Yates algorithm)
    function shuffleArray(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }

    // Save game progress
    function saveGameProgress() {
        const gameData = {
            game_id: 7, // Sentence Builder game ID
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
        updateInterface();
    });

    // Special keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (!gameStarted && e.key === 'Enter') {
            startBtn.click(); // Start when pressing Enter
        }

        if (gameStarted && !nextBtn.style.display === 'inline-flex' && e.key === 'Enter') {
            checkBtn.click(); // Check when pressing Enter during game
        }

        if (nextBtn.style.display === 'inline-flex' && e.key === 'Enter') {
            nextBtn.click(); // Next challenge when finished
        }

        if (e.key === 'r' && gameStarted && resetBtn.style.display === 'inline-flex') {
            resetBtn.click(); // Reset words with R
        }
    });
</script>
</body>
</html>