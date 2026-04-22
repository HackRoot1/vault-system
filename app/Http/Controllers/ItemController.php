<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\ItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\Vault;
use App\Services\EncryptionService;
use Illuminate\Http\Request;

class ItemController extends Controller
{
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
     * Store a newly created resource in storage.
     */
    public function store(ItemRequest $request, Vault $vault)
    {
        // Ensure vault belongs to authenticated user
        if ($vault->user_id !== auth()->id()) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $dataJson = json_encode($request->data);
        $encrypted = EncryptionService::encrypt($dataJson);

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

        $dataJson = json_encode($request->data);
        $encrypted = EncryptionService::encrypt($dataJson);

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
