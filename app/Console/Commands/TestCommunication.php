<?php

namespace App\Console\Commands;

use App\Enums\CommunicationChannel;
use App\Models\User;
use App\Services\Communication\CommunicationTransport;
use App\Services\Communication\NotificationChannelService;
use Illuminate\Console\Command;

/**
 * Envoie un message de test à un seul destinataire, sans créer de notification
 * ni toucher aux évaluations en attente. Utile pour valider un fournisseur en
 * production avant de lancer la notification des parents.
 */
class TestCommunication extends Command
{
    protected $signature = 'corischool:test-communication
        {email : Email d’un utilisateur de l’école concernée}
        {to : Numéro de téléphone ou adresse email du destinataire}
        {--channel=whatsapp : Canal à tester : whatsapp, sms ou email}
        {--message= : Message personnalisé}
        {--fallback : Utiliser l’ordre réel WhatsApp → SMS → Email}';

    protected $description = 'Envoie un message de test à un seul destinataire';

    public function handle(
        CommunicationTransport $transport,
        NotificationChannelService $channels
    ): int {
        $user = User::query()
            ->with('school')
            ->where('email', $this->argument('email'))
            ->first();

        if (! $user || ! $user->school) {
            $this->error('Utilisateur introuvable ou sans établissement.');

            return self::FAILURE;
        }

        $school = $user->school;
        $to = trim((string) $this->argument('to'));
        $isEmail = filter_var($to, FILTER_VALIDATE_EMAIL) !== false;

        if ($to === '' || (! $isEmail && ! preg_match('/^\+?[0-9 ().-]{6,}$/', $to))) {
            $this->error('Destinataire invalide : indique un numéro ou une adresse email.');

            return self::FAILURE;
        }

        $message = trim((string) $this->option('message'));

        if ($message === '') {
            $message = 'Test CoriSchool : ce message confirme que le canal fonctionne.';
        }

        if ($this->option('fallback')) {
            $candidates = $channels->candidates(
                $school,
                $isEmail ? null : $to,
                $isEmail ? $to : null,
            );

            if ($candidates === []) {
                $this->warn('Aucun canal disponible pour ce destinataire (configuration, budget ou coordonnée).');

                return self::FAILURE;
            }
        } else {
            $channel = CommunicationChannel::tryFrom(strtolower((string) $this->option('channel')));

            if (! $channel || ! $channel->isExternal()) {
                $this->error('Canal invalide. Utilise whatsapp, sms ou email.');

                return self::FAILURE;
            }

            if (($channel === CommunicationChannel::Email) !== $isEmail) {
                $this->error($channel === CommunicationChannel::Email
                    ? 'Le canal email exige une adresse email valide.'
                    : 'Ce canal exige un numéro de téléphone.');

                return self::FAILURE;
            }

            $candidates = [$channel];
        }

        $this->line("École : {$school->name}");

        foreach ($candidates as $channel) {
            $this->line("Envoi via {$channel->label()} vers {$to}…");

            $response = $transport->send($channel, $school, $to, $message);

            if ($response->success) {
                $this->info("Succès via {$channel->label()}"
                    .($response->externalId ? " (ID: {$response->externalId})" : '')
                    .'.');

                return self::SUCCESS;
            }

            $this->warn("Échec via {$channel->label()} : ".($response->error ?? 'erreur inconnue'));
        }

        $this->error('Aucun canal n’a pu envoyer le message.');

        return self::FAILURE;
    }
}
