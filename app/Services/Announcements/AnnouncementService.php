<?php

namespace App\Services\Announcements;

use App\Enums\AnnouncementStatus;
use App\Enums\NotificationStatus;
use App\Jobs\SendAnnouncementRecipientJob;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\ParentGuardian;
use Illuminate\Database\Eloquent\Collection;

/**
 * Résout l'audience d'une annonce, crée ses destinataires et orchestre l'envoi.
 */
class AnnouncementService
{
    /**
     * Parents ciblés par l'annonce selon son audience.
     *
     * @return Collection<int, ParentGuardian>
     */
    public function resolveRecipients(Announcement $announcement): Collection
    {
        $query = ParentGuardian::withoutGlobalScopes()
            ->where('school_id', $announcement->school_id)
            ->where('is_active', true)
            ->with(['students:id,class_id,first_name,last_name', 'students.classRoom:id,level']);

        switch ($announcement->audience_type) {
            case Announcement::AUDIENCE_CLASSES:
                $classIds = $announcement->classes()->pluck('classes.id');
                $query->whereHas('students', fn ($q) => $q->whereIn('class_id', $classIds));
                break;

            case Announcement::AUDIENCE_LEVELS:
                $levels = $announcement->levels ?? [];
                $query->whereHas('students.classRoom', fn ($q) => $q->whereIn('level', $levels));
                break;

            case Announcement::AUDIENCE_GUARDIANS:
                $ids = $announcement->guardians()->pluck('parents.id');
                $query->whereIn('id', $ids);
                break;
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Crée les destinataires et met les envois en file. Retourne le nombre
     * de destinataires créés (0 si l'annonce n'est plus éligible).
     */
    public function dispatch(Announcement $announcement): int
    {
        if (! $announcement->isDispatchable()) {
            return 0;
        }

        $announcement->update(['status' => AnnouncementStatus::Sending]);

        $recipients = $this->resolveRecipients($announcement);
        $channels = $this->resolveChannels($announcement);
        $created = 0;

        foreach ($recipients as $guardian) {
            foreach ($channels as $channel) {
                if ($channel === 'email' && blank($guardian->email)) {
                    continue;
                }

                if ($channel !== 'email' && blank($guardian->phone)) {
                    continue;
                }

                $key = hash('sha256', implode('|', [
                    $announcement->id,
                    $guardian->id,
                    $channel,
                ]));

                $recipient = AnnouncementRecipient::withoutGlobalScopes()->firstOrCreate(
                    ['idempotency_key' => $key],
                    [
                        'school_id' => $announcement->school_id,
                        'announcement_id' => $announcement->id,
                        'parent_id' => $guardian->id,
                        'student_id' => $this->targetStudentId($announcement, $guardian),
                        'channel' => $channel,
                        'phone' => $channel === 'email' ? null : $guardian->phone,
                        'email' => $channel === 'email' ? $guardian->email : null,
                        'message' => $this->buildMessage($announcement, $guardian),
                        'status' => NotificationStatus::Pending,
                    ]
                );

                if ($recipient->wasRecentlyCreated) {
                    $created++;
                    SendAnnouncementRecipientJob::dispatch($recipient->id);
                }
            }
        }

        $this->syncStatus($announcement);

        return $created;
    }

    /** Recalcule le statut global de l'annonce à partir de ses destinataires. */
    public function syncStatus(Announcement $announcement): void
    {
        $announcement->refresh();

        if ($announcement->status === AnnouncementStatus::Cancelled) {
            return;
        }

        $counts = $announcement->recipients()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ((int) $counts->sum() === 0) {
            // Aucun destinataire (audience vide) : ne pas rester bloqué sur « en cours ».
            $announcement->update(['status' => AnnouncementStatus::Failed]);

            return;
        }

        $pending = (int) (($counts['pending'] ?? 0) + ($counts['queued'] ?? 0));
        $sent = (int) (($counts['sent'] ?? 0) + ($counts['delivered'] ?? 0));
        $failed = (int) ($counts['failed'] ?? 0);

        $status = match (true) {
            $pending > 0 => AnnouncementStatus::Sending,
            $sent > 0 && $failed > 0 => AnnouncementStatus::Partial,
            $sent > 0 => AnnouncementStatus::Sent,
            default => AnnouncementStatus::Failed,
        };

        $announcement->update([
            'status' => $status,
            'sent_at' => in_array($status, [AnnouncementStatus::Sent, AnnouncementStatus::Partial], true)
                ? ($announcement->sent_at ?? now())
                : $announcement->sent_at,
        ]);
    }

    /**
     * Canaux réellement utilisés : le canal téléphonique est « whatsapp »
     * lorsque le fournisseur actif est la Meta Cloud API, sinon « sms ».
     *
     * @return array<int, string>
     */
    private function resolveChannels(Announcement $announcement): array
    {
        $channels = [];

        foreach ($announcement->channels ?? [] as $channel) {
            if ($channel === Announcement::CHANNEL_EMAIL) {
                $channels[] = 'email';
            } elseif ($channel === Announcement::CHANNEL_WHATSAPP) {
                $channels[] = $this->phoneChannel();
            }
        }

        return array_values(array_unique($channels));
    }

    private function phoneChannel(): string
    {
        return config('services.sms.provider', 'log') === 'whatsapp'
            ? 'whatsapp'
            : 'sms';
    }

    /** Élève rattaché servant de contexte (uniquement pour les audiences par classe/niveau). */
    private function targetStudentId(Announcement $announcement, ParentGuardian $guardian): ?int
    {
        if (! in_array($announcement->audience_type, [Announcement::AUDIENCE_CLASSES, Announcement::AUDIENCE_LEVELS], true)) {
            return null;
        }

        return $guardian->students->first()?->id;
    }

    /** Remplace les espaces réservés simples : {parent}, {école}, {eleve}. */
    private function buildMessage(Announcement $announcement, ParentGuardian $guardian): string
    {
        $student = $guardian->students->first();

        return strtr($announcement->body, [
            '{parent}' => $guardian->name,
            '{école}' => (string) ($announcement->school?->name ?? ''),
            '{ecole}' => (string) ($announcement->school?->name ?? ''),
            '{eleve}' => (string) ($student?->full_name ?? ''),
            '{élève}' => (string) ($student?->full_name ?? ''),
        ]);
    }
}
