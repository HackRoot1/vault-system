<?php

namespace App\Repositories;

use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;

class VaultRepository extends BaseRepository
{
    public function __construct(Vault $vault)
    {
        parent::__construct($vault);
    }

    public function findByUser(int $userId): Collection
    {
        return $this->findBy(['user_id' => $userId]);
    }

    public function findUserVault(int $userId, int $vaultId): ?Vault
    {
        return $this->model->where('user_id', $userId)
            ->where('id', $vaultId)
            ->first();
    }

    public function createForUser(int $userId, array $data): Vault
    {
        $data['user_id'] = $userId;

        return $this->create($data);
    }

    public function updateUserVault(int $userId, int $vaultId, array $data): bool
    {
        $vault = $this->findUserVault($userId, $vaultId);
        if (! $vault) {
            return false;
        }

        return $vault->update($data);
    }

    public function deleteUserVault(int $userId, int $vaultId): bool
    {
        $vault = $this->findUserVault($userId, $vaultId);
        if (! $vault) {
            return false;
        }

        return $vault->delete();
    }
}
