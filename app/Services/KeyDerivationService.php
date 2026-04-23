<?php

namespace App\Services;

class KeyDerivationService
{
    private const ALGORITHM = 'sha256';
    private const ITERATIONS = 100000;
    private const KEY_LENGTH = 32; // 256 bits for AES-256

    /**
     * Generate a random salt
     */
    public static function generateSalt(): string
    {
        return bin2hex(random_bytes(16)); // 32 character hex string
    }

    /**
     * Derive encryption key from password using PBKDF2
     */
    public static function deriveKey(string $password, string $salt, int $iterations = self::ITERATIONS): string
    {
        return hash_pbkdf2(
            self::ALGORITHM,
            $password,
            $salt,
            $iterations,
            self::KEY_LENGTH,
            true // return binary string for AES key
        );
    }

    /**
     * Get default iterations count
     */
    public static function getDefaultIterations(): int
    {
        return self::ITERATIONS;
    }

    /**
     * Get key length
     */
    public static function getKeyLength(): int
    {
        return self::KEY_LENGTH;
    }
}