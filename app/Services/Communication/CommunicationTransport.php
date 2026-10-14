<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Services\Sms\SmsResponse;

/**
 * Abstraction de transport externe. Permet de remplacer le fournisseur
 * (eSMS Africa, Meta WhatsApp, email) sans toucher au reste du système.
 */
interface CommunicationTransport
{
    public function send(CommunicationChannel $channel, string $recipient, string $message): SmsResponse;
}
