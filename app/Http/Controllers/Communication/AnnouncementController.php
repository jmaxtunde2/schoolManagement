<?php

namespace App\Http\Controllers\Communication;

use App\Enums\AnnouncementStatus;
use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcements\StoreAnnouncementRequest;
use App\Jobs\DispatchAnnouncementJob;
use App\Jobs\SendAnnouncementRecipientJob;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Module de communication : annonces libres envoyées aux parents
 * par WhatsApp et/ou email (réunion, information, convocation…).
 */
class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        $announcements = Announcement::query()
            ->with('creator:id,name', 'classes:id,name')
            ->withCount([
                'guardians',
                'recipients as recipients_count',
                'recipients as sent_count' => fn ($q) => $q->whereIn('status', ['sent', 'delivered']),
                'recipients as failed_count' => fn ($q) => $q->where('status', 'failed'),
            ])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Communication/Announcements/Index', [
            'announcements' => $announcements,
            'filters' => ['status' => $status],
            'counts' => Announcement::selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function create(): Response
    {
        $classes = ClassRoom::query()->orderBy('name')->get(['id', 'name', 'level']);

        return Inertia::render('Communication/Announcements/Create', [
            'classes' => $classes,
            'levels' => $classes->pluck('level')->filter()->unique()->values(),
            'guardians' => ParentGuardian::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email']),
            'providerChannel' => config('services.sms.provider', 'log') === 'whatsapp' ? 'whatsapp' : 'sms',
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $scheduled = ! empty($data['scheduled_at']);

        $attachment = $request->file('attachment');
        $attachmentData = [];

        if ($attachment) {
            $attachmentData = [
                'attachment_path' => $attachment->store('announcements/'.$request->user()->school_id, 'public'),
                'attachment_name' => $attachment->getClientOriginalName(),
                'attachment_mime' => $attachment->getClientMimeType(),
                'attachment_size' => $attachment->getSize(),
            ];
        }

        $announcement = Announcement::create([
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'channels' => $data['channels'],
            'audience_type' => $data['audience_type'],
            'levels' => $data['audience_type'] === Announcement::AUDIENCE_LEVELS
                ? ($data['levels'] ?? [])
                : null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $scheduled ? AnnouncementStatus::Scheduled : AnnouncementStatus::Sending,
            ...$attachmentData,
        ]);

        if ($data['audience_type'] === Announcement::AUDIENCE_CLASSES) {
            $announcement->classes()->sync($data['class_ids'] ?? []);
        }

        if ($data['audience_type'] === Announcement::AUDIENCE_GUARDIANS) {
            $announcement->guardians()->sync($data['guardian_ids'] ?? []);
        }

        if (! $scheduled) {
            DispatchAnnouncementJob::dispatch($announcement->id);
        }

        return redirect()
            ->route('communication.announcements.show', $announcement)
            ->with('success', $scheduled
                ? 'Annonce programmée pour la date choisie.'
                : 'Annonce en cours d\'envoi aux parents.');
    }

    public function show(Announcement $announcement): Response
    {
        $announcement->load('creator:id,name', 'classes:id,name');
        $announcement->loadCount([
            'recipients as recipients_count',
            'recipients as sent_count' => fn ($q) => $q->whereIn('status', ['sent', 'delivered']),
            'recipients as failed_count' => fn ($q) => $q->where('status', 'failed'),
        ]);

        $recipients = $announcement->recipients()
            ->with(['guardian:id,name', 'student:id,first_name,last_name'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Communication/Announcements/Show', [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'channels' => $announcement->channels,
                'audience_type' => $announcement->audience_type,
                'audience_label' => $announcement->audienceLabel(),
                'levels' => $announcement->levels,
                'classes' => $announcement->classes->map(fn ($class) => ['id' => $class->id, 'name' => $class->name]),
                'status' => $announcement->status->value,
                'status_label' => $announcement->status->label(),
                'scheduled_at' => $announcement->scheduled_at?->toIso8601String(),
                'sent_at' => $announcement->sent_at?->toIso8601String(),
                'created_at' => $announcement->created_at?->toIso8601String(),
                'creator' => $announcement->creator?->name,
                'attachment' => $announcement->hasAttachment() ? [
                    'name' => $announcement->attachment_name,
                    'url' => $announcement->attachmentUrl(),
                ] : null,
                'recipients_count' => $announcement->recipients_count,
                'sent_count' => $announcement->sent_count,
                'failed_count' => $announcement->failed_count,
            ],
            'recipients' => $recipients,
        ]);
    }

    public function cancel(Announcement $announcement): RedirectResponse
    {
        if (! $announcement->isCancellable()) {
            return back()->with('error', 'Cette annonce ne peut plus être annulée.');
        }

        $announcement->update(['status' => AnnouncementStatus::Cancelled]);

        return back()->with('success', 'Annonce annulée.');
    }

    public function resend(Announcement $announcement): RedirectResponse
    {
        $recipients = $announcement->recipients()
            ->where('status', NotificationStatus::Failed->value)
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->forceFill([
                'status' => NotificationStatus::Pending,
                'error' => null,
            ])->save();

            SendAnnouncementRecipientJob::dispatch($recipient->id);
        }

        if ($recipients->isNotEmpty()) {
            $announcement->update(['status' => AnnouncementStatus::Sending]);
        }

        return back()->with('success', $recipients->count().' destinataire(s) relancé(s).');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        if (! $announcement->isDeletable()) {
            return back()->with('error', 'Une annonce en cours d\'envoi ne peut pas être supprimée.');
        }

        if ($announcement->hasAttachment()) {
            Storage::disk('public')->delete($announcement->attachment_path);
        }

        $announcement->delete();

        return redirect()
            ->route('communication.announcements.index')
            ->with('success', 'Annonce supprimée.');
    }
}
