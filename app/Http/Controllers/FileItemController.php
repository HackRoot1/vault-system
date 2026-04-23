<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\EncryptionHelper;
use App\Http\Requests\FileItemRequest;
use App\Http\Resources\FileItemResource;
use App\Models\FileItem;
use App\Models\Vault;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FileItemController extends Controller
{
    private const DOWNLOAD_TOKEN_CACHE_PREFIX = 'file_download_token:';
    private const DOWNLOAD_TOKEN_TTL_SECONDS = 600;

    /**
     * Get encryption service with user's derived key.
     */
    private function getEncryptionService(): EncryptionService
    {
        $key = EncryptionHelper::getUserKey();
        if (!$key) {
            throw new \Exception('Encryption key not available');
        }

        return new EncryptionService($key);
    }

    public function index(Vault $vault)
    {
        if ($vault->user_id !== Auth::id()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        return ApiResponse::success(FileItemResource::collection($vault->fileItems));
    }

    public function store(FileItemRequest $request, Vault $vault)
    {
        if ($vault->user_id !== Auth::id()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $uploadedFile = $request->file('file');
        $rawContents = file_get_contents($uploadedFile->getRealPath());

        $encryption = $this->getEncryptionService();
        $encrypted = $encryption->encrypt($rawContents);

        $path = sprintf('vaults/%d/%s.enc', $vault->id, uniqid('', true));
        Storage::disk('local')->put($path, base64_decode($encrypted['encrypted_data']));

        $fileItem = $vault->fileItems()->create([
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
        ]);

        return ApiResponse::success(new FileItemResource($fileItem), 'File uploaded successfully', 201);
    }

    public function show(Vault $vault, FileItem $file)
    {
        if ($vault->user_id !== Auth::id() || $file->vault_id !== $vault->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        return ApiResponse::success(new FileItemResource($file));
    }

    public function update(FileItemRequest $request, Vault $vault, FileItem $file)
    {
        if ($vault->user_id !== Auth::id() || $file->vault_id !== $vault->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $uploadedFile = $request->file('file');
        $rawContents = file_get_contents($uploadedFile->getRealPath());

        $encryption = $this->getEncryptionService();
        $encrypted = $encryption->encrypt($rawContents);

        $newPath = sprintf('vaults/%d/%s.enc', $vault->id, uniqid('', true));
        Storage::disk('local')->put($newPath, base64_decode($encrypted['encrypted_data']));
        Storage::disk('local')->delete($file->file_path);

        $file->update([
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $newPath,
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
        ]);

        return ApiResponse::success(new FileItemResource($file), 'File updated successfully');
    }

    public function destroy(Vault $vault, FileItem $file)
    {
        if ($vault->user_id !== Auth::id() || $file->vault_id !== $vault->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        Storage::disk('local')->delete($file->file_path);
        $file->delete();

        return ApiResponse::success(null, 'File deleted successfully');
    }

    public function download(string $token)
    {
        $cacheKey = self::DOWNLOAD_TOKEN_CACHE_PREFIX . $token;
        $payload = Cache::get($cacheKey);

        if (!$payload || !isset($payload['file_item_id'], $payload['key'], $payload['user_id'])) {
            return ApiResponse::error('Download token invalid or expired.', 404);
        }

        $fileItem = FileItem::find($payload['file_item_id']);
        if (!$fileItem) {
            return ApiResponse::error('File not found.', 404);
        }

        $vault = $fileItem->vault;
        if (!$vault || $vault->user_id !== $payload['user_id']) {
            return ApiResponse::error('Unauthorized', 403);
        }

        if (!Storage::disk('local')->exists($fileItem->file_path)) {
            return ApiResponse::error('Encrypted file not found.', 404);
        }

        $encryptedContents = base64_encode(Storage::disk('local')->get($fileItem->file_path));
        $key = base64_decode($payload['key']);
        $encryption = new EncryptionService($key);
        $decrypted = $encryption->decrypt($encryptedContents, $fileItem->iv, $fileItem->tag);

        Cache::forget($cacheKey);

        return response($decrypted, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $fileItem->file_name . '"',
        ]);
    }

    public function downloadUrl(Vault $vault, FileItem $file)
    {
        if ($vault->user_id !== Auth::id() || $file->vault_id !== $vault->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $key = EncryptionHelper::getUserKey();
        if (!$key) {
            return ApiResponse::error('Encryption key not available.', 500);
        }

        $token = bin2hex(random_bytes(16));
        $cacheKey = self::DOWNLOAD_TOKEN_CACHE_PREFIX . $token;

        Cache::put($cacheKey, [
            'file_item_id' => $file->id,
            'user_id' => Auth::id(),
            'key' => base64_encode($key),
        ], self::DOWNLOAD_TOKEN_TTL_SECONDS);

        return ApiResponse::success([
            'download_url' => route('files.download', ['token' => $token]),
            'expires_at' => now()->addSeconds(self::DOWNLOAD_TOKEN_TTL_SECONDS)->toISOString(),
        ]);
    }
}
