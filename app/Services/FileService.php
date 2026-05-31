<?php

namespace App\Services;

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

    public function uploadFile(int $vaultId, UploadedFile $uploadedFile, string $fileName, string $iv, string $tag): FileItem
    {
        $path = sprintf('vaults/%d/%s.enc', $vaultId, uniqid('', true));
        Storage::disk('local')->put($path, file_get_contents($uploadedFile->getRealPath()));

        $fileData = [
            'file_name' => $fileName,
            'file_path' => $path,
            'iv' => $iv,
            'tag' => $tag,
        ];

        return $this->fileRepository->createForVault($vaultId, $fileData);
    }

    public function updateFile(int $vaultId, int $fileId, UploadedFile $uploadedFile, string $fileName, string $iv, string $tag): bool
    {
        $existingFile = $this->getVaultFile($vaultId, $fileId);
        if (! $existingFile) {
            return false;
        }

        $newPath = sprintf('vaults/%d/%s.enc', $vaultId, uniqid('', true));
        Storage::disk('local')->put($newPath, file_get_contents($uploadedFile->getRealPath()));
        Storage::disk('local')->delete($existingFile->file_path);

        $fileData = [
            'file_name' => $fileName,
            'file_path' => $newPath,
            'iv' => $iv,
            'tag' => $tag,
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

    public function getRecentFiles(int $userId, int $limit = 5)
    {
        return $this->fileRepository->findRecentByUser($userId, $limit);
    }

    public function generateDownloadUrl(int $userId, int $vaultId, int $fileId): ?array
    {
        $file = $this->getUserVaultFile($userId, $vaultId, $fileId);
        if (! $file) {
            return null;
        }

        $token = bin2hex(random_bytes(16));
        $cacheKey = self::DOWNLOAD_TOKEN_CACHE_PREFIX.$token;

        Cache::put($cacheKey, [
            'file_item_id' => $file->id,
            'user_id' => $userId,
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

        if (! $payload || ! isset($payload['file_item_id'], $payload['user_id'])) {
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

        $encryptedContents = Storage::disk('local')->get($fileItem->file_path);

        Cache::forget($cacheKey);

        return [
            'content' => $encryptedContents,
            'filename' => $fileItem->file_name,
            'iv' => $fileItem->iv,
            'tag' => $fileItem->tag,
        ];
    }
}
