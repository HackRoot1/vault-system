<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    private const CIPHER = 'aes-256-gcm';
    private const KEY_LENGTH = 32; // 256 bits

    /**
     * Get the encryption key.
     * In production, this should be a per-user or per-vault key.
     * For demo purposes, using a derived key from app key.
     */
    private static function getKey(): string
    {
        $key = config('app.key');
        // Derive a 32-byte key from the app key
        return substr(hash('sha256', $key), 0, self::KEY_LENGTH);
    }

    /**
     * Encrypt data using AES-256-GCM
     */
    public static function encrypt(string $data): array
    {
        $key = self::getKey();
        $iv = random_bytes(12); // 96 bits for GCM
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // tag length
        );

        if ($encrypted === false) {
            throw new \Exception('Encryption failed');
        }

        return [
            'encrypted_data' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
        ];
    }

    /**
     * Decrypt data using AES-256-GCM
     */
    public static function decrypt(string $encryptedData, string $iv, string $tag): string
    {
        $key = self::getKey();

        $decrypted = openssl_decrypt(
            base64_decode($encryptedData),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            base64_decode($iv),
            base64_decode($tag)
        );

        if ($decrypted === false) {
            throw new \Exception('Decryption failed');
        }

        return $decrypted;
    }
}