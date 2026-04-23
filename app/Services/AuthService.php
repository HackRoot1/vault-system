<?php

namespace App\Services;

use App\Helpers\EncryptionHelper;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    protected UserRepository $userRepository;

    protected KeyDerivationService $keyDerivationService;

    protected DeviceTrackingService $deviceTrackingService;

    public function __construct(
        UserRepository $userRepository,
        KeyDerivationService $keyDerivationService,
        DeviceTrackingService $deviceTrackingService
    ) {
        $this->userRepository = $userRepository;
        $this->keyDerivationService = $keyDerivationService;
        $this->deviceTrackingService = $deviceTrackingService;
    }

    protected function twoFactorService(): TwoFactorAuthService
    {
        return app(TwoFactorAuthService::class);
    }

    public function register(array $data): array
    {
        $salt = $this->keyDerivationService->generateSalt();
        $iterations = $this->keyDerivationService->getDefaultIterations();

        $user = $this->userRepository->createWithEncryption([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'encryption_salt' => $salt,
            'key_iterations' => $iterations,
        ]);

        $key = $this->keyDerivationService->deriveKey($data['password'], $salt, $iterations);
        EncryptionHelper::setUserKey($user->id, $data['password'], $salt, $iterations);

        $tokenResult = $user->createToken('API Token');
        EncryptionHelper::setUserKeyForToken($tokenResult->accessToken->id, $key);
        $token = $tokenResult->plainTextToken;

        // Track device on registration
        $this->deviceTrackingService->trackLogin($user, request());

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $data, Request $request): array
    {
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            return ['error' => 'Invalid credentials', 'code' => 401];
        }

        $user = Auth::user();

        // Check if 2FA is enabled
        if ($this->twoFactorService()->isEnabled($user)) {
            if (! isset($data['two_factor_code'])) {
                return ['requires_2fa' => true, 'user_id' => $user->id];
            }

            if (! $this->twoFactorService()->verifyCode($user->two_factor_secret, $data['two_factor_code'])) {
                return ['error' => 'Invalid two-factor authentication code', 'code' => 401];
            }
        }

        // Check for suspicious login
        if ($this->deviceTrackingService->isSuspiciousLogin($user, $request)) {
            // You might want to send an email notification here
        }

        $key = $this->keyDerivationService->deriveKey(
            $data['password'],
            $user->encryption_salt,
            $user->key_iterations
        );

        EncryptionHelper::setUserKey(
            $user->id,
            $data['password'],
            $user->encryption_salt,
            $user->key_iterations
        );

        $tokenResult = $user->createToken('API Token');
        EncryptionHelper::setUserKeyForToken($tokenResult->accessToken->id, $key);
        $token = $tokenResult->plainTextToken;

        // Track device login
        $this->deviceTrackingService->trackLogin($user, $request);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): bool
    {
        $tokenId = $user->currentAccessToken()?->id;
        EncryptionHelper::clearUserKey($tokenId);
        $user->currentAccessToken()->delete();

        return true;
    }

    public function setup2FA(User $user): array
    {
        if ($this->twoFactorService()->isEnabled($user)) {
            return ['error' => 'Two-factor authentication is already enabled', 'code' => 400];
        }

        $secret = $this->twoFactorService()->generateSecret();
        $qrCodeUrl = $this->twoFactorService()->getQRCodeUrl($user);

        // Temporarily store the secret
        $user->update(['two_factor_secret' => $secret]);

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ];
    }

    public function verify2FA(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        if ($this->twoFactorService()->verifyCode($user->two_factor_secret, $code)) {
            $this->twoFactorService()->enable2FA($user, $user->two_factor_secret);

            return true;
        }

        return false;
    }

    public function disable2FA(User $user, string $code): bool
    {
        if (! $this->twoFactorService()->isEnabled($user)) {
            return false;
        }

        if ($this->twoFactorService()->verifyCode($user->two_factor_secret, $code)) {
            $this->twoFactorService()->disable2FA($user);

            return true;
        }

        return false;
    }

    public function getDeviceHistory(User $user): array
    {
        $history = $this->deviceTrackingService->getDeviceHistory($user);
        $lastLogin = $this->deviceTrackingService->getLastLoginInfo($user);

        return [
            'device_history' => $history,
            'last_login' => $lastLogin,
        ];
    }
}
