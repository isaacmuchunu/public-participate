<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserNotSuspended
{
    /**
     * Handle an incoming request to ensure user is not suspended or account locked
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check if account is suspended
        if ($user->suspended_at !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your account has been suspended. Please contact support for assistance.');
        }

        // Check if account is temporarily locked due to failed login attempts
        if ($user->locked_until && $user->locked_until->isFuture()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $minutesRemaining = now()->diffInMinutes($user->locked_until);

            return redirect()->route('login')
                ->with('error', "Your account is temporarily locked due to multiple failed login attempts. Please try again in {$minutesRemaining} minutes.");
        }

        // Clear lockout if time has passed
        if ($user->locked_until && $user->locked_until->isPast()) {
            $user->update([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ]);
        }

        return $next($request);
    }
}
