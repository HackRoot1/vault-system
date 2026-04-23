<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\EncryptionHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\DeviceTrackingService;
use App\Services\KeyDerivationService;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;
    protected DeviceTrackingService $deviceTrackingService;

    public function __construct(
        TwoFactorAuthService $twoFactorService,
        DeviceTrackingService $deviceTrackingService
    ) {
        $this->twoFactorService = $twoFactorService;
        $this->deviceTrackingService = $deviceTrackingService;
    }

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

        // Track device on registration
        $this->deviceTrackingService->trackLogin($user, $request);

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

        // Check if 2FA is enabled
        if ($this->twoFactorService->isEnabled($user)) {
            // If 2FA code is not provided, return challenge
            if (!$request->has('two_factor_code')) {
                return ApiResponse::success([
                    'requires_2fa' => true,
                    'user_id' => $user->id,
                ], 'Two-factor authentication required');
            }

            // Verify 2FA code
            if (!$this->twoFactorService->verifyCode($user->two_factor_secret, $request->two_factor_code)) {
                return ApiResponse::error('Invalid two-factor authentication code', 401);
            }
        }

        // Check for suspicious login
        if ($this->deviceTrackingService->isSuspiciousLogin($user, $request)) {
            // You might want to send an email notification here
            // For now, we'll just log it and continue
        }

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

        // Track device login
        $this->deviceTrackingService->trackLogin($user, $request);

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

    public function setup2FA(Request $request)
    {
        $user = $request->user();

        if ($this->twoFactorService->isEnabled($user)) {
            return ApiResponse::error('Two-factor authentication is already enabled', 400);
        }

        $secret = $this->twoFactorService->generateSecret();
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl($user);

        // Temporarily store the secret (you might want to use session or cache)
        $user->update(['two_factor_secret' => $secret]);

        return ApiResponse::success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ], 'Scan the QR code with Google Authenticator and use the generated code to verify');
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return ApiResponse::error('Two-factor authentication setup not initiated', 400);
        }

        if ($this->twoFactorService->verifyCode($user->two_factor_secret, $request->code)) {
            $this->twoFactorService->enable2FA($user, $user->two_factor_secret);
            return ApiResponse::success(null, 'Two-factor authentication enabled successfully');
        }

        return ApiResponse::error('Invalid verification code', 400);
    }

    public function disable2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$this->twoFactorService->isEnabled($user)) {
            return ApiResponse::error('Two-factor authentication is not enabled', 400);
        }

        if ($this->twoFactorService->verifyCode($user->two_factor_secret, $request->code)) {
            $this->twoFactorService->disable2FA($user);
            return ApiResponse::success(null, 'Two-factor authentication disabled successfully');
        }

        return ApiResponse::error('Invalid verification code', 400);
    }

    public function getDeviceHistory(Request $request)
    {
        $user = $request->user();
        $history = $this->deviceTrackingService->getDeviceHistory($user);
        $lastLogin = $this->deviceTrackingService->getLastLoginInfo($user);

        return ApiResponse::success([
            'device_history' => $history,
            'last_login' => $lastLogin,
        ], 'Device history retrieved successfully');
    }
}