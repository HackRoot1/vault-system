<?php

namespace App\Http\Controllers;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $dto = new RegisterDTO($request->validated());
        $result = $this->authService->register($dto->toArray());

        return ApiResponse::success([
            'user' => $result['user'],
            'token' => $result['token'],
            'crypto' => $result['crypto'],
        ], 'User registered successfully', 201);
    }

    public function login(LoginRequest $request)
    {
        $dto = new LoginDTO($request->validated());
        $result = $this->authService->login($dto->toArray(), $request);

        if (isset($result['error'])) {
            return ApiResponse::error($result['error'], $result['code']);
        }

        if (isset($result['requires_2fa'])) {
            return ApiResponse::success([
                'requires_2fa' => true,
                'user_id' => $result['user_id'],
            ], 'Two-factor authentication required');
        }

        return ApiResponse::success([
            'user' => $result['user'],
            'token' => $result['token'],
            'crypto' => $result['crypto'],
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function dashboard(Request $request)
    {
        $totalVaults = $request->user()->vaults()->count();
        $totalItems = $request->user()->vaults()->withCount('items')->get()->sum('items_count');
        $totalFiles = $request->user()->vaults()->withCount('files')->get()->sum('files_count');

        return ApiResponse::success([
            'user' => $request->user(),
            'total_vaults' => $totalVaults,
            'total_items' => $totalItems,
            'total_files' => $totalFiles,
        ], 'Dashboard data retrieved successfully');
    }

    public function setup2FA(Request $request)
    {
        $result = $this->authService->setup2FA($request->user());

        if (isset($result['error'])) {
            return ApiResponse::error($result['error'], $result['code']);
        }

        return ApiResponse::success([
            'secret' => $result['secret'],
            'qr_code_url' => $result['qr_code_url'],
        ], 'Scan the QR code with Google Authenticator and use the generated code to verify');
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        if ($this->authService->verify2FA($request->user(), $request->code)) {
            return ApiResponse::success(null, 'Two-factor authentication enabled successfully');
        }

        return ApiResponse::error('Invalid verification code', 400);
    }

    public function disable2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        if ($this->authService->disable2FA($request->user(), $request->code)) {
            return ApiResponse::success(null, 'Two-factor authentication disabled successfully');
        }

        return ApiResponse::error('Invalid verification code', 400);
    }

    public function getDeviceHistory(Request $request)
    {
        $result = $this->authService->getDeviceHistory($request->user());

        return ApiResponse::success([
            'device_history' => $result['device_history'],
            'last_login' => $result['last_login'],
        ], 'Device history retrieved successfully');
    }
}
