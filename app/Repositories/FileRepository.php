<?php

namespace App\Repositories;

use App\Models\FileItem;
use Illuminate\Database\Eloquent\Collection;

class FileRepository extends BaseRepository
{
    public function __construct(FileItem $fileItem)
    {
        parent::__construct($fileItem);
    }

    public function findByVault(int $vaultId): Collection
    {
        return $this->findBy(['vault_id' => $vaultId]);
    }

    public function findVaultFile(int $vaultId, int $fileId): ?FileItem
    {
        return $this->model->where('vault_id', $vaultId)
            ->where('id', $fileId)
            ->first();
    }

    public function findUserVaultFile(int $userId, int $vaultId, int $fileId): ?FileItem
    {
        return $this->model->join('vaults', 'file_items.vault_id', '=', 'vaults.id')
            ->where('vaults.user_id', $userId)
            ->where('file_items.vault_id', $vaultId)
            ->where('file_items.id', $fileId)
            ->select('file_items.*')
            ->first();
    }

    public function createForVault(int $vaultId, array $data): FileItem
    {
        $data['vault_id'] = $vaultId;

        return $this->create($data);
    }

    public function updateVaultFile(int $vaultId, int $fileId, array $data): bool
    {
        $file = $this->findVaultFile($vaultId, $fileId);
        if (! $file) {
            return false;
        }

        return $file->update($data);
    }

    public function deleteVaultFile(int $vaultId, int $fileId): bool
    {
        $file = $this->findVaultFile($vaultId, $fileId);
        if (! $file) {
            return false;
        }

        return $file->delete();
    }

    public function findRecentByUser(int $userId, int $limit = 5): Collection
    {
        return $this->model->join('vaults', 'file_items.vault_id', '=', 'vaults.id')
            ->where('vaults.user_id', $userId)
            ->orderBy('file_items.updated_at', 'desc')
            ->limit($limit)
            ->select('file_items.*')
            ->get();
    }
}
