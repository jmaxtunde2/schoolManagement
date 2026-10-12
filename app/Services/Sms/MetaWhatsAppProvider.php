<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

/**
 * Fournisseur « WhatsApp Business Cloud API » (Meta).
 *
 * Actif via SMS_PROVIDER=whatsapp. Appel REST :
 *   POST https://graph.facebook.com/{version}/{phone_number_id}/messages
 *
 * Note : l'API exige des numéros au format international E.164
 * (ex. +22890000001). Les numéros nationaux sont normalisés avec l'indicatif
 * configuré (services.sms.country_code, 228 par défaut).
 *
 * Note Meta : une conversation initiée par l'établissement peut nécessiter un
 * modèle de message approuvé si le destinataire ne répond pas dans la fenêtre
 * de 24 h. L'envoi ci-dessous utilise le type « text » (valable pendant une
 * session). Pour un envoi hors session, basculer sur un template.
 */
class MetaWhatsAppProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): SmsResponse
    {
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (! is_string($token) || trim($token) === '') {
            return SmsResponse::failure('WHATSAPP_TOKEN is not configured.');
        }

        if (! is_string($phoneNumberId) || trim($phoneNumberId) === '') {
            return SmsResponse::failure('WHATSAPP_PHONE_NUMBER_ID is not configured.');
        }

        $to = $this->toE164($phone);

        if ($to === null) {
            return SmsResponse::failure("Numéro invalide pour WhatsApp : {$phone}");
        }

        $base = rtrim(config('services.whatsapp.api_url', 'https://graph.facebook.com'), '/');
        $version = config('services.whatsapp.api_version', 'v21.0');
        $url = "{$base}/{$version}/{$phoneNumberId}/messages";

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return SmsResponse::success($data['messages'][0]['id'] ?? null);
        }

        $error = $response->json('error.message')
            ?? substr((string) $response->body(), 0, 500);

        return SmsResponse::failure(
            'WhatsApp HTTP '.$response->status().': '.$error
        );
    }

    public function name(): string
    {
        return 'whatsapp';
    }

    /**
     * Normalise un numéro vers E.164 (imposé par l'API WhatsApp).
     *
     *  - "+22890000001"    -> tel quel
     *  - "0022890000001"   -> "+22890000001"
     *  - "22890000001"     -> "+22890000001"
     *  - "90000001"        -> "+22890000001"  (indicatif par défaut)
     *  - "090000001"       -> "+22890000001"  (0 national supprimé)
     */
    private function toE164(string $phone): ?string
    {
        $number = preg_replace('/[^0-9+]/', '', $phone);
        $countryCode = (string) config('services.sms.country_code', '228');

        if (str_starts_with($number, '+')) {
            $candidate = $number;
        } elseif (str_starts_with($number, '00')) {
            $candidate = '+'.substr($number, 2);
        } elseif (strlen($number) >= 11 && $countryCode !== '' && str_starts_with($number, $countryCode)) {
            $candidate = '+'.$number;
        } else {
            $candidate = '+'.$countryCode.ltrim($number, '0');
        }

        if (preg_match('/^\+[1-9]\d{7,14}$/', $candidate) !== 1) {
            return null;
        }

        return $candidate;
    }
}