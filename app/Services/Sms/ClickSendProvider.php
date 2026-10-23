<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class ClickSendProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): SmsResponse
    {
        $username = config('services.sms.clicksend.username');
        $apiKey = config('services.sms.clicksend.api_key');
        $sender = config('services.sms.clicksend.sender');

        if (empty($username) || empty($apiKey)) {
            return SmsResponse::failure('Identifiants ClickSend manquants (CLICKSEND_USERNAME / CLICKSEND_API_KEY).');
        }

        $payload = [
            'source' => 'corischool',
            'body' => $message,
            'to' => $phone,
        ];

        if (! empty($sender)) {
            $payload['from'] = $sender;
        }

        try {
            $url = rtrim(
                config('services.sms.clicksend.api_url', 'https://rest.clicksend.com/v3'),
                '/'
            ).'/sms/send';

            $response = Http::withBasicAuth($username, $apiKey)
                ->timeout(30)
                ->acceptJson()
                ->asJson()
                ->post($url, ['messages' => [$payload]]);

            $data = $response->json();
            $responseCode = $data['response_code'] ?? null;

            if (! $response->successful() || ($responseCode !== null && $responseCode !== 'SUCCESS')) {
                $error = $data['response_msg'] ?? substr((string) $response->body(), 0, 300);

                return SmsResponse::failure('ClickSend HTTP '.$response->status().' : '.$error);
            }

            $messageId = $data['data']['messages'][0]['message_id'] ?? null;

            return SmsResponse::success($messageId !== null ? (string) $messageId : null);
        } catch (\Throwable $e) {
            return SmsResponse::failure('ClickSend erreur : '.$e->getMessage());
        }
    }

    public function name(): string
    {
        return 'clicksend';
    }
}
