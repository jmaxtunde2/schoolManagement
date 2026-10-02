<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fournisseur par défaut : n'envoie rien, journalise le message.
 * Permet de démontrer et tester tout le flux (validation -> queue -> historique)
 * avant le branchement d'un vrai fournisseur SMS via SMS_PROVIDER=.
 */
class LogSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): SmsResponse
    {
        $externalId = 'log_'.Str::random(12);

        Log::info('[SMS:log] Envoi simulé', ['phone' => $phone, 'message' => $message, 'external_id' => $externalId]);

        return SmsResponse::success($externalId);
    }

    public function name(): string
    {
        return 'log';
    }
}
