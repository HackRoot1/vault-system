<?php

namespace App\Services;

class EncryptionService
{
    private const CIPHER = 'aes-256-gcm';

    private string $key;

    /**
     * Constructor - initialize with encryption key
     */
    public function __construct(string $key)
    {
        if (strlen($key) !== 32) {
            throw new \Exception('Encryption key must be exactly 32 bytes');
        }
        $this->key = $key;
    }

    /**
     * Encrypt data using AES-256-GCM
     */
    public function encrypt(string $data): array
    {
        $iv = random_bytes(12); // 96 bits for GCM
        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            $this->key,
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
    public function decrypt(string $encryptedData, string $iv, string $tag): string
    {
        $decrypted = openssl_decrypt(
            base64_decode($encryptedData),
            self::CIPHER,
            $this->key,
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
