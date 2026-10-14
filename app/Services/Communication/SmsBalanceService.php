<?php

namespace App\Services\Communication;

use App\Models\School;
use App\Models\SchoolCommunicationSetting;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Surveillance du solde SMS fournisseur et réactivation des canaux.
 *
 * Si aucun endpoint de solde n'est configuré (SMS_BALANCE_URL), le solde
 * local est conservé tel quel. Sinon, la bascule applique une hystérésis :
 * désactivation sous le seuil bas, réactivation au-dessus du seuil de reprise.
 */
class SmsBalanceService
{
    public function __construct(
        private readonly CommunicationSettingsService $settings
    ) {}

    public function refresh(School $school, ?int $providerBalance = null): SchoolCommunicationSetting
    {
        $settings = $this->settings->for($school);

        if ($providerBalance !== null) {
            $settings->sms_balance = max(0, $providerBalance);
        }

        if ((int) $settings->sms_balance < (int) $settings->sms_low_balance_threshold) {
            $settings->sms_enabled = false;
        } elseif ((int) $settings->sms_balance >= (int) $settings->sms_reactivation_threshold) {
            $settings->sms_enabled = true;
        }

        $settings->last_sms_balance_check_at = now();
        $settings->save();

        return $settings;
    }

    /**
     * Interroge le fournisseur une seule fois pour tout le cycle. Retourne null
     * si l'endpoint n'est pas configuré ou indisponible.
     */
    public function fetchBalance(): ?int
    {
        $url = config('services.sms.balance_url');
        $token = config('services.sms.token');

        if (! is_string($url) || trim($url) === '' || ! is_string($token) || trim($token) === '') {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $balance = $response->json('balance')
                ?? $response->json('credits')
                ?? data_get($response->json(), 'data.balance');

            return is_numeric($balance) ? (int) $balance : null;
        } catch (Throwable) {
            return null;
        }
    }
}
