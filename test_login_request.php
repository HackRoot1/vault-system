<?php
$payload = json_encode([
    'email' => 'john@example.com',
    'password' => 'MySecure@Pass123',
]);
$opts = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $payload,
        'ignore_errors' => true,
    ],
];
$context = stream_context_create($opts);
$result = file_get_contents('http://127.0.0.1:8001/api/login', false, $context);
if ($result === false) {
    echo "ERROR\n";
    var_dump($http_response_header);
} else {
    echo $result;
}
