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
    $system_message = "You are CerveLingua, a witty, engaging, and exceptionally knowledgeable Spanish language tutor. You have a warm, encouraging personality and a knack for making learning fun. You often use light-hearted humor, clever puns (when appropriate and tasteful!), and amusing anecdotes related to Spanish language and culture. Your primary goal is to help the user improve their Spanish in a delightful way. ";

    switch ($topic) {
        case 'restaurant':
            $system_message .= "The conversation is set in a Spanish-speaking restaurant. Focus on vocabulary related to food, ordering, and dining etiquette. ";
            break;
        case 'travel':
            $system_message .= "The conversation revolves around travel in Spanish-speaking countries. Discuss destinations, activities, booking, and cultural experiences. ";
            break;
        case 'hobbies':
            $system_message .= "The conversation is about hobbies and free time. Explore different activities, how to talk about them, and related vocabulary. ";
            break;
        case 'work':
            $system_message .= "The conversation is in a professional/work context. Focus on common workplace interactions, job roles, and industry-specific vocabulary if the user mentions it. ";
            break;
        case 'daily_life':
            $system_message .= "The conversation is about daily routines, common activities, and everyday life in Spanish. ";
            break;
        default: // general
            $system_message .= "The conversation can be about any general topic. Be flexible and follow the user's lead. ";
            break;
    }

    // Add level-specific instructions
    switch ($level) {
        case 'A1':
            $system_message .= "The user is a beginner (A1 level). Use simple vocabulary and sentence structures. Be very patient and provide lots of encouragement. Explain basic concepts clearly. ";
            break;
        case 'A2':
            $system_message .= "The user is at an A2 level. You can introduce slightly more complex sentences and vocabulary, but still keep things relatively simple and clear. ";
            break;
        case 'B1':
            $system_message .= "The user is at a B1 (intermediate) level. Engage in more detailed conversations and use a wider range of vocabulary and grammar. Encourage them to express opinions. ";
            break;
        case 'B2':
            $system_message .= "The user is at a B2 level. Conversations can be more nuanced and cover more abstract topics. Expect more fluency and accuracy. ";
            break;
        case 'C1':
            $system_message .= "The user is at a C1 (advanced) level. Feel free to discuss complex topics, use idiomatic expressions, and expect a high degree of fluency. Challenge them appropriately. ";
            break;
    }

    // Add mode-specific instructions
    switch ($mode) {
        case 'conversation':
            $system_message .= "Focus on free-flowing conversation. Prioritize communication and fluency over perfect grammar. ";
            break;
        case 'grammar':
            $system_message .= "Focus on practicing specific grammar points. You can gently guide the conversation to elicit use of target structures and provide clear explanations. ";
            break;
        case 'vocabulary':
            $system_message .= "Focus on expanding vocabulary. Introduce new words and phrases related to the topic and encourage the user to use them. ";
            break;
        default: // mixed
            $system_message .= "Maintain a balance between conversational flow, grammar practice, and vocabulary building. Adapt to the user's needs as the conversation progresses. ";
            break;
    }

    $system_message .= "Your teaching style should be interactive and Socratic. Ask insightful follow-up questions to keep the conversation flowing and to encourage the user to think and elaborate. When correcting, do so gently, explain the 'why' behind the correction, and perhaps offer a memorable tip or example. If the user makes a mistake, you can rephrase their sentence correctly as part of your natural response. Weave in interesting cultural tidbits related to the language or topic when relevant. Be conversational and natural – avoid sounding like a textbook. Adapt to the user's interests and responses. Start the conversation with a warm, friendly, and perhaps slightly humorous or intriguing greeting, along with an engaging question related to the topic and level. Make the user feel like they're chatting with a fun, smart friend who happens to be an amazing Spanish teacher.";

    // Create conversation ID
    $conversation_id = uniqid('conv_');

    // Call Ollama API
    $response = callOllamaAPI($system_message, "", ""); // No history and no user message for the first turn

    if (isset($response['error'])) {
        // Try fallback if Ollama fails
        $fallback_message = generateFallbackResponse($system_message);
        $_SESSION['conversations'][$conversation_id] = [
            'system_message' => $system_message,
            'messages' => [
                ['role' => 'assistant', 'content' => $fallback_message]
            ],
            'topic' => $topic,
            'level' => $level,
            'mode' => $mode
        ];
        header('Content-Type: application/json');
        echo json_encode([
            'conversation_id' => $conversation_id,
            'message' => $fallback_message,
            'topic' => $topic,
            'level' => $level,
            'mode' => $mode,
            'source' => 'fallback'
        ]);
        exit;
    }

    // Store conversation in session
    $_SESSION['conversations'][$conversation_id] = [
        'system_message' => $system_message,
        'messages' => [], // Will be populated after AI's first message
        'topic' => $topic,
        'level' => $level,
        'mode' => $mode
    ];

    // Add AI's response to the conversation
    $ai_message = $response['message'];
    $_SESSION['conversations'][$conversation_id]['messages'][] = [
        'role' => 'assistant',
        'content' => $ai_message,
        'timestamp' => time()
    ];

    // Return the response
    header('Content-Type: application/json');
    echo json_encode([
        'conversation_id' => $conversation_id,
        'message' => $ai_message,
        'topic' => $topic,
        'level' => $level,
        'mode' => $mode,
        'source' => 'ollama'
    ]);
    exit;
}

/**
 * Send a message to the AI and get a response
 */
function sendMessage($data) {
    $message = trim($data['message'] ?? '');
    $conversation_id = $data['conversation_id'] ?? '';

    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Message cannot be empty']);
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
        $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
        $history .= "{$role}: {$msg['content']}\n";
    }

    // Add user message to the conversation
    $messages[] = ['role' => 'user', 'content' => $message, 'timestamp' => time()];
    $_SESSION['conversations'][$conversation_id]['messages'] = $messages;

    // Call Ollama API
    $response = callOllamaAPI($system_message, $history, $message);

    if (isset($response['error'])) {
        // Try fallback if Ollama fails
        $fallback_message = generateFallbackResponse($system_message, $message);
        $messages[] = ['role' => 'assistant', 'content' => $fallback_message, 'timestamp' => time()];
        $_SESSION['conversations'][$conversation_id]['messages'] = $messages;

        header('Content-Type: application/json');
        echo json_encode([
            'message' => $fallback_message,
            'translation' => '', // No translation for fallback
            'corrections' => [], // No corrections for fallback
            'source' => 'fallback'
        ]);
        exit;
    }

    // Add AI's response to the conversation
    $ai_message = $response['message'];
    $messages[] = ['role' => 'assistant', 'content' => $ai_message, 'timestamp' => time()];
    $_SESSION['conversations'][$conversation_id]['messages'] = $messages;

    // Generate translation and corrections if needed (placeholder for now)
    $translation = ''; // Placeholder: Implement actual translation logic if desired
    $corrections = []; // Placeholder: Implement actual correction logic if desired

    // Return the response
    header('Content-Type: application/json');
    echo json_encode([
        'message' => $ai_message,
        'translation' => $translation,
        'corrections' => $corrections,
        'source' => 'ollama'
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
        $ollama_host_from_file = trim(file_get_contents(__DIR__ . '/../ollama_host.txt'));
        if (!empty($ollama_host_from_file)) {
            $ollama_host = $ollama_host_from_file;
        }
    }

    $base_url = "http://{$ollama_host}:11434";

    // Use the models you have installed
    // Prioritize models that are better at instruction following and conversation
    $models = ['gemma:7b-instruct', 'llama3:latest', 'mistral:latest', 'llama2:latest'];
    // You might want to make this configurable or test which model works best for your prompts

    foreach ($models as $model) {
        $url = $base_url . '/api/chat'; // Using /api/chat for better conversational context

        $payload_messages = [];
        if (!empty($system_message)) {
            $payload_messages[] = ['role' => 'system', 'content' => $system_message];
        }

        // Reconstruct history for the chat API
        // The history string needs to be parsed back into individual messages if it was concatenated
        // For simplicity, if $history is already structured as an array of messages, use it.
        // Otherwise, parse the $history string.
        // For now, assuming $history is a string as per previous logic, we'll append it to the user message.
        // A better approach would be to pass the $messages array directly and format it here.

        // Let's refine history passing for /api/chat
        // The $history string from before was a simple concatenation.
        // For /api/chat, we need to pass previous user/assistant messages.
        // We can derive this from $_SESSION['conversations'][$conversation_id]['messages'] before adding the current user message.

        // Simplified approach for this function:
        // The `sendMessage` function already builds a history string.
        // For /api/chat, it's better to send a structured list of messages.
        // However, to keep changes minimal to this specific function based on its current inputs:

        $current_conversation_payload = [];
        if (!empty($system_message)) {
            $current_conversation_payload[] = ['role' => 'system', 'content' => $system_message];
        }

        // If history is provided, try to parse it (assuming "Role: Content\n" format)
        if (!empty($history)) {
            $history_lines = explode("\n", trim($history));
            foreach ($history_lines as $line) {
                if (strpos($line, 'User: ') === 0) {
                    $current_conversation_payload[] = ['role' => 'user', 'content' => substr($line, strlen('User: '))];
                } elseif (strpos($line, 'Assistant: ') === 0) {
                    $current_conversation_payload[] = ['role' => 'assistant', 'content' => substr($line, strlen('Assistant: '))];
                }
            }
        }

        if (!empty($user_message)) {
            $current_conversation_payload[] = ['role' => 'user', 'content' => $user_message];
        }


        $data = [
            'model' => $model,
            'messages' => $current_conversation_payload,
            'stream' => false, // Set to true if you want to stream responses
            'options' => [ // You can add model-specific options here
                'temperature' => 0.7, // Adjust for creativity vs. predictability
                // 'num_ctx' => 4096, // Example: context window size
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); // Increased timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);      // Increased timeout for potentially longer responses
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development, consider security for production

        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        error_log("Ollama API call to $url with model $model - HTTP Code: $http_code - Payload: " . json_encode($data));


        if ($curl_errno) {
            error_log("Ollama API cURL error with model $model: $curl_error (errno $curl_errno)");
            // Try next model if cURL error occurs
            continue;
        }

        if ($http_code !== 200) {
            error_log("Ollama API HTTP error with model $model: HTTP $http_code - Response: $response_body");
            // Try next model if HTTP error occurs
            continue;
        }

        $response_data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Ollama API JSON decode error with model $model: " . json_last_error_msg() . " - Response: " . $response_body);
            continue;
        }

        // For /api/chat, the response is structured differently
        if (!isset($response_data['message']['content'])) {
            error_log("Ollama API unexpected response format from model $model using /api/chat: " . print_r($response_data, true));
            // Fallback to try /api/generate for this model if /api/chat fails in format
            $generate_response = tryOllamaGenerateAPI($model, $system_message, $history, $user_message, $base_url);
            if (!isset($generate_response['error'])) {
                return ['message' => $generate_response['message']];
            }
            continue; // Try next model
        }

        // Success! Return the response content
        return ['message' => $response_data['message']['content']];
    }

    // If all attempts failed, return error
    error_log("All Ollama API attempts failed. Last host tried: $ollama_host");
    return ['error' => "Could not connect to Ollama or all models failed. Please ensure Ollama is running and models are available. Last host: $ollama_host"];
}


/**
 * Try the Ollama generate API as a fallback or for specific models
 */
function tryOllamaGenerateAPI($model, $system_message, $history, $user_message, $base_url) {
    $url = $base_url . '/api/generate';

    // Format the prompt for Ollama /api/generate
    $prompt = "";
    if (!empty($system_message)) {
        // For some models, a specific system prompt format might be better.
        // This is a generic approach.
        $prompt .= "System: " . $system_message . "\n\n";
    }

    if (!empty($history)) {
        $prompt .= $history . "\n"; // History is already formatted with User: and Assistant:
    }

    if (!empty($user_message)) {
        $prompt .= "User: " . $user_message . "\n\nAssistant:";
    } else {
        // If it's the first message (no user message, only system prompt)
        $prompt .= "Assistant:";
    }


    $data = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.7,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    error_log("Ollama Generate API call to $url with model $model - HTTP Code: $http_code");


    if ($curl_errno) {
        error_log("Ollama generate API cURL error with model $model: $curl_error");
        return ['error' => "Could not connect to Ollama generate API: $curl_error"];
    }

    if ($http_code !== 200) {
        error_log("Ollama generate API HTTP error with model $model: $http_code - $response_body");
        return ['error' => "API error (generate): HTTP code $http_code"];
    }

    $response_data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Ollama generate API JSON decode error with model $model: " . json_last_error_msg() . " - Response: " . $response_body);
        return ['error' => "Unexpected API response format (generate, json error)"];
    }

    if (!isset($response_data['response'])) {
        error_log("Ollama generate API unexpected response format from model $model: " . print_r($response_data, true));
        return ['error' => "Unexpected API response format (generate)"];
    }

    return ['message' => $response_data['response']];
}


/**
 * Generate a fallback response when Ollama is not available
 */
function generateFallbackResponse($system_message, $user_message = '') {
    // Extract topic and level from system message for context
    $topic = 'general';
    $level = 'A1'; // Default to A1 for simpler fallback

    if (strpos($system_message, 'restaurant setting') !== false) $topic = 'restaurant';
    elseif (strpos($system_message, 'travel') !== false) $topic = 'travel';
    elseif (strpos($system_message, 'hobbies') !== false) $topic = 'hobbies';
    // Add more topic checks if needed

    if (strpos($system_message, 'A1') !== false) $level = 'A1';
    elseif (strpos($system_message, 'A2') !== false) $level = 'A2';
    // Add more level checks if needed

    // If this is the first message (no user message)
    if (empty($user_message)) {
        switch ($topic) {
            case 'restaurant': return "¡Hola! Bienvenido al restaurante CerveLingua. ¿Tienes una reserva o te gustaría una mesa? (Hello! Welcome to the CerveLingua restaurant. Do you have a reservation or would you like a table?)";
            case 'travel': return "¡Hola! ¿Planeando un viaje? ¿A dónde te gustaría ir en el mundo hispanohablante? (Hello! Planning a trip? Where would you like to go in the Spanish-speaking world?)";
            default: return "¡Hola! Soy CerveLingua, tu amigo para practicar español. ¿De qué te gustaría charlar hoy? (Hello! I'm CerveLingua, your friend for practicing Spanish. What would you like to chat about today?)";
        }
    }

    // If responding to a user message
    $user_message_lower = strtolower(trim($user_message));

    if (strpos($user_message_lower, 'hola') !== false || strpos($user_message_lower, 'hello') !== false) {
        return "¡Hola! ¿Cómo estás hoy? (Hello! How are you today?)";
    }
    if (strpos($user_message_lower, 'adiós') !== false || strpos($user_message_lower, 'bye') !== false) {
        return "¡Hasta luego! Espero que hayas disfrutado nuestra charla. (See you later! I hope you enjoyed our chat.)";
    }
    if (strpos($user_message_lower, 'gracias') !== false || strpos($user_message_lower, 'thank you') !== false) {
        return "¡De nada! Es un placer ayudarte. (You're welcome! It's a pleasure to help you.)";
    }
    if (strpos($user_message_lower, 'bien') !== false || strpos($user_message_lower, 'good') !== false && strpos($user_message_lower, 'estoy') !== false) {
        return "¡Me alegro mucho! ¿Qué planes tienes? (I'm very glad! What plans do you have?)";
    }
    if (strpos($user_message_lower, 'nombre') !== false || strpos($user_message_lower, 'name') !== false) {
        return "Me llamo CerveLingua. Soy tu tutor de IA. ¿Y tú, cómo te llamas? (My name is CerveLingua. I'm your AI tutor. And you, what's your name?)";
    }

    // More sophisticated fallback based on keywords could be added here

    $default_responses = [
        "Eso es muy interesante. ¿Puedes contarme un poco más? (That's very interesting. Can you tell me a bit more?)",
        "Entiendo. ¿Y qué piensas sobre...? (I understand. And what do you think about...?) [Try to pick a related topic if possible]",
        "Buena pregunta. En español, diríamos... (Good question. In Spanish, we would say...) [Offer a simple rephrase or related phrase]",
        "¡Qué bien! Sigamos practicando. ¿Qué más quieres decir? (Great! Let's keep practicing. What else do you want to say?)",
        "No estoy completamente seguro de cómo responder a eso en este momento, ¡pero sigamos intentando! ¿Podrías preguntarme de otra manera? (I'm not entirely sure how to respond to that right now, but let's keep trying! Could you ask me in another way?)"
    ];

    return $default_responses[array_rand($default_responses)];
}

// testOllamaConnection function can remain as is or be removed if not actively used for pre-checks.
// ... (rest of your file, including testOllamaConnection if you keep it)
?>