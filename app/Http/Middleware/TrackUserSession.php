<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TrackUserSession
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! Auth::check()) {
            return $response;
        }

        if (! $this->hasUserSessionsTable()) {
            return $response;
        }

        $sessionId = $request->session()->getId();

        $session = UserSession::firstOrNew([
            'session_id' => $sessionId,
        ]);

        if (! $session->exists) {
            $session->login_at = now();
        }

        $session->fill([
            'user_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1024),
            'device' => $this->resolveDevice((string) $request->userAgent()),
            'last_activity_at' => now(),
        ])->save();

        return $response;
    }

    private function resolveDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown device';
        }

        $agent = strtolower($userAgent);

        return match (true) {
            str_contains($agent, 'iphone') => 'iPhone',
            str_contains($agent, 'ipad') => 'iPad',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'mac os') => 'Mac',
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'linux') => 'Linux',
            default => 'Web client',
        };
    }

    private function hasUserSessionsTable(): bool
    {
        return Schema::hasTable('user_sessions');
    }
}
