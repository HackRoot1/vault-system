<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Vault;
use App\Services\ItemService;
use App\Services\VaultService;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    protected ItemService $itemService;

    protected VaultService $vaultService;

    public function __construct(ItemService $itemService, VaultService $vaultService)
    {
        $this->itemService = $itemService;
        $this->vaultService = $vaultService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Vault $vault)
    {
        if (! $this->vaultService->authorizeVaultAccess(auth()->id(), $vault->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $items = $this->itemService->getVaultItems($vault->id);

        return ApiResponse::success(ItemResource::collection($items));
    }

    /**
     * Sync items updated since the last timestamp.
     */
    public function sync(Request $request)
    {
        $lastSync = $request->query('last_sync');
        if (! $lastSync) {
            return ApiResponse::error('The last_sync query parameter is required.', 400);
        }

        try {
            $items = $this->itemService->syncItems(auth()->id(), $lastSync);
        } catch (\Exception $e) {
            return ApiResponse::error('Invalid last_sync timestamp.', 400);
        }

        return ApiResponse::success(ItemResource::collection($items));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ItemRequest $request, Vault $vault)
    {
        if (! $this->vaultService->authorizeVaultAccess(auth()->id(), $vault->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $item = $this->itemService->createItem($vault->id, $request->validated());

            return ApiResponse::success(new ItemResource($item), 'Item created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create item: '.$e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Vault $vault, Item $item)
    {
        if (! $this->vaultService->authorizeVaultAccess(auth()->id(), $vault->id) ||
            ! $this->itemService->getUserVaultItem(auth()->id(), $vault->id, $item->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        return ApiResponse::success(new ItemResource($item));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ItemRequest $request, Vault $vault, Item $item)
    {
        if (! $this->vaultService->authorizeVaultAccess(auth()->id(), $vault->id) ||
            ! $this->itemService->getUserVaultItem(auth()->id(), $vault->id, $item->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        try {
            $updated = $this->itemService->updateItem($vault->id, $item->id, $request->validated());

            if (! $updated) {
                return ApiResponse::error('Item not found', 404);
            }

            $item->refresh();

            return ApiResponse::success(new ItemResource($item), 'Item updated successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update item: '.$e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vault $vault, Item $item)
    {
        if (! $this->vaultService->authorizeVaultAccess(auth()->id(), $vault->id) ||
            ! $this->itemService->getUserVaultItem(auth()->id(), $vault->id, $item->id)) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $deleted = $this->itemService->deleteItem($vault->id, $item->id);

        if (! $deleted) {
            return ApiResponse::error('Item not found', 404);
        }

        return ApiResponse::success(null, 'Item deleted successfully');
    }

    /**
     * Get recently edited items for dashboard.
     */
    public function recent()
    {
        $items = $this->itemService->getRecentItems(auth()->id());
        return ApiResponse::success(ItemResource::collection($items));
    }
}
