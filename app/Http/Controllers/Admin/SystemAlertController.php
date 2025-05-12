<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSystemAlertRequest;
use App\Models\SystemAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SystemAlertController extends Controller
{
    public function store(StoreSystemAlertRequest $request): RedirectResponse
    {
        $expiresAt = $request->input('expires_at');

        SystemAlert::create([
            'title' => $request->input('title'),
            'message' => $request->input('message'),
            'severity' => $request->input('severity'),
            'action_url' => $request->input('action_url'),
            'created_by' => $request->user()->id,
            'published_at' => now(),
            'expires_at' => $expiresAt ? Carbon::parse($expiresAt) : null,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('flash', [
                'status' => 'success',
                'message' => 'System alert published successfully.',
            ]);
    }

    public function destroy(Request $request, SystemAlert $systemAlert): RedirectResponse
    {
        $systemAlert->update([
            'dismissed_at' => now(),
            'dismissed_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('flash', [
                'status' => 'success',
                'message' => 'System alert dismissed.',
            ]);
    }
}
