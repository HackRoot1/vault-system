<?php

namespace App\DTOs;

class LoginDTO
{
    public string $email;

    public string $password;

    public ?string $two_factor_code;

    public function __construct(array $data)
    {
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->two_factor_code = $data['two_factor_code'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'two_factor_code' => $this->two_factor_code,
        ];
    }

    public function hasTwoFactorCode(): bool
    {
        return ! empty($this->two_factor_code);
    }
}
