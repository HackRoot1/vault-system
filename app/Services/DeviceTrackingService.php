<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class DeviceTrackingService
{
    /**
     * Track a login attempt
     */
    public function trackLogin(User $user, Request $request): void
    {
        $deviceInfo = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_user_agent' => $request->userAgent(),
        ]);

        // Add to device history (keep last 10 devices)
        $history = $user->device_history ?? [];
        array_unshift($history, $deviceInfo);
        $history = array_slice($history, 0, 10);

        $user->update(['device_history' => $history]);
    }

    /**
     * Get device history for a user
     */
    public function getDeviceHistory(User $user): array
    {
        return $user->device_history ?? [];
    }

    /**
     * Check if IP is suspicious (basic check)
     */
    public function isSuspiciousLogin(User $user, Request $request): bool
    {
        $currentIp = $request->ip();
        $lastLoginIp = $user->last_login_ip;

        // If first login, not suspicious
        if (!$lastLoginIp) {
            return false;
        }

        // Check if IP is from different country (basic check - you might want to use a geo IP service)
        // For now, just check if it's a completely different IP
        return $currentIp !== $lastLoginIp;
    }

    /**
     * Get last login information
     */
    public function getLastLoginInfo(User $user): ?array
    {
        if (!$user->last_login_at) {
            return null;
        }

        return [
            'timestamp' => $user->last_login_at,
            'ip' => $user->last_login_ip,
            'user_agent' => $user->last_login_user_agent,
        ];
    }
}