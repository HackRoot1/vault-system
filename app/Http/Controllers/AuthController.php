<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\EncryptionHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\KeyDerivationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $salt = KeyDerivationService::generateSalt();
        $iterations = KeyDerivationService::getDefaultIterations();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'encryption_salt' => $salt,
            'key_iterations' => $iterations,
        ]);

        $key = KeyDerivationService::deriveKey($request->password, $salt, $iterations);
        EncryptionHelper::setUserKey($user->id, $request->password, $salt, $iterations);

        $tokenResult = $user->createToken('API Token');
        EncryptionHelper::setUserKeyForToken($tokenResult->accessToken->id, $key);
        $token = $tokenResult->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        $user = Auth::user();

        $key = KeyDerivationService::deriveKey(
            $request->password,
            $user->encryption_salt,
            $user->key_iterations
        );

        EncryptionHelper::setUserKey(
            $user->id,
            $request->password,
            $user->encryption_salt,
            $user->key_iterations
        );

        $tokenResult = $user->createToken('API Token');
        EncryptionHelper::setUserKeyForToken($tokenResult->accessToken->id, $key);
        $token = $tokenResult->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $tokenId = $request->user()->currentAccessToken()?->id;
        EncryptionHelper::clearUserKey($tokenId);
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }
}