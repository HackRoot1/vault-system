<?php

namespace App\Helpers;

use App\Services\KeyDerivationService;
use Illuminate\Support\Facades\Cache;

class EncryptionHelper
{
    private static ?string $userKey = null;

    private static ?int $userId = null;

    private const CACHE_PREFIX = 'encryption_key:';

    /**
     * Set the encryption key for the current user
     */
    public static function setUserKey(int $userId, string $password, string $salt, int $iterations): void
    {
        self::$userId = $userId;
        self::$userKey = KeyDerivationService::deriveKey($password, $salt, $iterations);
    }

    /**
     * Store the derived key for a given token ID.
     */
    public static function setUserKeyForToken(int $tokenId, string $key): void
    {
        self::$userKey = $key;
        Cache::forever(self::CACHE_PREFIX.$tokenId, base64_encode($key));
    }

    /**
     * Get the encryption key for the current request.
     */
    public static function getUserKey(): ?string
    {
        if (self::$userKey) {
            return self::$userKey;
        }

        $tokenId = self::getCurrentTokenId();
        if (! $tokenId) {
            return null;
        }

        $cached = Cache::get(self::CACHE_PREFIX.$tokenId);
        if (! is_string($cached)) {
            return null;
        }

        $decoded = base64_decode($cached, true);

        return $decoded !== false ? $decoded : $cached;
    }

    /**
     * Get the user ID
     */
    public static function getUserId(): ?int
    {
        return self::$userId;
    }

    /**
     * Clear the current user key from memory and cache.
     */
    public static function clearUserKey(?int $tokenId = null): void
    {
        self::$userKey = null;
        self::$userId = null;

        if ($tokenId === null) {
            $tokenId = self::getCurrentTokenId();
        }

        if ($tokenId) {
            Cache::forget(self::CACHE_PREFIX.$tokenId);
        }
    }

    /**
     * Check if user key is set
     */
    public static function hasUserKey(): bool
    {
        return self::getUserKey() !== null;
    }

    /**
     * Parse the token ID from the current Bearer token.
     */
    private static function getCurrentTokenId(): ?int
    {
        $bearer = request()->bearerToken();
        if (! $bearer) {
            return null;
        }

        $parts = explode('|', $bearer, 2);
        if (count($parts) !== 2 || ! is_numeric($parts[0])) {
            return null;
        }

        return (int) $parts[0];
    }
}
