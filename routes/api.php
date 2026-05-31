<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileItemController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\VaultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1'); // Rate limiting: 10 attempts per minute

Route::middleware('auth:sanctum')->group(function () {
    // Dashboard 
    Route::class(AuthController::class)->group(function () {
        Route::get('/dashboard', 'dashboard');
        Route::post('/logout', 'logout');
    });
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Two-factor authentication routes
    Route::post('/2fa/setup', [AuthController::class, 'setup2FA']);
    Route::post('/2fa/verify', [AuthController::class, 'verify2FA']);
    Route::post('/2fa/disable', [AuthController::class, 'disable2FA']);
    Route::get('/devices', [AuthController::class, 'getDeviceHistory']);

    Route::get('/items/sync', [ItemController::class, 'sync']);
    Route::apiResource('vaults', VaultController::class);
    Route::apiResource('vaults.items', ItemController::class);
    Route::apiResource('vaults.files', FileItemController::class);
    Route::get('/vaults/{vault}/files/{file}/download-url', [FileItemController::class, 'downloadUrl']);
});

Route::get('/files/download/{token}', [FileItemController::class, 'download'])->name('files.download');
