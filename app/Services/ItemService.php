<?php

namespace App\Services;

use App\Helpers\EncryptionHelper;
use App\Models\Item;
use App\Repositories\ItemRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ItemService
{
    protected ItemRepository $itemRepository;

    protected EncryptionService $encryptionService;

    public function __construct(ItemRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    protected function getEncryptionService(): EncryptionService
    {
        $key = EncryptionHelper::getUserKey();
        if (! $key) {
            throw new \Exception('Encryption key not available');
        }

        return new EncryptionService($key);
    }

    public function getVaultItems(int $vaultId): Collection
    {
        return $this->itemRepository->findByVault($vaultId);
    }

    public function getVaultItem(int $vaultId, int $itemId): ?Item
    {
        return $this->itemRepository->findVaultItem($vaultId, $itemId);
    }

    public function getUserVaultItem(int $userId, int $vaultId, int $itemId): ?Item
    {
        return $this->itemRepository->findUserVaultItem($userId, $vaultId, $itemId);
    }

    public function createItem(int $vaultId, array $data): Item
    {
        $itemData = [
            'type' => $data['type'],
            'encrypted_data' => $data['encrypted_data'],
            'iv' => $data['iv'],
            'tag' => $data['tag'],
        ];

        return $this->itemRepository->createForVault($vaultId, $itemData);
    }

    public function updateItem(int $vaultId, int $itemId, array $data): bool
    {
        $itemData = [
            'type' => $data['type'],
            'encrypted_data' => $data['encrypted_data'],
            'iv' => $data['iv'],
            'tag' => $data['tag'],
        ];

        return $this->itemRepository->updateVaultItem($vaultId, $itemId, $itemData);
    }

    public function deleteItem(int $vaultId, int $itemId): bool
    {
        return $this->itemRepository->deleteVaultItem($vaultId, $itemId);
    }

    public function getRecentItems(int $userId, int $limit = 5): Collection
    {
        return $this->itemRepository->findRecentByUser($userId, $limit);
    }

    public function syncItems(int $userId, string $lastSync): Collection
    {
        try {
            $lastSync = Carbon::parse($lastSync);
        } catch (\Exception $e) {
            throw new \Exception('Invalid last_sync timestamp');
        }

        return $this->itemRepository->syncItems($userId, $lastSync);
    }

    public function decryptItemData(Item $item): array
    {
        $encryption = $this->getEncryptionService();
        $decryptedData = $encryption->decrypt(
            $item->encrypted_data,
            $item->iv,
            $item->tag
        );

        return json_decode($decryptedData, true);
    }
}
