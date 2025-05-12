<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        if (! Schema::hasTable('notifications')) {
            return Inertia::render('Notifications/Index', [
                'notifications' => $this->emptyPaginator(),
            ]);
        }

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? $notification->type,
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ]);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        if (! Schema::hasTable('notifications')) {
            return back();
        }

        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->first();

        if (! $notification) {
            abort(404);
        }

        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('notifications')) {
            return back();
        }

        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    private function emptyPaginator(): LengthAwarePaginator
    {
        $currentPage = Paginator::resolveCurrentPage();

        return new LengthAwarePaginator([], 0, 20, $currentPage, [
            'path' => route('notifications.index', absolute: false),
        ]);
    }
}
