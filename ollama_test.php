<?php
// Simple browser-accessible test for Ollama connection

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Ollama Connection Test</h1>";

// OrbStack provides a special hostname to access the host machine
$host_addresses = [
    '127.0.0.1', // Standard localhost (won't work from Docker)
    'host.docker.internal', // Docker for Mac standard hostname
    'host.orbstack.local', // OrbStack specific hostname
    'host.lima.internal', // Lima specific hostname
];

foreach ($host_addresses as $host) {
    echo "<h2>Testing with curl to $host</h2>";
    $url = "http://$host:11434/api/tags";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $info = curl_getinfo($ch);

    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);

    curl_close($ch);

    if ($curl_errno) {
        echo "<p style='color:red'>ERROR: Could not connect to Ollama API at $host: $curl_error</p>";
        echo "<pre>Curl info: " . print_r($info, true) . "</pre>";
        echo "<pre>Verbose log: " . htmlspecialchars($verboseLog) . "</pre>";
    } else {
        echo "<p style='color:green'>SUCCESS: Connected to Ollama API at $host. HTTP code: $http_code</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // If we found a working host, save it for future reference
        echo "<p><strong>Working host found: $host</strong></p>";
        file_put_contents(__DIR__ . '/ollama_host.txt', $host);
        break;
    }
}

echo "<h2>PHP Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>User: " . exec('whoami') . "</p>";
echo "<p>Current Directory: " . getcwd() . "</p>";
echo "<p>Is Docker Environment: " . (file_exists('/.dockerenv') ? 'Yes' : 'No') . "</p>";
?>