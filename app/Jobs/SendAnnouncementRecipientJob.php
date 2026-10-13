<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Services\Announcements\AnnouncementService;
use App\Services\Mail\SchoolMailService;
use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SmsResponse;
use App\Services\Sms\SupportsDocuments;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Envoie une annonce à un parent donné sur un canal (whatsapp/sms ou email).
 * Gère la pièce jointe : email en pièce jointe, WhatsApp via un lien public.
 */
class SendAnnouncementRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public int $recipientId) {}

    public function handle(
        SmsManager $sms,
        SchoolMailService $mail,
        AnnouncementService $service
    ): void {
        $recipient = AnnouncementRecipient::withoutGlobalScopes()
            ->with(['announcement.school', 'guardian:id,name'])
            ->find($this->recipientId);

        if (! $recipient || in_array($recipient->status->value, ['sent', 'delivered'], true)) {
            return;
        }

        $recipient->increment('attempts');
        $recipient->markStatus(NotificationStatus::Queued);

        try {
            if ($recipient->channel === 'email') {
                $this->sendEmail($recipient, $mail);
            } else {
                $this->sendPhone($recipient, $sms);
            }
        } catch (Throwable $e) {
            $recipient->forceFill(['error' => $e->getMessage()])->save();
            $recipient->markStatus(NotificationStatus::Failed);

            if ($recipient->announcement) {
                $service->syncStatus($recipient->announcement);
            }

            throw $e;
        }

        if ($recipient->announcement) {
            $service->syncStatus($recipient->announcement);
        }
    }

    private function sendPhone(AnnouncementRecipient $recipient, SmsManager $sms): void
    {
        $provider = $sms->provider();
        $announcement = $recipient->announcement;

        // WhatsApp accepte une pièce jointe via un lien public ; sinon on
        // envoie le texte seul (SMS et autres fournisseurs).
        if (
            $announcement?->hasAttachment()
            && $recipient->channel === 'whatsapp'
            && $provider instanceof SupportsDocuments
        ) {
            $url = $announcement->attachmentUrl();

            if ($url) {
                $response = $provider->sendDocument(
                    (string) $recipient->phone,
                    $url,
                    $announcement->attachment_name,
                    $recipient->message
                );

                $this->applyPhoneResponse($recipient, $provider, $response);

                return;
            }
        }

        $response = $provider->send((string) $recipient->phone, $recipient->message);

        $this->applyPhoneResponse($recipient, $provider, $response);
    }

    private function applyPhoneResponse(
        AnnouncementRecipient $recipient,
        SmsProviderInterface $provider,
        SmsResponse $response
    ): void {
        if ($response->success) {
            $recipient->forceFill([
                'provider' => $provider->name(),
                'external_id' => $response->externalId,
                'sent_at' => now(),
                'error' => null,
            ])->save();
            $recipient->markStatus(NotificationStatus::Sent);

            return;
        }

        $recipient->forceFill([
            'provider' => $provider->name(),
            'error' => $response->error,
        ])->save();
        $recipient->markStatus(NotificationStatus::Failed);
    }

    private function sendEmail(AnnouncementRecipient $recipient, SchoolMailService $mail): void
    {
        $announcement = $recipient->announcement;
        $school = $announcement?->school;
        $subject = $announcement?->title ?? 'Annonce';
        $email = (string) $recipient->email;

        $attachments = $this->emailAttachments($announcement);

        $settings = $school?->mailSettings()->withoutGlobalScopes()->first();

        if ($school && $settings && $settings->is_active) {
            $mail->send(
                $school,
                $email,
                $recipient->guardian?->name,
                $subject,
                $this->toHtml($recipient->message),
                $attachments
            );

            $provider = 'school_smtp';
        } else {
            $this->sendWithDefaultMailer($email, $subject, $recipient->message, $attachments);

            $provider = (string) config('mail.default');
        }

        $recipient->forceFill([
            'provider' => $provider,
            'sent_at' => now(),
            'error' => null,
        ])->save();
        $recipient->markStatus(NotificationStatus::Sent);
    }

    /** @return array<int, array{path: string, name: ?string, mime: ?string}> */
    private function emailAttachments(?Announcement $announcement): array
    {
        if (! $announcement?->hasAttachment()) {
            return [];
        }

        $absolute = Storage::disk('public')->path($announcement->attachment_path);

        if (! is_file($absolute)) {
            return [];
        }

        return [[
            'path' => $absolute,
            'name' => $announcement->attachment_name,
            'mime' => $announcement->attachment_mime,
        ]];
    }

    private function sendWithDefaultMailer(
        string $to,
        string $subject,
        string $body,
        array $attachments
    ): void {
        Mail::raw($body, function ($message) use ($to, $subject, $attachments) {
            $message->to($to)->subject($subject);

            foreach ($attachments as $attachment) {
                $message->attach($attachment['path'], array_filter([
                    'as' => $attachment['name'] ?? null,
                    'mime' => $attachment['mime'] ?? null,
                ]));
            }
        });
    }

    private function toHtml(string $message): string
    {
        return nl2br(e($message));
    }

    public function failed(Throwable $exception): void
    {
        $recipient = AnnouncementRecipient::withoutGlobalScopes()->find($this->recipientId);

        $recipient?->forceFill(['error' => $exception->getMessage()])->save();
        $recipient?->markStatus(NotificationStatus::Failed);
    }
}
