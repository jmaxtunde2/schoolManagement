<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Models\School;

/**
 * Choisit le canal externe par ordre de priorité :
 * SMS → WhatsApp → Email, en fonction de la disponibilité (solde/budget)
 * et des coordonnées réellement disponibles pour le parent.
 */
class NotificationChannelService
{
    public function __construct(
        private readonly CommunicationBudgetService $budget
    ) {}

    /**
     * @return array<int, CommunicationChannel>
     */
    public function candidates(School $school, ?string $phone, ?string $email): array
    {
        $channels = [];

        if ($phone) {
            if ($this->budget->smsAvailable($school)) {
                $channels[] = CommunicationChannel::Sms;
            }

            if ($this->budget->whatsappAvailable($school)) {
                $channels[] = CommunicationChannel::Whatsapp;
            }
        }

        if ($email && $this->budget->emailAvailable($school)) {
            $channels[] = CommunicationChannel::Email;
        }

        return $channels;
    }

    public function select(School $school, ?string $phone, ?string $email): ?CommunicationChannel
    {
        return $this->candidates($school, $phone, $email)[0] ?? null;
    }

    public function isExternal(CommunicationChannel $channel): bool
    {
        return $channel !== CommunicationChannel::InApp;
    }
}
