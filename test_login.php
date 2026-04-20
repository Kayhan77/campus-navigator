<?php
// Test the login endpoint

$url = 'http://127.0.0.1:8000/api/v1/login';
$data = json_encode([
    'email' => 'test@example.com',
    'password' => 'password123'
]);

$options = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nContent-Length: " . strlen($data) . "\r\n",
        'content' => $data,
        'timeout' => 10
    ]
]);

try {
    $response = file_get_contents($url, false, $options);
    echo "✅ Status: Success\n";
    echo "Response:\n";
    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
