<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Models\School;
use App\Services\Mail\SchoolMailService;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SmsResponse;
use App\Services\Sms\WhatsAppProvider as SmsWhatsAppProvider;
use Illuminate\Support\Str;
use Throwable;

/**
 * Transport concret branché sur les fournisseurs configurés dans le projet.
 * Le SMS utilise le fournisseur actif (SMS_PROVIDER), WhatsApp passe par la
 * Meta WhatsApp Cloud API et l'email par le SMTP de l'établissement.
 */
class ProviderCommunicationTransport implements CommunicationTransport
{
    public function send(
        CommunicationChannel $channel,
        School $school,
        string $recipient,
        string $message,
        ?array $metadata = null
    ): SmsResponse {
        return match ($channel) {
            CommunicationChannel::Sms => $this->smsProvider()->send($recipient, $message),
            CommunicationChannel::Whatsapp => (new SmsWhatsAppProvider)->send($recipient, $message, $metadata),
            CommunicationChannel::Email => $this->sendEmail($school, $recipient, $message),
            default => SmsResponse::failure('Canal externe non pris en charge.'),
        };
    }

    private function smsProvider(): SmsProviderInterface
    {
        return app(SmsProviderInterface::class);
    }

    private function sendEmail(School $school, string $recipient, string $message): SmsResponse
    {
        try {
            app(SchoolMailService::class)->send(
                $school,
                $recipient,
                null,
                'Nouvelles notes scolaires',
                nl2br(e($message))
            );

            return SmsResponse::success('mail_'.Str::uuid());
        } catch (Throwable $exception) {
            return SmsResponse::failure('Email : '.$exception->getMessage());
        }
    }
}
