<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptanceController extends Controller
{
    /**
     * Show the invitation acceptance form
     */
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $user = User::where('invitation_token', $token)
            ->whereNull('invitation_used_at')
            ->whereNull('email_verified_at')
            ->first();

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired invitation link.');
        }

        return Inertia::render('auth/AcceptInvitation', [
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }

    /**
     * Accept the invitation and set password
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $user = User::where('invitation_token', $token)
            ->whereNull('invitation_used_at')
            ->whereNull('email_verified_at')
            ->first();

        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired invitation link.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Mark invitation as used and verify email
        $user->update([
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'invitation_used_at' => now(),
            'invitation_token' => null, // Clear the token for security
        ]);

        // Log the user in
        auth()->login($user);

        // Regenerate session for security
        $request->session()->regenerate();

        // Redirect based on role
        return match ($user->role->value) {
            'mp', 'senator' => redirect()->intended(route('legislator.bills.index'))
                ->with('success', 'Welcome! Your account has been activated successfully.'),
            'clerk' => redirect()->intended(route('clerk.legislators.index'))
                ->with('success', 'Welcome! Your account has been activated successfully.'),
            default => redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome! Your account has been activated successfully.'),
        };
    }
}
