<?php
// Include configuration
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Return error if not logged in
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get the request body
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Check if action is provided
if (!isset($data['action']) || empty($data['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No action provided']);
    exit;
}

// Get OpenAI API key from config
$api_key = OPENAI_API_KEY; // Make sure this is defined in your config.php

// Get user data for context
$user_id = $_SESSION['user_id'];
try {
    // Fetch user's level data
    $level_stmt = $pdo->prepare("
        SELECT pl.level_code 
        FROM user_progress up 
        JOIN proficiency_levels pl ON up.current_level_id = pl.level_id 
        WHERE up.user_id = :user_id
    ");
    $level_stmt->bindParam(':user_id', $user_id);
    $level_stmt->execute();
    $level_data = $level_stmt->fetch();
    
    $user_level = $level_data ? $level_data['level_code'] : 'A1';
} catch (PDOException $e) {
    error_log("Error fetching user level: " . $e->getMessage());
    $user_level = 'A1'; // Default to beginner level
}

// Handle different actions
switch ($data['action']) {
    case 'start':
        handleStartConversation($data, $api_key, $user_level, $user_id, $pdo);
        break;
    case 'message':
        handleUserMessage($data, $api_key, $user_level, $user_id, $pdo);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

/**
 * Handle starting a new conversation
 */
function handleStartConversation($data, $api_key, $user_level, $user_id, $pdo) {
    // Extract conversation settings
    $topic = $data['topic'] ?? 'general';
    $level = $data['level'] ?? $user_level;
    $mode = $data['mode'] ?? 'mixed';
    
    // Create system message based on settings
    $system_message = createSystemMessage($topic, $level, $mode);
    
    // Create initial user message
    $user_message = "Let's start a conversation about " . getTopicDescription($topic);
    
    // Store conversation in session
    $_SESSION['conversation'] = [
        'messages' => [
            ['role' => 'system', 'content' => $system_message],
            ['role' => 'user', 'content' => $user_message]
        ],
        'topic' => $topic,
        'level' => $level,
        'mode' => $mode
    ];
    
    // Make API request
    $response = makeOpenAIRequest($_SESSION['conversation']['messages'], $api_key);
    
    if (isset($response['error'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Add assistant response to conversation history
    $_SESSION['conversation']['messages'][] = [
        'role' => 'assistant',
        'content' => $response['content']
    ];
    
    // Generate translation if needed
    $translation = null;
    if ($mode === 'spanish' || ($mode === 'mixed' && preg_match('/[áéíóúüñ¿¡]/i', $response['content']))) {
        $translation = generateTranslation($response['content'], 'es', 'en', $api_key);
    }
    
    // Log conversation start
    try {
        $stmt = $pdo->prepare("
            INSERT INTO conversation_sessions 
            (user_id, topic, level, mode, started_at) 
            VALUES (:user_id, :topic, :level, :mode, NOW())
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':topic', $topic);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':mode', $mode);
        $stmt->execute();
        
        $_SESSION['conversation_id'] = $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error logging conversation start: " . $e->getMessage());
    }
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode([
        'response' => $response['content'],
        'translation' => $translation
    ]);
    exit;
}

/**
 * Handle user message in conversation
 */
function handleUserMessage($data, $api_key, $user_level, $user_id, $pdo) {
    // Check if conversation exists
    if (!isset($_SESSION['conversation'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No active conversation']);
        exit;
    }
    
    // Extract message
    $message = $data['message'] ?? '';
    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No message provided']);
        exit;
    }
    
    // Add user message to conversation history
    $_SESSION['conversation']['messages'][] = [
        'role' => 'user',
        'content' => $message
    ];
    
    // Check if we need to trim conversation history (keep it under token limit)
    if (count($_SESSION['conversation']['messages']) > 12) {
        // Keep system message and last 10 messages
        $system_message = $_SESSION['conversation']['messages'][0];
        $_SESSION['conversation']['messages'] = array_slice($_SESSION['conversation']['messages'], -10);
        array_unshift($_SESSION['conversation']['messages'], $system_message);
    }
    
    // Make API request
    $response = makeOpenAIRequest($_SESSION['conversation']['messages'], $api_key);
    
    if (isset($response['error'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Add assistant response to conversation history
    $_SESSION['conversation']['messages'][] = [
        'role' => 'assistant',
        'content' => $response['content']
    ];
    
    // Generate translation if needed
    $translation = null;
    $mode = $_SESSION['conversation']['mode'];
    if ($mode === 'spanish' || ($mode === 'mixed' && preg_match('/[áéíóúüñ¿¡]/i', $response['content']))) {
        $translation = generateTranslation($response['content'], 'es', 'en', $api_key);
    }
    
    // Check for grammar/spelling errors and provide correction
    $correction = null;
    if ($_SESSION['conversation']['level'] !== 'C1' && preg_match('/[áéíóúüñ¿¡]/i', $message)) {
        $correction = generateCorrection($message, $api_key);
    }
    
    // Calculate XP earned
    $xp_earned = calculateXP($message, $_SESSION['conversation']['level']);
    
    // Update user XP
    if ($xp_earned > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE user_progress 
                SET xp_points = xp_points + :xp_earned,
                    last_activity_date = NOW()
                WHERE user_id = :user_id
            ");
            $stmt->bindParam(':xp_earned', $xp_earned);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating user XP: " . $e->getMessage());
        }
    }
    
    // Log conversation message
    if (isset($_SESSION['conversation_id'])) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO conversation_messages 
                (conversation_id, is_user, message, response, created_at) 
                VALUES (:conversation_id, 1, :message, :response, NOW())
            ");
            $stmt->bindParam(':conversation_id', $_SESSION['conversation_id']);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':response', $response['content']);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error logging conversation message: " . $e->getMessage());
        }
    }
    
    // Return response
    header('Content-Type: application/json');
    echo json_encode([
        'response' => $response['content'],
        'translation' => $translation,
        'correction' => $correction,
        'xp_earned' => $xp_earned
    ]);
    exit;
}

/**
 * Create system message based on conversation settings
 */
function createSystemMessage($topic, $level, $mode) {
    $topic_description = getTopicDescription($topic);
    
    $level_instructions = [
        'A1' => 'Use very simple vocabulary and basic present tense. Speak slowly with short sentences. Stick to common everyday topics.',
        'A2' => 'Use simple vocabulary and basic present/past tenses. Keep sentences relatively short. Focus on common situations.',
        'B1' => 'Use intermediate vocabulary and a variety of tenses. Introduce some idiomatic expressions. Discuss a wider range of topics.',
        'B2' => 'Use advanced vocabulary and complex sentence structures. Include idiomatic expressions and cultural references.',
        'C1' => 'Use sophisticated vocabulary and complex grammatical structures. Discuss abstract concepts and specialized topics.'
    ];
    
    $mode_instructions = [
        'mixed' => 'Respond primarily in Spanish but include English translations for difficult words or phrases. If the user speaks in English, respond with Spanish and English translations.',
        'spanish' => 'Respond only in Spanish, adjusting your language to be simpler if the user seems confused.',
        'english' => 'Respond primarily in English but include Spanish vocabulary and phrases to teach the user.'
    ];
    
    return "You are a friendly Spanish conversation partner helping someone practice their Spanish. 
    
Topic: {$topic_description}

Proficiency level: {$level}
{$level_instructions[$level]}

Language mode: {$mode_instructions[$mode]}

Additional instructions:
1. Keep your responses conversational and engaging.
2. Ask questions to keep the conversation going.
3. Gently correct major grammar or vocabulary errors if appropriate for their level.
4. If the user asks how to say something in Spanish, provide the translation.
5. If the user types 'help', provide suggestions for what they could say next.
6. Adapt to the user's interests and responses.";
}

/**
 * Get description for conversation topic
 */
function getTopicDescription($topic) {
    $topics = [
        'general' => 'general conversation topics like hobbies, weather, and daily life',
        'restaurant' => 'ordering food, asking about menu items, and restaurant etiquette',
        'shopping' => 'buying clothes, asking about prices, and shopping preferences',
        'travel' => 'planning trips, asking for directions, and talking about destinations',
        'work' => 'job interviews, office conversation, and professional development',
        'family' => 'family members, relationships, and family activities'
    ];
    
    return $topics[$topic] ?? 'general conversation';
}

/**
 * Make API request to OpenAI
 */
function makeOpenAIRequest($messages, $api_key) {
    $request_data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'max_tokens' => 300,
        'temperature' => 0.7
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_status != 200) {
        $error_data = json_decode($response, true);
        error_log('OpenAI API Error: ' . ($error_data['error']['message'] ?? 'Unknown error'));
        return ['error' => 'Failed to get response from AI'];
    }
    
    $response_data = json_decode($response, true);
    return [
        'content' => $response_data['choices'][0]['message']['content'] ?? 'I apologize, but I could not generate a response.'
    ];
}

/**
 * Generate translation using OpenAI
 */
function generateTranslation($text, $from_lang, $to_lang, $api_key) {
    $messages = [
        [
            'role' => 'system',
            'content' => "You are a translator from {$from_lang} to {$to_lang}. Provide only the translation without any additional text or explanations."
        ],
        [
            'role' => 'user',
            'content' => "Translate the following text from {$from_lang} to {$to_lang}: \"{$text}\""
        ]
    ];
    
    $response = makeOpenAIRequest($messages, $api_key);
    
    if (isset($response['error'])) {
        return null;
    }
    
    return $response['content'];
}

/**
 * Generate correction for user's Spanish
 */
function generateCorrection($text, $api_key) {
    $messages = [
        [
            'role' => 'system',
            'content' => "You are a Spanish language teacher. Review the following Spanish text and provide corrections for any grammar, spelling, or vocabulary errors. Format your response as: 'Correction: [corrected text]'. If there are no errors, respond with 'No corrections needed.'"
        ],
        [
            'role' => 'user',
            'content' => "Please correct this Spanish text: \"{$text}\""
        ]
    ];
    
    $response = makeOpenAIRequest($messages, $api_key);
    
    if (isset($response['error'])) {
        return null;
    }
    
    // Extract just the correction part
    $content = $response['content'];
    if (strpos($content, 'No corrections needed') !== false) {
        return null;
    }
    
    // Try to extract the corrected text
    if (preg_match('/Correction: (.*?)($|\.)/i', $content, $matches)) {
        return $matches[1];
    }
    
    return $content;
}

/**
 * Calculate XP earned for a message
 */
function calculateXP($message, $level) {
    // Base XP for sending a message
    $base_xp = 5;
    
    // Additional XP based on message length and complexity
    $length_xp = min(floor(strlen($message) / 20), 5); // Up to 5 XP for length
    
    // Additional XP for using Spanish (detected by Spanish characters)
    $spanish_xp = preg_match('/[áéíóúüñ¿¡]/i', $message) ? 5 : 0;
    
    // Level multiplier - higher levels get slightly less XP to encourage progression
    $level_multiplier = [
        'A1' => 1.2,  // Beginners get bonus XP
        'A2' => 1.1,
        'B1' => 1.0,
        'B2' => 0.9,
        'C1' => 0.8   // Advanced users need to work harder for XP
    ];
    
    $multiplier = $level_multiplier[$level] ?? 1.0;
    
    // Calculate total XP
    $total_xp = round(($base_xp + $length_xp + $spanish_xp) * $multiplier);
    
    return $total_xp;
}
?>