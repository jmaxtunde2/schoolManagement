<?php

namespace App\Services\Sms;

interface WhatsAppProviderInterface extends SmsProviderInterface
{
    public function send(string $phone, string $message, ?array $metadata = null): SmsResponse;
}
