<?php

namespace App\DTOs;

class RegisterDTO
{
    public string $name;

    public string $email;

    public string $password;

    public string $master_password;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->password = $data['password'];
        $this->master_password = $data['master_password'];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'master_password' => $this->master_password,
        ];
    }
}
