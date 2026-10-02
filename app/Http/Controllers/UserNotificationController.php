<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserNotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications->through(function (UserNotification $notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                    'data' => $notification->data ?? [],
                    'is_read' => (bool) $notification->read_at,
                ];
            }),
        ]);
    }

    public function markAsRead(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->user_id === $request->user()->id
            && $notification->school_id === $request->user()->school_id,
            403
        );

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été lues.');
    }
}
