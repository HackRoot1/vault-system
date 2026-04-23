<?php

function post($url, $data)
{
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

        return null;
    }

    return $result;
}

$register = post('http://127.0.0.1:8001/api/register', '{"name":"John Doe","email":"john@example.com","password":"MySecure@Pass123","password_confirmation":"MySecure@Pass123"}');
echo "REGISTER RESPONSE:\n";
echo $register."\n\n";

$login = post('http://127.0.0.1:8001/api/login', '{"email":"john@example.com","password":"MySecure@Pass123"}');
echo "LOGIN RESPONSE:\n";
echo $login."\n";
