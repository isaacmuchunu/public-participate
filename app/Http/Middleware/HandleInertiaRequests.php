<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'notifications' => fn () => $request->user() && Schema::hasTable('notifications')
                ? [
                    'unread_count' => $request->user()->unreadNotifications()->count(),
                    'latest' => $request->user()->notifications()->latest()->limit(5)->get()
                        ->map(fn ($notification) => [
                            'id' => $notification->id,
                            'type' => $notification->data['type'] ?? $notification->type,
                            'data' => $notification->data,
                            'read_at' => $notification->read_at,
                            'created_at' => $notification->created_at,
                        ])
                        ->all(),
                ]
                : ['unread_count' => 0, 'latest' => []],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $this->resolveStatusMessage($request->session()->get('status')),
                'message' => $request->session()->get('message'),
                'bag' => $request->session()->get('flash'),
            ],
        ];
    }

    private function resolveStatusMessage(?string $status): ?string
    {
        return match ($status) {
            null => null,
            'verification-link-sent' => 'A new verification link has been sent to the email address you provided during registration.',
            'session-revoked' => 'Session revoked successfully.',
            default => $status,
        };
    }
}
