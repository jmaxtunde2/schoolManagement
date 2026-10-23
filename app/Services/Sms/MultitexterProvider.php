<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class MultitexterProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): SmsResponse
    {
        $email = config('services.sms.multitexter.email', env('SMS_EMAIL'));
        $password = config('services.sms.multitexter.password', env('SMS_PASS'));
        $sender = config('services.sms.multitexter.sender', env('SMS_SENDER_NAME', 'LCRS'));

        if (empty($email) || empty($password)) {
            return SmsResponse::failure('Identifiants MultiTexter manquants (SMS_EMAIL / SMS_PASS).');
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->get('https://app.multitexter.com/v2/app/sms', [
                    'email'       => $email,
                    'password'    => $password,
                    'message'     => $message,
                    'sender_name' => $sender,
                    'recipients'  => $phone,
                    'forcednd'    => 1,
                ]);

            $body = trim($response->body());

            if (! $response->successful()) {
                return SmsResponse::failure('MultiTexter HTTP ' . $response->status() . ' : ' . substr($body, 0, 200));
            }

            // L'API MultiTexter renvoie un JSON même en cas d'erreur métier
            // (ex. {"status":-2,"msg":"Invalid or international numbers..."}).
            $data = json_decode($body, true);

            if (is_array($data) && array_key_exists('status', $data) && (int) $data['status'] < 0) {
                return SmsResponse::failure(
                    'MultiTexter : ' . ($data['msg'] ?? 'échec inconnu')
                );
            }

            return SmsResponse::success($data['id'] ?? null);
        } catch (\Throwable $e) {
            return SmsResponse::failure('MultiTexter erreur : ' . $e->getMessage());
        }
    }

    public function name(): string
    {
        return 'multitexter';
    }
}
