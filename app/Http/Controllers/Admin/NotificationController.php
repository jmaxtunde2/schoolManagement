<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\ResendNotificationRequest;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendSmsNotificationJob;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = Notification::with(['student:id,first_name,last_name', 'guardian:id,name,phone', 'evaluation:id,title'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only('status'),
            'counts' => Notification::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function resend(ResendNotificationRequest $request, Notification $notification): RedirectResponse
    {
        $notification->recordStatus(NotificationStatus::Pending, 'Relance manuelle par un administrateur.');

        if ($notification->channel === 'email') { SendEmailNotificationJob::dispatch($notification->id); } else { SendSmsNotificationJob::dispatch($notification->id); }

        return back()->with('success', 'SMS remis en file d\'envoi.');
    }
}
