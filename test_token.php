<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

$user = User::firstOrCreate(
    ['email' => 'token-test@example.com'],
    [
        'name' => 'Token Test',
        'password' => Hash::make('MySecure@Pass123'),
        'encryption_salt' => '0123456789abcdef0123456789abcdef',
        'key_iterations' => 100000,
    ]
);
$token = $user->createToken('Test Token');
echo json_encode(['id' => $token->accessToken->id, 'token' => $token->plainTextToken])."\n";
