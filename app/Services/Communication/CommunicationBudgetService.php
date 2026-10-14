<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Models\School;
use App\Models\SchoolCommunicationSetting;

/**
 * Disponibilité des canaux et comptabilisation des coûts de communication.
 *
 * Distingue le budget alloué par CoriSchool (sms_budget / whatsapp_budget)
 * du solde réel du fournisseur (sms_balance).
 */
class CommunicationBudgetService
{
    public function __construct(
        private readonly CommunicationSettingsService $settings
    ) {}

    public function settingsFor(School $school): SchoolCommunicationSetting
    {
        return $this->settings->for($school);
    }

    public function smsAvailable(School $school): bool
    {
        $settings = $this->settingsFor($school);

        return $settings->sms_enabled
            && (int) $settings->sms_balance >= (int) $settings->sms_low_balance_threshold;
    }

    public function whatsappAvailable(School $school): bool
    {
        $settings = $this->settingsFor($school);

        return $settings->whatsapp_enabled && $settings->whatsappRemaining() > 0;
    }

    public function emailAvailable(School $school): bool
    {
        return (bool) $this->settingsFor($school)->email_enabled;
    }

    /** @return array{0: float, 1: string} Coût unitaire et devise. */
    public function costFor(CommunicationChannel $channel): array
    {
        $costs = config('communication.costs', []);
        $currencies = config('communication.currencies', []);

        return [
            (float) ($costs[$channel->value] ?? 0),
            (string) ($currencies[$channel->value] ?? 'XOF'),
        ];
    }

    /**
     * Débite le budget de l'école après un envoi réussi.
     */
    public function consume(School $school, CommunicationChannel $channel, float $cost): void
    {
        if ($cost <= 0) {
            return;
        }

        $settings = $this->settingsFor($school);

        if ($channel === CommunicationChannel::Sms) {
            $settings->sms_balance = max(0, (int) $settings->sms_balance - (int) ceil($cost));
            $settings->save();

            return;
        }

        if ($channel === CommunicationChannel::Whatsapp) {
            $settings->whatsapp_consumed = (float) $settings->whatsapp_consumed + $cost;
            $settings->save();
        }
    }
}
