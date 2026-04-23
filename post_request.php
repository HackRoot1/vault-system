<?php
function post($url, $data) {
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $data,
            'ignore_errors' => true,
        ],
    ];
    $ctx = stream_context_create($opts);
    $result = file_get_contents($url, false, $ctx);
    if ($result === false) {
        echo "ERROR\n";
        var_dump($http_response_header);
    } else {
        echo $result;
    }
}

post('http://127.0.0.1:8001/api/login', '{"email":"john@example.com","password":"MySecure@Pass123"}');
