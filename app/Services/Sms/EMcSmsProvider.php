<?php

namespace App\Services\Sms;

use App\Support\BeninPhoneNumber;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EMcSmsProvider implements SmsProviderInterface
{
    private ?int $lastHttpStatus = null;

    public function send(string $phone, string $message): SmsResponse
    {
        $this->lastHttpStatus = null;

        if (! config('services.sms.emc.enabled', true)) {
            return SmsResponse::failure('Fournisseur e-MC désactivé (EMC_SMS_ENABLED).');
        }

        $apiUrl = rtrim((string) config('services.sms.emc.api_url', 'https://api.e-mc.co/v3'), '/');
        $sender = trim((string) config('services.sms.emc.sender', 'CoriSchool'));
        $username = config('services.sms.emc.username');
        $password = config('services.sms.emc.password');
        $apiKey = config('services.sms.emc.api_key');

        if ($apiUrl === '' || (empty($apiKey) && (empty($username) || empty($password)))) {
            return SmsResponse::failure('Identifiants e-MC manquants (EMC_SMS_API_KEY ou EMC_SMS_USERNAME / EMC_SMS_PASSWORD).');
        }

        if ($sender === '') {
            return SmsResponse::failure('Sender e-MC manquant (EMC_SMS_SENDER).');
        }

        $to = $this->normalizePhone($phone);

        if ($to === null) {
            return SmsResponse::failure('Numéro de téléphone invalide pour e-MC.');
        }

        $timeout = max(1, (int) config('services.sms.emc.timeout', 15));

        try {
            $request = Http::timeout($timeout)->acceptJson()->asJson();

            if (! empty($username) && ! empty($password)) {
                $request = $request->withBasicAuth((string) $username, (string) $password);
            } else {
                $token = preg_replace('/^Basic\s+/i', '', trim((string) $apiKey));
                $request = $request->withHeaders(['Authorization' => 'Basic '.$token]);
            }

            $response = $request->post($apiUrl.'/sms', [
                'from' => $sender,
                'to' => $to,
                'content' => $message,
            ]);

            $this->lastHttpStatus = $response->status();

            $data = $response->json();
            $success = data_get($data, 'data.success');

            if (! $response->successful()) {
                return SmsResponse::failure('e-MC HTTP '.$response->status().' : '.$this->errorMessage($response, $data));
            }

            if ($success !== true) {
                return SmsResponse::failure('e-MC : '.$this->errorMessage($response, $data));
            }

            $messageId = data_get($data, 'data.messageId');

            return SmsResponse::success(is_scalar($messageId) ? (string) $messageId : null);
        } catch (\Throwable $e) {
            Log::warning('[SMS:emc] Échec de l’envoi', [
                'phone' => $this->maskPhone($to),
                'status' => $this->lastHttpStatus,
                'error' => $e->getMessage(),
            ]);

            return SmsResponse::failure('e-MC erreur : '.$e->getMessage());
        }
    }

    public function name(): string
    {
        return 'emc';
    }

    public function lastHttpStatus(): ?int
    {
        return $this->lastHttpStatus;
    }

    private function normalizePhone(string $phone): ?string
    {
        $countryCode = ltrim((string) config('services.sms.emc.country_code', '229'), '+');
        $assumeBenin = $countryCode === BeninPhoneNumber::COUNTRY_CODE;

        if ($benin = BeninPhoneNumber::forSms($phone, $assumeBenin)) {
            return $benin;
        }

        $number = preg_replace('/[^0-9+]/', '', $phone);

        if (! is_string($number) || $number === '') {
            return null;
        }

        if (str_starts_with($number, '+')) {
            $digits = substr($number, 1);
        } elseif (str_starts_with($number, '00')) {
            $digits = substr($number, 2);
        } else {
            $digits = $number;

            if ($countryCode !== '' && ! str_starts_with($digits, $countryCode)) {
                $digits = $countryCode.$digits;
            }
        }

        if (preg_match('/^[1-9]\d{7,14}$/', $digits) !== 1) {
            return null;
        }

        return $digits;
    }

    private function errorMessage(Response $response, mixed $data): string
    {
        $message = data_get($data, 'data.message')
            ?? data_get($data, 'message')
            ?? data_get($data, 'error')
            ?? data_get($data, 'data.error');

        if (is_array($message)) {
            $message = $message['message'] ?? json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return substr((string) $response->body(), 0, 300);
    }

    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);

        return $length <= 6
            ? str_repeat('*', $length)
            : substr($phone, 0, 4).str_repeat('*', max(0, $length - 6)).substr($phone, -2);
    }
}
