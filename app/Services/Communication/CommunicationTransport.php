<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Models\School;
use App\Services\Sms\SmsResponse;

/**
 * Abstraction de transport externe. Permet de remplacer le fournisseur
 * (SMS, WhatsApp, email) sans toucher au reste du système.
 */
interface CommunicationTransport
{
    public function send(
        CommunicationChannel $channel,
        School $school,
        string $recipient,
        string $message,
        ?array $metadata = null
    ): SmsResponse;
}
