<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\FileItemRequest;
use App\Http\Resources\FileItemResource;
use App\Models\FileItem;
use App\Models\Vault;
use App\Services\FileService;
use App\Services\VaultService;
use Illuminate\Support\Facades\Auth;

class FileItemController extends Controller
{
    protected FileService $fileService;

    protected VaultService $vaultService;

    public function __construct(FileService $fileService, VaultService $vaultService)
    {
        $this->fileService = $fileService;
        $this->vaultService = $vaultService;
    }

    public function index(Vault $vault)
    {
        if (! $this->vaultService->authorizeVaultAccess(Auth::id(), $vault->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $files = $this->fileService->getVaultFiles($vault->id);

        return ApiResponse::success(FileItemResource::collection($files));
    }

    public function store(FileItemRequest $request, Vault $vault)
    {
        if (! $this->vaultService->authorizeVaultAccess(Auth::id(), $vault->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $fileItem = $this->fileService->uploadFile(
                $vault->id,
                $request->file('file'),
                $request->string('file_name')->toString(),
                $request->string('iv')->toString(),
                $request->string('tag')->toString()
            );

            return ApiResponse::success(new FileItemResource($fileItem), 'File uploaded successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to upload file: ' . $e->getMessage(), 500);
        }
    }

    public function show(Vault $vault, FileItem $file)
    {
        if (
            ! $this->vaultService->authorizeVaultAccess(Auth::id(), $vault->id) ||
            ! $this->fileService->getUserVaultFile(Auth::id(), $vault->id, $file->id)
        ) {
            return ApiResponse::error('Unauthorized', 403);
        }

        return ApiResponse::success(new FileItemResource($file));
    }

    public function update(FileItemRequest $request, Vault $vault, FileItem $file)
    {
        if (
            ! $this->vaultService->authorizeVaultAccess(Auth::id(), $vault->id) ||
            ! $this->fileService->getUserVaultFile(Auth::id(), $vault->id, $file->id)
        ) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $updated = $this->fileService->updateFile(
                $vault->id,
                $file->id,
                $request->file('file'),
                $request->string('file_name')->toString(),
                $request->string('iv')->toString(),
                $request->string('tag')->toString()
            );

            if (! $updated) {
                return ApiResponse::error('File not found', 404);
            }

            $file->refresh();

            return ApiResponse::success(new FileItemResource($file), 'File updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update file: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Vault $vault, FileItem $file)
    {
        if (
            ! $this->vaultService->authorizeVaultAccess(Auth::id(), $vault->id) ||
            ! $this->fileService->getUserVaultFile(Auth::id(), $vault->id, $file->id)
        ) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $deleted = $this->fileService->deleteFile($vault->id, $file->id);

        if (! $deleted) {
            return ApiResponse::error('File not found', 404);
        }

        return ApiResponse::success(null, 'File deleted successfully');
    }

    /**
     * Recent Files.
     */
    public function recent()
    {
        $files = $this->fileService->getRecentFiles(auth()->id());
        return ApiResponse::success(FileItemResource::collection($files));
    }

    public function download(string $token)
    {
        $result = $this->fileService->downloadFile($token);

        if (! $result) {
            return ApiResponse::error('Download token invalid or expired.', 404);
        }

        $downloadName = str_replace(['"', "\r", "\n"], '', $result['filename']) . '.enc';

        return response($result['content'], 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            'X-File-Name' => rawurlencode($result['filename']),
            'X-File-Iv' => $result['iv'],
            'X-File-Tag' => $result['tag'],
        ]);
    }

    public function downloadUrl(Vault $vault, FileItem $file)
    {
        if (
            ! $this->vaultService->authorizeVaultAccess(Auth::id(), $vault->id) ||
            ! $this->fileService->getUserVaultFile(Auth::id(), $vault->id, $file->id)
        ) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $result = $this->fileService->generateDownloadUrl(Auth::id(), $vault->id, $file->id);

        if (! $result) {
            return ApiResponse::error('Failed to generate download URL', 500);
        }

        return ApiResponse::success([
            'download_url' => $result['download_url'],
            'expires_at' => $result['expires_at'],
        ]);
    }
}
