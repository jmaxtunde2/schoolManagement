<?php

namespace App\Console\Commands;

use App\Services\Sms\EMcSmsProvider;
use Illuminate\Console\Command;

class TestEmcSms extends Command
{
    protected $signature = 'corischool:test-emc-sms
        {to : Numéro de test destinataire}
        {--message= : Message personnalisé}';

    protected $description = 'Envoie un SMS de test unique via le fournisseur e-MC';

    public function handle(EMcSmsProvider $provider): int
    {
        $to = trim((string) $this->argument('to'));
        $message = trim((string) $this->option('message'));

        if ($message === '') {
            $message = 'Test CoriSchool : ce message confirme que le fournisseur e-MC fonctionne.';
        }

        $this->line('Provider : '.$provider->name());
        $this->line('Numéro : '.$this->maskPhone($to));

        $response = $provider->send($to, $message);

        $this->line('HTTP status : '.($provider->lastHttpStatus() ?? 'n/a'));

        if ($response->success) {
            $this->info('Succès'
                .($response->externalId ? " (message ID: {$response->externalId})" : '')
                .'.');

            return self::SUCCESS;
        }

        $this->error('Échec : '.($response->error ?? 'erreur inconnue'));

        return self::FAILURE;
    }

    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);

        return $length <= 6
            ? str_repeat('*', $length)
            : substr($phone, 0, 4).str_repeat('*', max(0, $length - 6)).substr($phone, -2);
    }
}
