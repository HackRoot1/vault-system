<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA;
    }

    /**
     * Generate a new 2FA secret for a user
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Get the QR code URL for Google Authenticator
     */
    public function getQRCodeUrl(User $user): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'Vault System'),
            $user->email,
            $user->two_factor_secret
        );
    }

    /**
     * Verify a 2FA code
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Enable 2FA for a user
     */
    public function enable2FA(User $user, string $secret): bool
    {
        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
        ]);

        return true;
    }

    /**
     * Disable 2FA for a user
     */
    public function disable2FA(User $user): bool
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
        ]);

        return true;
    }

    /**
     * Check if user has 2FA enabled
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_enabled && ! empty($user->two_factor_secret);
    }
}
