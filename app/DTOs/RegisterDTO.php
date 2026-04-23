<?php

namespace App\DTOs;

class RegisterDTO
{
    public string $name;

    public string $email;

    public string $password;

    public string $password_confirmation;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->password_confirmation = $data['password_confirmation'];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ];
    }
}
