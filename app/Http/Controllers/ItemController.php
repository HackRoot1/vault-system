<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\EncryptionHelper;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Vault;
use App\Services\EncryptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Get encryption service with user's derived key
     */
    private function getEncryptionService(): EncryptionService
    {
        $key = EncryptionHelper::getUserKey();
        if (!$key) {
            throw new \Exception('Encryption key not available');
        }
        return new EncryptionService($key);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Vault $vault)
    {
        // Ensure vault belongs to authenticated user
        if ($vault->user_id !== auth()->id()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $items = $vault->items;
        return ApiResponse::success(ItemResource::collection($items));
    }

    /**
     * Sync items updated since the last timestamp.
     */
    public function sync(Request $request)
    {
        $lastSync = $request->query('last_sync');
        if (!$lastSync) {
            return ApiResponse::error('The last_sync query parameter is required.', 400);
        }

        try {
            $lastSync = Carbon::parse($lastSync);
        } catch (\Exception $e) {
            return ApiResponse::error('Invalid last_sync timestamp.', 400);
        }

        $user = auth()->user();
        $vaultIds = $user->vaults()->pluck('id');

        $items = Item::withTrashed()
            ->whereIn('vault_id', $vaultIds)
            ->where(function ($query) use ($lastSync) {
                $query->where('updated_at', '>', $lastSync)
                    ->orWhere('deleted_at', '>', $lastSync);
            })
            ->orderBy('updated_at')
            ->get();

        return ApiResponse::success(ItemResource::collection($items));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ItemRequest $request, Vault $vault)
    {
        // Ensure vault belongs to authenticated user
        if ($vault->user_id !== auth()->id()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $encryption = $this->getEncryptionService();
        $dataJson = json_encode($request->data);
        $encrypted = $encryption->encrypt($dataJson);

        $item = $vault->items()->create([
            'type' => $request->type,
            'encrypted_data' => $encrypted['encrypted_data'],
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
        ]);

        return ApiResponse::success(new ItemResource($item), 'Item created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vault $vault, Item $item)
    {
        // Ensure vault and item belong to authenticated user
        if ($vault->user_id !== auth()->id() || $item->vault_id !== $vault->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        return ApiResponse::success(new ItemResource($item));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ItemRequest $request, Vault $vault, Item $item)
    {
        // Ensure vault and item belong to authenticated user
        if ($vault->user_id !== auth()->id() || $item->vault_id !== $vault->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $encryption = $this->getEncryptionService();
        $dataJson = json_encode($request->data);
        $encrypted = $encryption->encrypt($dataJson);

        $item->update([
            'type' => $request->type,
            'encrypted_data' => $encrypted['encrypted_data'],
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
        ]);

        return ApiResponse::success(new ItemResource($item), 'Item updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vault $vault, Item $item)
    {
        // Ensure vault and item belong to authenticated user
        if ($vault->user_id !== auth()->id() || $item->vault_id !== $vault->id) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $item->delete();
        return ApiResponse::success(null, 'Item deleted successfully');
    }
}
