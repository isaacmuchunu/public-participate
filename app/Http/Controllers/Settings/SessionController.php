<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        if (! Schema::hasTable('user_sessions')) {
            return Inertia::render('settings/Sessions', [
                'sessions' => [],
            ]);
        }

        $currentSessionId = $request->session()->getId();

        $sessions = $request->user()
            ->sessions()
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn (UserSession $session) => [
                'id' => $session->id,
                'session_id' => $session->session_id,
                'device' => $session->device,
                'ip_address' => $session->ip_address,
                'location' => $session->location,
                'user_agent' => $session->user_agent,
                'login_at' => $session->login_at?->toDateTimeString(),
                'last_activity_at' => $session->last_activity_at?->toDateTimeString(),
                'is_current' => $session->isCurrent($currentSessionId),
            ]);

        return Inertia::render('settings/Sessions', [
            'sessions' => $sessions,
        ]);
    }

    public function destroy(Request $request, UserSession $session): RedirectResponse
    {
        if (! Schema::hasTable('user_sessions')) {
            return back();
        }

        abort_unless($session->user_id === $request->user()->id, 403);

        $isCurrent = $session->isCurrent($request->session()->getId());

        $session->delete();

        if ($isCurrent) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return back()->with('status', 'session-revoked');
    }
}
