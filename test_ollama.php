<?php
// Simple script to test Ollama connection from PHP

echo "Testing Ollama connection...\n";

// Test with /api/tags endpoint
echo "Testing /api/tags endpoint...\n";
$test_url = 'http://127.0.0.1:11434/api/tags';
$ch = curl_init($test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_errno) {
    echo "ERROR: Could not connect to Ollama tags API: $curl_error\n";
} else {
    echo "SUCCESS: Connected to Ollama tags API. HTTP code: $http_code\n";
    echo "Response: $response\n\n";
}

// Test with /api/generate endpoint
echo "Testing /api/generate endpoint with llama2 model...\n";
$url = 'http://127.0.0.1:11434/api/generate';
$data = [
    'model' => 'llama2',
    'prompt' => 'Hello',
    'stream' => false
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_errno) {
    echo "ERROR: Could not connect to Ollama generate API: $curl_error\n";
} else {
    echo "SUCCESS: Connected to Ollama generate API. HTTP code: $http_code\n";
    $response_data = json_decode($response, true);
    if (isset($response_data['response'])) {
        echo "Response: " . $response_data['response'] . "\n";
    } else {
        echo "Unexpected response format: $response\n";
    }
}

echo "\nTest completed.\n";