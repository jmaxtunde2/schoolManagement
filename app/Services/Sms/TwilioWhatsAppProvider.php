<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TwilioWhatsAppProvider implements WhatsAppProviderInterface
{
    public function name(): string
    {
        return 'twilio';
    }

    public function send(
        string $phone,
        string $message,
        ?array $metadata = null
    ): SmsResponse {
        try {
            $accountSid = trim((string) config('services.twilio.account_sid'));
            $authToken = trim((string) config('services.twilio.auth_token'));
            $from = trim((string) config('services.twilio.whatsapp_from'));
            $timeout = (int) config('services.twilio.timeout', 15);

            if ($accountSid === '' || $authToken === '') {
                return SmsResponse::failure(
                    'Twilio credentials are not configured.'
                );
            }

            if ($from === '') {
                return SmsResponse::failure(
                    'Twilio WhatsApp sender is not configured.'
                );
            }

            $template = data_get($metadata, 'whatsapp.template');

            if (!$template) {
                return SmsResponse::failure(
                    'Twilio WhatsApp template is missing.'
                );
            }

            $contentSid = config(
                "services.twilio.templates.{$template}.content_sid"
            );

            if (!$contentSid) {
                return SmsResponse::failure(
                    "Twilio Content SID is not configured for template [{$template}]."
                );
            }

            $bodyParameters = data_get(
                $metadata,
                'whatsapp.body_parameters',
                []
            );

            $urlParameter = data_get(
                $metadata,
                'whatsapp.url_parameter'
            );

            /*
             * Twilio ContentVariables use string keys:
             *
             * {
             *   "1": "...",
             *   "2": "...",
             *   ...
             * }
             */
            $variables = [];

            foreach (array_values($bodyParameters) as $index => $value) {
                if ($value === null || $value === '') {
                    return SmsResponse::failure(
                        "Twilio variable [" . ($index + 1) . "] is empty."
                    );
                }

                $variables[(string) ($index + 1)] =
                    $this->cleanVariable((string) $value);
            }

            /*
             * URL parameter is the last variable.
             *
             * For:
             * espace_parent:
             *   1 = parent
             *   2 = school
             *   3 = slug
             *
             * evaluation:
             *   1 = school
             *   2 = parent
             *   3 = student
             *   4 = academic year
             *   5 = marks
             *   6 = slug
             */
            if ($urlParameter !== null && $urlParameter !== '') {
                $variables[(string) (count($variables) + 1)] =
                    $this->cleanVariable((string) $urlParameter);
            }

            $to = $this->normalizePhone($phone);

            if (!$to) {
                return SmsResponse::failure(
                    "Invalid WhatsApp phone number: {$phone}"
                );
            }

            $payload = [
                'From' => $from,
                'To' => 'whatsapp:' . $to,
                'ContentSid' => $contentSid,
            ];

            /*
             * Authentication templates also use ContentVariables.
             *
             * verification_code_parent:
             * {
             *     "1": "CS-DVH0T2UL"
             * }
             */
            if (!empty($variables)) {
                $payload['ContentVariables'] = json_encode(
                    $variables,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }

            $url = sprintf(
                'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
                $accountSid
            );

            $response = Http::withBasicAuth(
                $accountSid,
                $authToken
            )
                ->asForm()
                ->timeout($timeout)
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();

                $sid = $data['sid'] ?? null;

                Log::info('Twilio WhatsApp message sent', [
                    'template' => $template,
                    'content_sid' => $contentSid,
                    'to' => $to,
                    'message_sid' => $sid,
                    'status' => $data['status'] ?? null,
                ]);

                return SmsResponse::success($sid);
            }

            $data = $response->json();

            $error = $data['message']
                ?? $data['detail']
                ?? $response->body();

            Log::error('Twilio WhatsApp send failed', [
                'template' => $template,
                'content_sid' => $contentSid,
                'to' => $to,
                'http_status' => $response->status(),
                'error' => $error,
                'code' => $data['code'] ?? null,
            ]);

            return SmsResponse::failure(
                "Twilio error {$response->status()}: {$error}"
            );
        } catch (Throwable $e) {
            Log::error('Twilio WhatsApp exception', [
                'error' => $e->getMessage(),
            ]);

            return SmsResponse::failure(
                'Twilio exception: ' . $e->getMessage()
            );
        }
    }

    private function cleanVariable(string $value): string
    {
        /*
         * WhatsApp Content variables cannot contain newlines.
         */
        return trim(
            preg_replace('/\s+/', ' ', $value)
        );
    }

    private function normalizePhone(string $phone): ?string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        /*
         * Already international:
         * +22995336503
         */
        if (str_starts_with($phone, '+')) {
            $digits = '+' . preg_replace('/\D+/', '', substr($phone, 1));

            return strlen($digits) >= 10 ? $digits : null;
        }

        /*
         * International format without +:
         * 22995336503
         */
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '229') && strlen($digits) >= 11) {
            return '+' . $digits;
        }

        /*
         * Benin local format:
         * 95336503
         * 01XXXXXXXX
         *
         * For CoriSchool, default to Benin (+229).
         */
        if (strlen($digits) === 8) {
            return '+229' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            $local = ltrim($digits, '0');

            if (strlen($local) === 8) {
                return '+229' . $local;
            }
        }

        return null;
    }
}