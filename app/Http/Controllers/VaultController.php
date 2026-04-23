<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\VaultRequest;
use App\Http\Resources\VaultResource;
use App\Models\Vault;
use App\Services\VaultService;

class VaultController extends Controller
{
    protected VaultService $vaultService;

    public function __construct(VaultService $vaultService)
    {
        $this->vaultService = $vaultService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vaults = $this->vaultService->getUserVaults(auth()->id());

        return ApiResponse::success(VaultResource::collection($vaults));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VaultRequest $request)
    {
        $vault = $this->vaultService->createVault(auth()->id(), $request->validated());

        return ApiResponse::success(new VaultResource($vault), 'Vault created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vault $vault)
    {
        if (! $this->vaultService->authorizeVaultAccess(auth()->id(), $vault->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        return ApiResponse::success(new VaultResource($vault));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VaultRequest $request, Vault $vault)
    {
        if (! $this->vaultService->authorizeVaultAccess(auth()->id(), $vault->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $updated = $this->vaultService->updateVault(auth()->id(), $vault->id, $request->validated());

        if (! $updated) {
            return ApiResponse::error('Vault not found', 404);
        }

        $vault->refresh();

        return ApiResponse::success(new VaultResource($vault), 'Vault updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vault $vault)
    {
        if (! $this->vaultService->authorizeVaultAccess(auth()->id(), $vault->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $deleted = $this->vaultService->deleteVault(auth()->id(), $vault->id);

        if (! $deleted) {
            return ApiResponse::error('Vault not found', 404);
        }

        return ApiResponse::success(null, 'Vault deleted successfully');
    }
}
