<?php

namespace App\Services;

use App\Helpers\EncryptionHelper;
use App\Models\FileItem;
use App\Repositories\FileRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FileService
{
    protected FileRepository $fileRepository;

    private const DOWNLOAD_TOKEN_CACHE_PREFIX = 'file_download_token:';

    private const DOWNLOAD_TOKEN_TTL_SECONDS = 600;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    protected function getEncryptionService(): EncryptionService
    {
        $key = EncryptionHelper::getUserKey();
        if (! $key) {
            throw new \Exception('Encryption key not available');
        }

        return new EncryptionService($key);
    }

    public function getVaultFiles(int $vaultId)
    {
        return $this->fileRepository->findByVault($vaultId);
    }

    public function getVaultFile(int $vaultId, int $fileId): ?FileItem
    {
        return $this->fileRepository->findVaultFile($vaultId, $fileId);
    }

    public function getUserVaultFile(int $userId, int $vaultId, int $fileId): ?FileItem
    {
        return $this->fileRepository->findUserVaultFile($userId, $vaultId, $fileId);
    }

    public function uploadFile(int $vaultId, UploadedFile $uploadedFile): FileItem
    {
        $rawContents = file_get_contents($uploadedFile->getRealPath());

        $encryption = $this->getEncryptionService();
        $encrypted = $encryption->encrypt($rawContents);

        $path = sprintf('vaults/%d/%s.enc', $vaultId, uniqid('', true));
        Storage::disk('local')->put($path, base64_decode($encrypted['encrypted_data']));

        $fileData = [
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
        ];

        return $this->fileRepository->createForVault($vaultId, $fileData);
    }

    public function updateFile(int $vaultId, int $fileId, UploadedFile $uploadedFile): bool
    {
        $existingFile = $this->getVaultFile($vaultId, $fileId);
        if (! $existingFile) {
            return false;
        }

        $rawContents = file_get_contents($uploadedFile->getRealPath());

        $encryption = $this->getEncryptionService();
        $encrypted = $encryption->encrypt($rawContents);

        $newPath = sprintf('vaults/%d/%s.enc', $vaultId, uniqid('', true));
        Storage::disk('local')->put($newPath, base64_decode($encrypted['encrypted_data']));
        Storage::disk('local')->delete($existingFile->file_path);

        $fileData = [
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $newPath,
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
        ];

        return $this->fileRepository->updateVaultFile($vaultId, $fileId, $fileData);
    }

    public function deleteFile(int $vaultId, int $fileId): bool
    {
        $file = $this->getVaultFile($vaultId, $fileId);
        if (! $file) {
            return false;
        }

        Storage::disk('local')->delete($file->file_path);

        return $this->fileRepository->deleteVaultFile($vaultId, $fileId);
    }

    public function generateDownloadUrl(int $userId, int $vaultId, int $fileId): ?array
    {
        $file = $this->getUserVaultFile($userId, $vaultId, $fileId);
        if (! $file) {
            return null;
        }

        $key = EncryptionHelper::getUserKey();
        if (! $key) {
            return null;
        }

        $token = bin2hex(random_bytes(16));
        $cacheKey = self::DOWNLOAD_TOKEN_CACHE_PREFIX.$token;

        Cache::put($cacheKey, [
            'file_item_id' => $file->id,
            'user_id' => $userId,
            'key' => base64_encode($key),
        ], self::DOWNLOAD_TOKEN_TTL_SECONDS);

        return [
            'download_url' => route('files.download', ['token' => $token]),
            'expires_at' => now()->addSeconds(self::DOWNLOAD_TOKEN_TTL_SECONDS)->toISOString(),
        ];
    }

    public function downloadFile(string $token)
    {
        $cacheKey = self::DOWNLOAD_TOKEN_CACHE_PREFIX.$token;
        $payload = Cache::get($cacheKey);

        if (! $payload || ! isset($payload['file_item_id'], $payload['key'], $payload['user_id'])) {
            return null;
        }

        $fileItem = $this->fileRepository->find($payload['file_item_id']);
        if (! $fileItem) {
            return null;
        }

        $vault = $fileItem->vault;
        if (! $vault || $vault->user_id !== $payload['user_id']) {
            return null;
        }

        if (! Storage::disk('local')->exists($fileItem->file_path)) {
            return null;
        }

        $encryptedContents = base64_encode(Storage::disk('local')->get($fileItem->file_path));
        $key = base64_decode($payload['key']);
        $encryption = new EncryptionService($key);
        $decrypted = $encryption->decrypt($encryptedContents, $fileItem->iv, $fileItem->tag);

        Cache::forget($cacheKey);

        return [
            'content' => $decrypted,
            'filename' => $fileItem->file_name,
        ];
    }
}
