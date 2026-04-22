<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\VaultRequest;
use App\Http\Resources\VaultResource;
use App\Models\Vault;
use Illuminate\Http\Request;

class VaultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vaults = auth()->user()->vaults;
        return ApiResponse::success(VaultResource::collection($vaults));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VaultRequest $request)
    {
        $vault = auth()->user()->vaults()->create($request->validated());
        return ApiResponse::success(new VaultResource($vault), 'Vault created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vault $vault)
    {
        // Ensure the vault belongs to the authenticated user
        if ($vault->user_id !== auth()->id()) {
            return ApiResponse::error('Unauthorized', 403);
        }
        return ApiResponse::success(new VaultResource($vault));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VaultRequest $request, Vault $vault)
    {
        // Ensure the vault belongs to the authenticated user
        if ($vault->user_id !== auth()->id()) {
            return ApiResponse::error('Unauthorized', 403);
        }
        $vault->update($request->validated());
        return ApiResponse::success(new VaultResource($vault), 'Vault updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vault $vault)
    {
        // Ensure the vault belongs to the authenticated user
        if ($vault->user_id !== auth()->id()) {
            return ApiResponse::error('Unauthorized', 403);
        }
        $vault->delete();
        return ApiResponse::success(null, 'Vault deleted successfully');
    }
}
