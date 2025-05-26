<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        // Handle case where user doesn't exist
        session_destroy();
        header("Location: login.php?error=invalid_user");
        exit;
    }
    
    // Fetch user's progress data
    $progress_stmt = $pdo->prepare("SELECT * FROM user_progress WHERE user_id = :user_id");
    $progress_stmt->bindParam(':user_id', $user_id);
    $progress_stmt->execute();
    $progress = $progress_stmt->fetch();
    
    if (!$progress) {
        $progress = [
            'current_level_id' => 1,
            'xp_points' => 0,
            'streak_days' => 0,
            'total_study_time' => 0,
            'last_activity_date' => date('Y-m-d')
        ];
    }
    
    // Fetch user's level data
    $level_stmt = $pdo->prepare("SELECT * FROM proficiency_levels WHERE level_id = :level_id");
    $level_stmt->bindParam(':level_id', $progress['current_level_id']);
    $level_stmt->execute();
    $level = $level_stmt->fetch();
    
    if (!$level) {
        $level = [
            'level_name' => 'Beginner',
            'level_code' => 'A1',
            'description' => 'Basic understanding of Spanish'
        ];
    }
    
} catch (PDOException $e) {
    // Log error and continue with default values
    error_log("AI conversation page error: " . $e->getMessage());
    
    // Set default values
    $user = [
        'username' => $_SESSION['username'] ?? 'User',
        'first_name' => '',
        'profile_image' => ''
    ];
    
    $progress = [
        'current_level_id' => 1,
        'xp_points' => 0,
        'streak_days' => 0,
        'total_study_time' => 0
    ];
    
    $level = [
        'level_name' => 'Beginner',
        'level_code' => 'A1'
    ];
}

// Set page title
$page_title = "AI Conversation Practice";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - AI Conversation</title>
    <link rel="stylesheet" href="css/styles.css"> 
    <!-- <link rel="stylesheet" href="css/dashboard.css"> -->
    <link rel="stylesheet" href="css/practice.css">
    <link rel="stylesheet" href="css/ai-conversation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <!-- AI Conversation Content -->
    <div class="ai-conversation-container">
        <div class="container">
            <div class="page-header">
                <br>
                <br>
                <br>
                <br>
                   
                <h1>AI Conversation Practice</h1>
                <p>Practice your Spanish conversation skills with our AI language assistant</p>
            </div>
            
            <div class="conversation-interface">
                <div class="conversation-sidebar">
                    <div class="conversation-settings">
                        <h3>Conversation Settings</h3>
                        <div class="setting-group">
                            <label for="conversation-topic">Topic:</label>
                            <select id="conversation-topic">
                                <option value="general">General Conversation</option>
                                <option value="restaurant">At a Restaurant</option>
                                <option value="shopping">Shopping</option>
                                <option value="travel">Travel</option>
                                <option value="work">At Work</option>
                                <option value="family">Family</option>
                            </select>
                        </div>
                        <div class="setting-group">
                            <label for="conversation-level">Difficulty Level:</label>
                            <select id="conversation-level">
                                <option value="A1" <?php echo ($level['level_code'] === 'A1') ? 'selected' : ''; ?>>Beginner (A1)</option>
                                <option value="A2" <?php echo ($level['level_code'] === 'A2') ? 'selected' : ''; ?>>Elementary (A2)</option>
                                <option value="B1" <?php echo ($level['level_code'] === 'B1') ? 'selected' : ''; ?>>Intermediate (B1)</option>
                                <option value="B2" <?php echo ($level['level_code'] === 'B2') ? 'selected' : ''; ?>>Upper Intermediate (B2)</option>
                                <option value="C1" <?php echo ($level['level_code'] === 'C1') ? 'selected' : ''; ?>>Advanced (C1)</option>
                            </select>
                        </div>
                        <div class="setting-group">
                            <label for="conversation-mode">Mode:</label>
                            <select id="conversation-mode">
                                <option value="mixed">Mixed (Spanish & English)</option>
                                <option value="spanish">Spanish Only</option>
                                <option value="english">English Only</option>
                            </select>
                        </div>
                        <button id="start-conversation" class="btn btn-primary">Start New Conversation</button>
                    </div>
                    
                    <div class="conversation-tips">
                        <h3>Conversation Tips</h3>
                        <ul>
                            <li><i class="fas fa-lightbulb"></i> Ask open-ended questions to keep the conversation going</li>
                            <li><i class="fas fa-lightbulb"></i> Try to use vocabulary from your recent lessons</li>
                            <li><i class="fas fa-lightbulb"></i> Don't worry about making mistakes - practice is key!</li>
                            <li><i class="fas fa-lightbulb"></i> Use "¿Cómo se dice...?" to ask how to say something</li>
                            <li><i class="fas fa-lightbulb"></i> Type "help" for assistance during the conversation</li>
                        </ul>
                    </div>
                </div>
                
                <div class="conversation-main">
                    <div class="conversation-header">
                        <div class="ai-profile">
                            <div class="ai-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="ai-info">
                                <h3>Spanish Conversation Partner</h3>
                                <p>AI Language Assistant</p>
                            </div>
                        </div>
                        <div class="conversation-actions">
                            <button id="toggle-translation" class="btn btn-outline"><i class="fas fa-language"></i> Show Translations</button>
                            <button id="toggle-corrections" class="btn btn-outline"><i class="fas fa-check-double"></i> Show Corrections</button>
                            <button id="toggle-voice" class="btn btn-outline"><i class="fas fa-volume-up"></i> Voice Mode</button>
                        </div>
                    </div>
                    
                    <div id="conversation-messages" class="conversation-messages">
                        <!-- Messages will be added here -->
                        <div class="ai-message">
                            <div class="ai-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content">
                                <p>¡Hola! I'm your Spanish conversation partner. Choose a topic and difficulty level, then click "Start New Conversation" to begin practicing!</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="conversation-input">
                        <textarea id="user-message" placeholder="Type your message here..."></textarea>
                        <div class="input-actions">
                            <button id="voice-input" class="btn btn-outline"><i class="fas fa-microphone"></i></button>
                            <button id="send-message" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="js/user-dropdown.js"></script>
    
    <!-- AI Conversation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const conversationMessages = document.getElementById('conversation-messages');
            const userMessageInput = document.getElementById('user-message');
            const sendMessageButton = document.getElementById('send-message');
            const startConversationButton = document.getElementById('start-conversation');
            const topicSelect = document.getElementById('conversation-topic');
            const levelSelect = document.getElementById('conversation-level');
            const modeSelect = document.getElementById('conversation-mode');
            const toggleTranslationButton = document.getElementById('toggle-translation');
            const toggleCorrectionsButton = document.getElementById('toggle-corrections');
            const toggleVoiceButton = document.getElementById('toggle-voice');
            const voiceInputButton = document.getElementById('voice-input');
            
            let showTranslations = false;
            let showCorrections = false;
            let voiceModeEnabled = false;
            let conversationContext = '';
            let speechSynthesis = window.speechSynthesis;
            
            // Voice settings state
            let voiceSettings = {
                voice: null,
                rate: 0.9,
                pitch: 1.0,
                volume: 1.0
            };
            
            // Initialize voice settings UI
            initVoiceSettings();
            
            // Toggle voice mode
            toggleVoiceButton.addEventListener('click', function() {
                voiceModeEnabled = !voiceModeEnabled;
                this.classList.toggle('active');
                
                if (voiceModeEnabled) {
                    // Notify user that voice mode is enabled
                    const notification = document.createElement('div');
                    notification.className = 'xp-notification';
                    notification.innerHTML = `<i class="fas fa-volume-up"></i> Voice mode enabled`;
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.classList.add('show');
                        setTimeout(() => {
                            notification.classList.remove('show');
                            setTimeout(() => notification.remove(), 500);
                        }, 3000);
                    }, 100);
                }
            });
            
            // Toggle translations
            toggleTranslationButton.addEventListener('click', function() {
                showTranslations = !showTranslations;
                this.classList.toggle('active');
                
                // Update all messages to show/hide translations
                document.querySelectorAll('.translation').forEach(el => {
                    el.style.display = showTranslations ? 'block' : 'none';
                });
            });
            
            // Toggle corrections
            toggleCorrectionsButton.addEventListener('click', function() {
                showCorrections = !showCorrections;
                this.classList.toggle('active');
                
                // Update all messages to show/hide corrections
                document.querySelectorAll('.correction').forEach(el => {
                    el.style.display = showCorrections ? 'block' : 'none';
                });
            });
            
            // Start new conversation
            startConversationButton.addEventListener('click', function() {
                const topic = topicSelect.value;
                const level = levelSelect.value;
                const mode = modeSelect.value;
                
                // Clear previous messages except the first one
                while (conversationMessages.children.length > 1) {
                    conversationMessages.removeChild(conversationMessages.lastChild);
                }
                
                // Show loading state
                addAIMessage("Starting a new conversation about " + getTopicName(topic) + " at " + level + " level...", true);
                
                // Make API call to start conversation
                fetch('api/chatgpt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'start_conversation',
                        topic: topic,
                        level: level,
                        mode: mode
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // Remove loading message
                    conversationMessages.removeChild(conversationMessages.lastChild);
                    
                    // Add AI's initial message
                    addAIMessage(data.message);
                    
                    // Speak the message if voice mode is enabled
                    if (voiceModeEnabled) {
                        speakText(data.message, modeSelect.value === 'english' ? 'en-US' : 'es-ES');
                    }
                    
                    // Store conversation context
                    conversationContext = data.conversation_id || '';
                    
                    // Enable input
                    userMessageInput.disabled = false;
                    sendMessageButton.disabled = false;
                })
                .catch(error => {
                    console.error('Error starting conversation:', error);
                    // Remove loading message
                    if (conversationMessages.lastChild.classList.contains('loading')) {
                        conversationMessages.removeChild(conversationMessages.lastChild);
                    }
                    addAIMessage("Sorry, there was an error starting the conversation. Please try again. Error: " + error.message);
                });
            });
            
            // Update the sendMessageButton event listener:
            sendMessageButton.addEventListener('click', function() {
                sendUserMessage();
            });
            
            userMessageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendUserMessage();
                }
            });
            
            function sendUserMessage() {
                const userMessage = userMessageInput.value.trim();
                
                if (!userMessage) return;
                
                // Add user message to the conversation
                addUserMessage(userMessage);
                
                // Clear input
                userMessageInput.value = '';
                
                // Disable input while waiting for response
                userMessageInput.disabled = true;
                sendMessageButton.disabled = true;
                
                // Add loading message
                addAIMessage('', true);
                
                // Make API call to get response
                fetch('api/chatgpt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'send_message',
                        message: userMessage,
                        conversation_id: conversationContext
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // Remove loading message
                    conversationMessages.removeChild(conversationMessages.lastChild);
                    
                    // Add AI's response
                    addAIMessage(data.message, false, data.translation, data.corrections);
                    
                    // Speak the message if voice mode is enabled
                    if (voiceModeEnabled) {
                        speakText(data.message, modeSelect.value === 'english' ? 'en-US' : 'es-ES');
                    }
                    
                    // Enable input
                    userMessageInput.disabled = false;
                    sendMessageButton.disabled = false;
                    userMessageInput.focus();
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    // Remove loading message
                    if (conversationMessages.lastChild.classList.contains('loading')) {
                        conversationMessages.removeChild(conversationMessages.lastChild);
                    }
                    addAIMessage("Sorry, there was an error processing your message. Please try again. Error: " + error.message);
                    
                    // Enable input
                    userMessageInput.disabled = false;
                    sendMessageButton.disabled = false;
                });
            }
            
            // Enhanced speak function with settings
            function speakText(text, language = 'es-ES') {
                if (!speechSynthesis) {
                    console.error('Speech synthesis not supported');
                    return;
                }
                
                // Cancel any ongoing speech
                speechSynthesis.cancel();
                
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = language;
                
                // Apply voice settings
                if (voiceSettings.voice) {
                    utterance.voice = voiceSettings.voice;
                    console.log('Using voice:', voiceSettings.voice.name);
                } else {
                    // Fallback to language-specific voice
                    const voices = speechSynthesis.getVoices();
                    const langVoice = findBestVoice(language);
                    if (langVoice) {
                        utterance.voice = langVoice;
                        console.log('Using fallback voice:', langVoice.name);
                    }
                }
                
                utterance.rate = voiceSettings.rate;
                utterance.pitch = voiceSettings.pitch;
                utterance.volume = voiceSettings.volume;
                
                // Add visual indicator for the speaking message
                const playButtons = document.querySelectorAll('.play-voice-btn');
                const lastPlayButton = playButtons[playButtons.length - 1];
                if (lastPlayButton) {
                    lastPlayButton.classList.add('speaking');
                    
                    utterance.onend = function() {
                        lastPlayButton.classList.remove('speaking');
                    };
                    
                    utterance.onerror = function() {
                        lastPlayButton.classList.remove('speaking');
                    };
                }
                
                // Speak with a small delay to ensure voice is loaded
                setTimeout(() => {
                    speechSynthesis.speak(utterance);
                }, 100);
            }
            
            // Add these helper functions to your JavaScript
            
            function addUserMessage(message) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'user-message';
                
                const userAvatar = document.createElement('div');
                userAvatar.className = 'user-avatar';
                
                // Use profile initial or image
                const initialSpan = document.createElement('span');
                initialSpan.textContent = '<?php echo strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>';
                userAvatar.appendChild(initialSpan);
                
                const messageContent = document.createElement('div');
                messageContent.className = 'message-content';
                
                const messagePara = document.createElement('p');
                messagePara.textContent = message;
                messageContent.appendChild(messagePara);
                
                messageDiv.appendChild(userAvatar);
                messageDiv.appendChild(messageContent);
                
                conversationMessages.appendChild(messageDiv);
                
                // Scroll to bottom
                conversationMessages.scrollTop = conversationMessages.scrollHeight;
            }
            
            function addAIMessage(message, isLoading = false, translation = '', corrections = []) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'ai-message';
                if (isLoading) {
                    messageDiv.classList.add('loading');
                }
                
                const aiAvatar = document.createElement('div');
                aiAvatar.className = 'ai-avatar';
                
                const avatarIcon = document.createElement('i');
                avatarIcon.className = 'fas fa-robot';
                aiAvatar.appendChild(avatarIcon);
                
                const messageContent = document.createElement('div');
                messageContent.className = 'message-content';
                
                if (isLoading) {
                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'typing-indicator';
                    for (let i = 0; i < 3; i++) {
                        const dot = document.createElement('span');
                        loadingDiv.appendChild(dot);
                    }
                    messageContent.appendChild(loadingDiv);
                } else {
                    const messagePara = document.createElement('p');
                    messagePara.textContent = message;
                    messageContent.appendChild(messagePara);
                    
                    // Add play button for voice playback
                    if (!isLoading && message) {
                        const playButton = document.createElement('button');
                        playButton.className = 'play-voice-btn';
                        playButton.innerHTML = '<i class="fas fa-volume-up"></i>';
                        playButton.title = 'Play message';
                        playButton.addEventListener('click', function() {
                            speakText(message, modeSelect.value === 'english' ? 'en-US' : 'es-ES');
                        });
                        messageContent.appendChild(playButton);
                    }
                    
                    // Add translation if available and translations are enabled
                    if (translation && showTranslations) {
                        const translationDiv = document.createElement('div');
                        translationDiv.className = 'translation';
                        translationDiv.innerHTML = `<i class="fas fa-language"></i> ${translation}`;
                        messageContent.appendChild(translationDiv);
                    }
                    
                    // Add corrections if available and corrections are enabled
                    if (corrections.length > 0 && showCorrections) {
                        const correctionsDiv = document.createElement('div');
                        correctionsDiv.className = 'corrections';
                        correctionsDiv.innerHTML = '<i class="fas fa-check-double"></i> Corrections:';
                        
                        const correctionsList = document.createElement('ul');
                        corrections.forEach(correction => {
                            const correctionItem = document.createElement('li');
                            correctionItem.innerHTML = `<span class="incorrect">${correction.incorrect}</span> → <span class="correct">${correction.correct}</span>`;
                            correctionsList.appendChild(correctionItem);
                        });
                        
                        correctionsDiv.appendChild(correctionsList);
                        messageContent.appendChild(correctionsDiv);
                    }
                }
                
                messageDiv.appendChild(aiAvatar);
                messageDiv.appendChild(messageContent);
                
                conversationMessages.appendChild(messageDiv);
                
                // Scroll to bottom
                conversationMessages.scrollTop = conversationMessages.scrollHeight;
            }
            
            // Enhanced voice input button functionality
            voiceInputButton.addEventListener('click', function() {
                if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    const recognition = new SpeechRecognition();
                    
                    // Set language based on conversation mode
                    recognition.lang = modeSelect.value === 'spanish' ? 'es-ES' : 'en-US';
                    recognition.interimResults = false;
                    recognition.maxAlternatives = 1;
                    
                    // Show recording indicator
                    voiceInputButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                    voiceInputButton.classList.add('recording');
                    
                    // Add visual feedback for recording
                    const recordingIndicator = document.createElement('div');
                    recordingIndicator.className = 'recording-indicator';
                    recordingIndicator.innerHTML = `<i class="fas fa-microphone"></i> Listening... (${recognition.lang})`;
                    document.querySelector('.conversation-input').appendChild(recordingIndicator);
                    
                    recognition.start();
                    
                    recognition.onresult = function(event) {
                        const transcript = event.results[0][0].transcript;
                        userMessageInput.value = transcript;
                        
                        // Auto-send after a short delay
                        setTimeout(() => {
                            if (voiceModeEnabled) {
                                sendUserMessage();
                            }
                        }, 500);
                    };
                    
                    recognition.onerror = function(event) {
                        console.error('Speech recognition error', event.error);
                        // Reset recording indicator
                        voiceInputButton.innerHTML = '<i class="fas fa-microphone"></i>';
                        voiceInputButton.classList.remove('recording');
                        document.querySelector('.recording-indicator')?.remove();
                    };
                    
                    recognition.onend = function() {
                        // Reset recording indicator
                        voiceInputButton.innerHTML = '<i class="fas fa-microphone"></i>';
                        voiceInputButton.classList.remove('recording');
                        document.querySelector('.recording-indicator')?.remove();
                    };
                } else {
                    alert('Speech recognition is not supported in your browser. Try Chrome or Edge.');
                }
            });
            
            function getTopicName(topicValue) {
                const topics = {
                    'general': 'General Conversation',
                    'restaurant': 'At a Restaurant',
                    'shopping': 'Shopping',
                    'travel': 'Travel',
                    'work': 'At Work',
                    'family': 'Family'
                };
                
                return topics[topicValue] || topicValue;
            }
            
            // Voice settings initialization
            function initVoiceSettings() {
                // Add voice settings button to the UI
                const settingsButton = document.createElement('button');
                settingsButton.id = 'voice-settings-toggle';
                settingsButton.innerHTML = '<i class="fas fa-cog"></i>';
                settingsButton.title = 'Voice Settings';
                toggleVoiceButton.appendChild(settingsButton);
                
                // Create voice settings panel
                const settingsPanel = document.createElement('div');
                settingsPanel.className = 'voice-settings-panel';
                settingsPanel.innerHTML = `
                    <h4>Voice Settings <button id="close-voice-settings"><i class="fas fa-times"></i></button></h4>
                    <div class="voice-setting-group">
                        <label for="voice-select">Voice:</label>
                        <select id="voice-select"></select>
                    </div>
                    <div class="voice-setting-group">
                        <label for="voice-rate">Speed: <span class="range-value">0.9</span></label>
                        <input type="range" id="voice-rate" min="0.5" max="1.5" step="0.1" value="0.9">
                    </div>
                    <div class="voice-setting-group">
                        <label for="voice-pitch">Pitch: <span class="range-value">1.0</span></label>
                        <input type="range" id="voice-pitch" min="0.8" max="1.2" step="0.1" value="1.0">
                    </div>
                    <div class="voice-setting-group">
                        <label for="voice-volume">Volume: <span class="range-value">1.0</span></label>
                        <input type="range" id="voice-volume" min="0" max="1" step="0.1" value="1.0">
                    </div>
                    <div class="voice-setting-group">
                        <label>Voice Quality:</label>
                        <div class="voice-quality-options">
                            <label><input type="radio" name="voice-quality" value="premium" checked> Premium</label>
                            <label><input type="radio" name="voice-quality" value="any"> Any</label>
                        </div>
                    </div>
                    <div class="voice-setting-group">
                        <label>Voice Gender:</label>
                        <div class="voice-gender-options">
                            <label><input type="radio" name="voice-gender" value="female" checked> Female</label>
                            <label><input type="radio" name="voice-gender" value="male"> Male</label>
                            <label><input type="radio" name="voice-gender" value="any"> Any</label>
                        </div>
                    </div>
                    <button id="test-voice" class="voice-test-btn">Test Voice</button>
                    <div class="voice-status"></div>
                `;
                
                document.querySelector('.conversation-main').appendChild(settingsPanel);
                
                // Initialize speech synthesis
                if (speechSynthesis.onvoiceschanged !== undefined) {
                    speechSynthesis.onvoiceschanged = populateVoiceList;
                }
                
                // Try to populate voices immediately
                populateVoiceList();
                
                // Try again after a delay (sometimes voices take time to load)
                setTimeout(populateVoiceList, 1000);
                
                // Event listeners for voice settings
                document.getElementById('voice-select').addEventListener('change', function() {
                    const voices = speechSynthesis.getVoices();
                    voiceSettings.voice = voices.find(voice => voice.name === this.value) || null;
                    
                    // Test the voice immediately
                    if (voiceSettings.voice) {
                        const testUtterance = new SpeechSynthesisUtterance("Test");
                        testUtterance.voice = voiceSettings.voice;
                        speechSynthesis.speak(testUtterance);
                        speechSynthesis.cancel(); // Just load the voice, don't actually speak
                        
                        document.querySelector('.voice-status').textContent = 
                            `Selected: ${voiceSettings.voice.name} (${voiceSettings.voice.lang})`;
                    }
                });
                
                document.getElementById('voice-rate').addEventListener('input', function() {
                    voiceSettings.rate = parseFloat(this.value);
                    this.parentNode.querySelector('.range-value').textContent = this.value;
                });
                
                document.getElementById('voice-pitch').addEventListener('input', function() {
                    voiceSettings.pitch = parseFloat(this.value);
                    this.parentNode.querySelector('.range-value').textContent = this.value;
                });
                
                document.getElementById('voice-volume').addEventListener('input', function() {
                    voiceSettings.volume = parseFloat(this.value);
                    this.parentNode.querySelector('.range-value').textContent = this.value;
                });
                
                // Test voice button
                document.getElementById('test-voice').addEventListener('click', function() {
                    const testText = "Hola, ¿cómo estás? Soy tu asistente de conversación.";
                    speakText(testText);
                });
                
                // Toggle settings panel
                settingsButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    settingsPanel.classList.toggle('show');
                });
                
                // Close settings panel
                document.getElementById('close-voice-settings').addEventListener('click', function() {
                    settingsPanel.classList.remove('show');
                });
                
                // Close panel when clicking outside
                document.addEventListener('click', function(e) {
                    if (!settingsPanel.contains(e.target) && e.target !== settingsButton) {
                        settingsPanel.classList.remove('show');
                    }
                });
                
                // Add a button to manually refresh voices
                const refreshButton = document.createElement('button');
                refreshButton.className = 'btn btn-sm btn-outline';
                refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
                refreshButton.title = 'Refresh Voices';
                refreshButton.style.marginLeft = '5px';
                refreshButton.addEventListener('click', function() {
                    populateVoiceList();
                    listAllVoices();
                    
                    // Notify user
                    document.querySelector('.voice-status').textContent = 'Voice list refreshed!';
                    setTimeout(() => {
                        document.querySelector('.voice-status').textContent = 
                            voiceSettings.voice ? 
                            `Using: ${voiceSettings.voice.name}` : 
                            'No voice selected';
                    }, 2000);
                });
                
                document.querySelector('.voice-settings-panel h4').appendChild(refreshButton);
                
                // Add event listeners for voice quality and gender options
                document.querySelectorAll('input[name="voice-quality"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        const qualityPreference = this.value;
                        const genderPreference = document.querySelector('input[name="voice-gender"]:checked').value;
                        
                        // Update voice selection based on preferences
                        updateVoiceSelection(qualityPreference, genderPreference);
                    });
                });
                
                document.querySelectorAll('input[name="voice-gender"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        const genderPreference = this.value;
                        const qualityPreference = document.querySelector('input[name="voice-quality"]:checked').value;
                        
                        // Update voice selection based on preferences
                        updateVoiceSelection(qualityPreference, genderPreference);
                    });
                });
            }
            
            // Populate voice select dropdown
            function populateVoiceList() {
                const voiceSelect = document.getElementById('voice-select');
                if (!voiceSelect) return;
                
                voiceSelect.innerHTML = '';
                
                const voices = speechSynthesis.getVoices();
                console.log(`Loaded ${voices.length} voices`);
                
                if (voices.length === 0) {
                    const option = document.createElement('option');
                    option.textContent = 'No voices available';
                    voiceSelect.appendChild(option);
                    return;
                }
                
                // Group voices by language
                const voicesByLang = {};
                voices.forEach(voice => {
                    const lang = voice.lang.split('-')[0];
                    if (!voicesByLang[lang]) {
                        voicesByLang[lang] = [];
                    }
                    voicesByLang[lang].push(voice);
                });
                
                // Create option groups by language
                for (const lang in voicesByLang) {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = getLangName(lang);
                    
                    voicesByLang[lang].forEach(voice => {
                        const option = document.createElement('option');
                        option.value = voice.name;
                        option.textContent = `${voice.name} (${voice.lang})`;
                        
                        // Prefer higher quality voices
                        if (voice.localService === false) {
                            option.textContent += ' ★'; // Mark cloud voices with a star
                        }
                        
                        optgroup.appendChild(option);
                    });
                    
                    voiceSelect.appendChild(optgroup);
                }
                
                // Try to select a good Spanish voice by default
                const bestVoice = findBestVoice('es-ES');
                if (bestVoice) {
                    voiceSelect.value = bestVoice.name;
                    voiceSettings.voice = bestVoice;
                    
                    document.querySelector('.voice-status').textContent = 
                        `Using: ${bestVoice.name} (${bestVoice.lang})`;
                }
            }
            
            // Get language name from code
            function getLangName(code) {
                const langNames = {
                    'es': 'Spanish',
                    'en': 'English',
                    'fr': 'French',
                    'de': 'German',
                    'it': 'Italian',
                    'pt': 'Portuguese',
                    'ru': 'Russian',
                    'ja': 'Japanese',
                    'ko': 'Korean',
                    'zh': 'Chinese'
                };
                return langNames[code] || code.toUpperCase();
            }
            
            // Find the best voice for a language
            function findBestVoice(language) {
                const voices = speechSynthesis.getVoices();
                if (voices.length === 0) return null;
                
                const langCode = language.split('-')[0];
                
                // Premium voice providers (in order of preference)
                const premiumProviders = [
                    'Google', 'Microsoft', 'Amazon', 'Neural', 'Premium', 'Enhanced'
                ];
                
                // First try to find premium voices for the language
                for (const provider of premiumProviders) {
                    const premiumVoice = voices.find(v => 
                        v.lang.startsWith(langCode) && 
                        v.name.includes(provider)
                    );
                    
                    if (premiumVoice) {
                        console.log(`Found premium ${provider} voice:`, premiumVoice.name);
                        return premiumVoice;
                    }
                }
                
                // Then try to find any female voice (often preferred for language learning)
                const femaleVoice = voices.find(v => 
                    v.lang.startsWith(langCode) && 
                    (v.name.includes('Female') || v.name.includes('Samantha') || 
                     v.name.includes('Monica') || v.name.includes('Paulina') || 
                     v.name.includes('Lucia'))
                );
                
                if (femaleVoice) {
                    console.log('Found female voice:', femaleVoice.name);
                    return femaleVoice;
                }
                
                // Then try any voice for the language
                const anyVoice = voices.find(v => v.lang.startsWith(langCode));
                if (anyVoice) {
                    console.log('Found language voice:', anyVoice.name);
                    return anyVoice;
                }
                
                // Fallback to any voice
                console.log('No matching voice found, using default');
                return voices[0];
            }
            
            // List all available voices for debugging
            function listAllVoices() {
                const voices = speechSynthesis.getVoices();
                console.log('Available voices:', voices.length);
                
                voices.forEach((voice, i) => {
                    console.log(`${i+1}. ${voice.name} (${voice.lang}) - ${voice.localService ? 'Local' : 'Cloud'}`);
                });
            }
            
            // Update voice selection based on preferences
            function updateVoiceSelection(quality, gender) {
                const voices = speechSynthesis.getVoices();
                const voiceSelect = document.getElementById('voice-select');
                const language = modeSelect.value === 'spanish' ? 'es' : 'en';
                
                // Filter voices by language
                let filteredVoices = voices.filter(v => v.lang.startsWith(language));
                
                // Filter by quality if premium is selected
                if (quality === 'premium') {
                    const premiumVoices = filteredVoices.filter(v => 
                        v.name.includes('Google') || 
                        v.name.includes('Microsoft') || 
                        v.name.includes('Neural') || 
                        v.name.includes('Premium') || 
                        v.name.includes('Enhanced') ||
                        v.localService === false
                    );
                    
                    if (premiumVoices.length > 0) {
                        filteredVoices = premiumVoices;
                    }
                }
                
                // Filter by gender if specified
                if (gender !== 'any') {
                    const genderKeywords = gender === 'female' ? 
                        ['Female', 'Samantha', 'Monica', 'Paulina', 'Lucia', 'Elsa', 'Ines'] : 
                        ['Male', 'Daniel', 'Jorge', 'Juan', 'Diego', 'Miguel'];
                    
                    const genderVoices = filteredVoices.filter(v => 
                        genderKeywords.some(keyword => v.name.includes(keyword))
                    );
                    
                    if (genderVoices.length > 0) {
                        filteredVoices = genderVoices;
                    }
                }
                
                // If we have filtered voices, select the best one
                if (filteredVoices.length > 0) {
                    // Sort by preference (premium providers first)
                    filteredVoices.sort((a, b) => {
                        const aIsPremium = premiumProviders.some(provider => a.name.includes(provider));
                        const bIsPremium = premiumProviders.some(provider => b.name.includes(provider));
                        
                        if (aIsPremium && !bIsPremium) return -1;
                        if (!aIsPremium && bIsPremium) return 1;
                        return 0;
                    });
                    
                    // Select the best voice
                    const bestVoice = filteredVoices[0];
                    voiceSelect.value = bestVoice.name;
                    voiceSettings.voice = bestVoice;
                    
                    // Update status
                    document.querySelector('.voice-status').textContent = 
                        `Selected: ${bestVoice.name} (${bestVoice.lang})`;
                    
                    // Test the voice
                    const testUtterance = new SpeechSynthesisUtterance("Test");
                    testUtterance.voice = bestVoice;
                    speechSynthesis.speak(testUtterance);
                    speechSynthesis.cancel(); // Just load the voice, don't actually speak
                }
            }
        });
    </script>
</body>
</html>