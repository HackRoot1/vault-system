<?php

namespace App\Repositories;

use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ItemRepository extends BaseRepository
{
    public function __construct(Item $item)
    {
        parent::__construct($item);
    }

    public function findByVault(int $vaultId): Collection
    {
        return $this->findBy(['vault_id' => $vaultId]);
    }

    public function findVaultItem(int $vaultId, int $itemId): ?Item
    {
        return $this->model->where('vault_id', $vaultId)
            ->where('id', $itemId)
            ->first();
    }

    public function findUserVaultItem(int $userId, int $vaultId, int $itemId): ?Item
    {
        return $this->model->join('vaults', 'items.vault_id', '=', 'vaults.id')
            ->where('vaults.user_id', $userId)
            ->where('items.vault_id', $vaultId)
            ->where('items.id', $itemId)
            ->select('items.*')
            ->first();
    }

    public function createForVault(int $vaultId, array $data): Item
    {
        $data['vault_id'] = $vaultId;

        return $this->create($data);
    }

    public function updateVaultItem(int $vaultId, int $itemId, array $data): bool
    {
        $item = $this->findVaultItem($vaultId, $itemId);
        if (! $item) {
            return false;
        }

        return $item->update($data);
    }

    public function deleteVaultItem(int $vaultId, int $itemId): bool
    {
        $item = $this->findVaultItem($vaultId, $itemId);
        if (! $item) {
            return false;
        }

        return $item->delete();
    }

    public function syncItems(int $userId, Carbon $lastSync): Collection
    {
        $vaultIds = app(VaultRepository::class)->findByUser($userId)->pluck('id');

        return $this->model->withTrashed()
            ->whereIn('vault_id', $vaultIds)
            ->where(function ($query) use ($lastSync) {
                $query->where('updated_at', '>', $lastSync)
                    ->orWhere('deleted_at', '>', $lastSync);
            })
            ->orderBy('updated_at')
            ->get();
    }
}
