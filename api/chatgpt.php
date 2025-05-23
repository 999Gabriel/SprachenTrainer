<?php
// Include configuration
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Get the JSON data from the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Handle different actions
$action = $data['action'] ?? '';

switch ($action) {
    case 'start_conversation':
        startConversation($data);
        break;
    case 'send_message':
        sendMessage($data);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

/**
 * Start a new conversation with the AI
 */
function startConversation($data) {
    $topic = $data['topic'] ?? 'general';
    $level = $data['level'] ?? 'A1';
    $mode = $data['mode'] ?? 'mixed';
    
    // Create system message based on settings
    $system_message = "You are CerveLingua, a friendly and patient Spanish language tutor with a warm personality. ";
    
    switch ($topic) {
        case 'restaurant':
            $system_message .= "You're helping the user practice Spanish conversation in a restaurant setting. Act as if you're a waiter/waitress or fellow diner. Use relevant vocabulary about food, ordering, and dining etiquette. You are a very chill guy with which we can have great conversations about food, drinks, and services.";
            break;
        case 'shopping':
            $system_message .= "You're helping the user practice Spanish conversation in a shopping context. Act as if you're a shop assistant or fellow shopper. Use vocabulary related to clothing, prices, sizes, and shopping preferences. ";
            break;
        case 'travel':
            $system_message .= "You're helping the user practice Spanish conversation related to travel. Act as if you're a local guide or fellow traveler. Use vocabulary about destinations, transportation, accommodations, and tourist attractions. ";
            break;
        case 'work':
            $system_message .= "You're helping the user practice Spanish conversation in a work environment. Act as if you're a colleague or supervisor. Use vocabulary related to jobs, tasks, schedules, and professional interactions. ";
            break;
        case 'family':
            $system_message .= "You're helping the user practice Spanish conversation about family. Act as if you're a friend sharing about your own family. Use vocabulary related to family members, relationships, and family activities. ";
            break;
        default:
            $system_message .= "You're helping the user practice general Spanish conversation. Be conversational and friendly, as if chatting with a friend. Cover everyday topics naturally. ";
    }
    
    // Add level-specific instructions
    switch ($level) {
        case 'A1':
            $system_message .= "The user is at A1 (Beginner) level. Use very simple vocabulary and short sentences. Stick to present tense when possible. Repeat key phrases and provide gentle corrections. Speak slowly by using shorter sentences. ";
            break;
        case 'A2':
            $system_message .= "The user is at A2 (Elementary) level. Use simple vocabulary and basic grammar structures. You can introduce simple past tense. Provide encouragement and gentle corrections when appropriate. ";
            break;
        case 'B1':
            $system_message .= "The user is at B1 (Intermediate) level. You can use more complex sentences and varied vocabulary. Introduce more tenses and idiomatic expressions, but explain them if they're likely new. Encourage the user to expand their responses. ";
            break;
        case 'B2':
            $system_message .= "The user is at B2 (Upper Intermediate) level. You can use complex grammar and diverse vocabulary. Use natural speech patterns and colloquialisms. Challenge the user with questions that require detailed responses. ";
            break;
        case 'C1':
            $system_message .= "The user is at C1 (Advanced) level. You can use sophisticated language and idiomatic expressions. Discuss abstract concepts and use humor. Correct only significant errors and focus on nuance and style. ";
            break;
    }
    
    // Add mode-specific instructions
    switch ($mode) {
        case 'spanish':
            $system_message .= "Respond only in Spanish. If the user seems confused, simplify your language rather than switching to English. ";
            break;
        case 'english':
            $system_message .= "Respond only in English when explaining concepts, but provide Spanish examples. ";
            break;
        default:
            $system_message .= "Respond primarily in Spanish but provide English translations in parentheses for new or difficult vocabulary. If the user struggles, increase the amount of English support. ";
    }
    
    $system_message .= "Be conversational and natural - avoid sounding like a textbook. Ask follow-up questions to keep the conversation flowing. Adapt to the user's interests and responses. Provide gentle corrections only when necessary, focusing primarily on communication. Start the conversation with a warm, friendly greeting and an engaging question related to the topic.";
    
    // Create conversation ID
    $conversation_id = uniqid('conv_');
    
    // Call Ollama API
    $response = callOllamaAPI($system_message, "", "");
    
    if (isset($response['error'])) {
        // Return the error directly without fallback
        header('Content-Type: application/json');
        echo json_encode(['error' => $response['error']]);
        exit;
    }
    
    // Store conversation in session
    $_SESSION['conversations'][$conversation_id] = [
        'system_message' => $system_message,
        'messages' => [],
        'topic' => $topic,
        'level' => $level,
        'mode' => $mode
    ];
    
    // Add AI's response to the conversation
    $ai_message = $response['message'];
    $_SESSION['conversations'][$conversation_id]['messages'][] = [
        'role' => 'assistant',
        'content' => $ai_message
    ];
    
    // Return the response
    header('Content-Type: application/json');
    echo json_encode([
        'message' => $ai_message,
        'conversation_id' => $conversation_id
    ]);
    exit;
}

/**
 * Send a message to the AI and get a response
 */
function sendMessage($data) {
    $message = $data['message'] ?? '';
    $conversation_id = $data['conversation_id'] ?? '';
    
    if (!$message) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Message is required']);
        exit;
    }
    
    if (!$conversation_id || !isset($_SESSION['conversations'][$conversation_id])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid conversation ID']);
        exit;
    }
    
    // Get conversation from session
    $conversation = $_SESSION['conversations'][$conversation_id];
    $system_message = $conversation['system_message'];
    $messages = $conversation['messages'];
    
    // Build conversation history
    $history = "";
    foreach ($messages as $msg) {
        if ($msg['role'] === 'user') {
            $history .= "User: " . $msg['content'] . "\n";
        } else if ($msg['role'] === 'assistant') {
            $history .= "Assistant: " . $msg['content'] . "\n";
        }
    }
    
    // Add user message to the conversation
    $messages[] = ['role' => 'user', 'content' => $message];
    $_SESSION['conversations'][$conversation_id]['messages'] = $messages;
    
    // Call Ollama API
    $response = callOllamaAPI($system_message, $history, $message);
    
    if (isset($response['error'])) {
        // Return the error directly without fallback
        header('Content-Type: application/json');
        echo json_encode(['error' => $response['error']]);
        exit;
    }
    
    // Add AI's response to the conversation
    $ai_message = $response['message'];
    $messages[] = ['role' => 'assistant', 'content' => $ai_message];
    $_SESSION['conversations'][$conversation_id]['messages'] = $messages;
    
    // Generate translation and corrections if needed
    $translation = '';
    $corrections = [];
    
    // Return the response
    header('Content-Type: application/json');
    echo json_encode([
        'message' => $ai_message,
        'translation' => $translation,
        'corrections' => $corrections
    ]);
    exit;
}

/**
 * Call the Ollama API
 */
function callOllamaAPI($system_message, $history, $user_message) {
    // Get the correct host for Ollama
    $ollama_host = 'host.docker.internal'; // Default Docker for Mac hostname
    
    // Try to read from the host file if it exists
    if (file_exists(__DIR__ . '/../ollama_host.txt')) {
        $ollama_host = trim(file_get_contents(__DIR__ . '/../ollama_host.txt'));
    }
    
    // Use the models you have installed
    $models = ['gemma:7b-instruct', 'llama3:latest', 'llama2:latest'];
    
    foreach ($models as $model) {
        // Ollama API endpoint with the correct host
        $url = "http://$ollama_host:11434/api/generate";
        
        // Format the prompt for Ollama
        $prompt = "";
        if (!empty($system_message)) {
            $prompt .= "System: $system_message\n\n";
        }
        
        if (!empty($history)) {
            $prompt .= "$history\n";
        }
        
        if (!empty($user_message)) {
            $prompt .= "User: $user_message\n\nAssistant:";
        } else {
            $prompt .= "Assistant:";
        }
        
        // Prepare the request data
        $data = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false
        ];
        
        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        // Execute the request
        $response = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        // Log the attempt
        error_log("Ollama API call to $url with model $model - HTTP Code: $http_code");
        
        if ($curl_errno) {
            error_log("Ollama API error with model $model: $curl_error");
            continue; // Try next model
        }
        
        if ($http_code !== 200) {
            error_log("Ollama API HTTP error with model $model: $http_code - $response");
            continue; // Try next model
        }
        
        $response_data = json_decode($response, true);
        
        if (!isset($response_data['response'])) {
            error_log("Ollama API unexpected response format from model $model");
            continue; // Try next model
        }
        
        // Success! Return the response
        return ['message' => $response_data['response']];
    }
    
    // If all attempts failed, return error
    return ['error' => "Could not connect to Ollama at $ollama_host:11434. Please make sure Ollama is running with: ollama serve"];
}

/**
 * Try the Ollama generate API as a fallback
 */
function tryOllamaGenerateAPI($model, $system_message, $history, $user_message) {
    $url = 'http://127.0.0.1:11434/api/generate';
    
    // Format the prompt for Ollama
    $prompt = "";
    if (!empty($system_message)) {
        $prompt .= "System: $system_message\n\n";
    }
    
    if (!empty($history)) {
        $prompt .= "$history\n";
    }
    
    if (!empty($user_message)) {
        $prompt .= "User: $user_message\n\nAssistant:";
    } else {
        $prompt .= "Assistant:";
    }
    
    // Prepare the request data
    $data = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false
    ];
    
    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // For debugging
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    // Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    
    // Get verbose information
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    curl_close($ch);
    
    if ($curl_errno) {
        error_log("Ollama generate API error with model $model: $curl_error");
        error_log("Verbose log: $verboseLog");
        return ['error' => "Could not connect to Ollama generate API: $curl_error"];
    }
    
    if ($http_code !== 200) {
        error_log("Ollama generate API HTTP error with model $model: $http_code - $response");
        error_log("Verbose log: $verboseLog");
        return ['error' => "API error: HTTP code $http_code"];
    }
    
    $response_data = json_decode($response, true);
    
    if (!isset($response_data['response'])) {
        error_log("Ollama generate API unexpected response format from model $model: " . print_r($response_data, true));
        return ['error' => "Unexpected API response format"];
    }
    
    return ['message' => $response_data['response']];
}

/**
 * Generate a fallback response when Ollama is not available
 */
function generateFallbackResponse($system_message, $user_message = '') {
    // Extract topic and level from system message
    $topic = 'general';
    $level = 'A1';
    
    if (strpos($system_message, 'restaurant setting') !== false) {
        $topic = 'restaurant';
    } elseif (strpos($system_message, 'shopping context') !== false) {
        $topic = 'shopping';
    } elseif (strpos($system_message, 'travel') !== false) {
        $topic = 'travel';
    } elseif (strpos($system_message, 'work environment') !== false) {
        $topic = 'work';
    } elseif (strpos($system_message, 'family') !== false) {
        $topic = 'family';
    }
    
    if (strpos($system_message, 'A1') !== false) {
        $level = 'A1';
    } elseif (strpos($system_message, 'A2') !== false) {
        $level = 'A2';
    } elseif (strpos($system_message, 'B1') !== false) {
        $level = 'B1';
    } elseif (strpos($system_message, 'B2') !== false) {
        $level = 'B2';
    } elseif (strpos($system_message, 'C1') !== false) {
        $level = 'C1';
    }
    
    // If this is the first message (no user message)
    if (empty($user_message)) {
        switch ($topic) {
            case 'restaurant':
                return "¡Hola! Bienvenido al restaurante. ¿Puedo tomar tu orden? (Hello! Welcome to the restaurant. Can I take your order?)";
            case 'shopping':
                return "¡Hola! ¿Puedo ayudarte a encontrar algo hoy? (Hello! Can I help you find something today?)";
            case 'travel':
                return "¡Hola! ¿A dónde te gustaría viajar? (Hello! Where would you like to travel?)";
            case 'work':
                return "¡Hola! ¿Cómo va tu día de trabajo? (Hello! How is your work day going?)";
            case 'family':
                return "¡Hola! ¿Puedes hablarme de tu familia? (Hello! Can you tell me about your family?)";
            default:
                return "¡Hola! ¿Cómo estás hoy? (Hello! How are you today?)";
        }
    }
    
    // If responding to a user message
    $user_message = strtolower($user_message);
    
    if (strpos($user_message, 'hola') !== false || strpos($user_message, 'hello') !== false) {
        return "¡Hola! ¿Cómo estás? (Hello! How are you?)";
    }
    
    if (strpos($user_message, 'bien') !== false || strpos($user_message, 'good') !== false) {
        return "¡Me alegro! ¿Qué te gustaría hablar hoy? (I'm glad! What would you like to talk about today?)";
    }
    
    if (strpos($user_message, 'name') !== false || strpos($user_message, 'nombre') !== false) {
        return "Me llamo CerveLingua. Soy tu asistente de español. ¿Y tú? (My name is CerveLingua. I'm your Spanish assistant. And you?)";
    }
    
    // Default responses
    $default_responses = [
        "Interesante. Cuéntame más sobre eso. (Interesting. Tell me more about that.)",
        "¿Puedes explicar eso de otra manera? (Can you explain that another way?)",
        "No estoy seguro de entender. ¿Puedes decirlo de otra forma? (I'm not sure I understand. Can you say it differently?)",
        "¿Qué piensas sobre este tema? (What do you think about this topic?)",
        "¿Tienes alguna pregunta para mí? (Do you have any questions for me?)"
    ];
    
    return $default_responses[array_rand($default_responses)];
}

/**
 * Test if Ollama is available
 */
function testOllamaConnection() {
    $urls = ['http://localhost:11434/api/generate', 'http://127.0.0.1:11434/api/generate'];
    
    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'llama2',
            'prompt' => 'Hello',
            'stream' => false
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        
        curl_close($ch);
        
        if (!$errno) {
            return true;
        }
    }
    
    return false;
}