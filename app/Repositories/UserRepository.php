<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function createWithEncryption(array $data): User
    {
        return $this->create($data);
    }

    public function updateDeviceHistory(int $userId, array $history): bool
    {
        return $this->update($userId, ['device_history' => $history]);
    }

    public function updateLastLogin(int $userId, array $loginData): bool
    {
        return $this->update($userId, [
            'last_login_at' => $loginData['timestamp'],
            'last_login_ip' => $loginData['ip'],
            'last_login_user_agent' => $loginData['user_agent'],
        ]);
    }
}
