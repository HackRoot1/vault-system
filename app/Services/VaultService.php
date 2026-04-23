<?php

namespace App\Services;

use App\Models\Vault;
use App\Repositories\VaultRepository;
use Illuminate\Database\Eloquent\Collection;

class VaultService
{
    protected VaultRepository $vaultRepository;

    public function __construct(VaultRepository $vaultRepository)
    {
        $this->vaultRepository = $vaultRepository;
    }

    public function getUserVaults(int $userId): Collection
    {
        return $this->vaultRepository->findByUser($userId);
    }

    public function getUserVault(int $userId, int $vaultId): ?Vault
    {
        return $this->vaultRepository->findUserVault($userId, $vaultId);
    }

    public function createVault(int $userId, array $data): Vault
    {
        return $this->vaultRepository->createForUser($userId, $data);
    }

    public function updateVault(int $userId, int $vaultId, array $data): bool
    {
        return $this->vaultRepository->updateUserVault($userId, $vaultId, $data);
    }

    public function deleteVault(int $userId, int $vaultId): bool
    {
        return $this->vaultRepository->deleteUserVault($userId, $vaultId);
    }

    public function authorizeVaultAccess(int $userId, int $vaultId): bool
    {
        $vault = $this->getUserVault($userId, $vaultId);

        return $vault !== null;
    }
}
