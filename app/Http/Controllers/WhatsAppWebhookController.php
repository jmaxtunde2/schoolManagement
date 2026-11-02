<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWatiWebhookJob;
use App\Jobs\ProcessWhatsAppWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $verifyToken = config('services.whatsapp.verify_token');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $verifyToken && $token === $verifyToken) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handle(Request $request): Response
    {
        $payload = $request->all();

        if ($this->isWatiWebhook($request, $payload)) {
            return $this->handleWatiWebhook($request, $payload);
        }

        Log::debug('Meta WhatsApp webhook received', ['object' => $payload['object'] ?? null]);

        dispatch(new ProcessWhatsAppWebhookJob($payload));

        return response('EVENT_RECEIVED', 200);
    }

    private function handleWatiWebhook(Request $request, array $payload): Response
    {
        $secret = trim((string) config('services.whatsapp.wati.webhook_secret', ''));

        if ($secret === '') {
            Log::error('WATI webhook rejected because WATI_WEBHOOK_SECRET is not configured.', [
                'event' => $payload['eventType'] ?? null,
            ]);

            return response('Forbidden', 403);
        }

        if (! $this->hasValidWatiAuth($request, $secret)) {
            Log::warning('WATI webhook rejected due to invalid authentication.', [
                'event' => $payload['eventType'] ?? null,
                'ip' => $request->ip(),
            ]);

            return response('Forbidden', 403);
        }

        dispatch(new ProcessWatiWebhookJob($payload));

        return response('EVENT_RECEIVED', 200);
    }

    private function isWatiWebhook(Request $request, array $payload): bool
    {
        if ($request->query('provider') === 'wati') {
            return true;
        }

        if (isset($payload['eventType'])) {
            return true;
        }

        return isset($payload['localMessageId']) && isset($payload['statusString']);
    }

    private function hasValidWatiAuth(Request $request, string $secret): bool
    {
        $authorization = (string) $request->header('Authorization', '');

        if (str_starts_with($authorization, 'Bearer ')
            && hash_equals($secret, substr($authorization, 7))) {
            return true;
        }

        $headerSecret = (string) ($request->header('X-Wati-Webhook-Secret')
            ?: $request->header('X-Webhook-Secret')
            ?: '');

        if ($headerSecret !== '' && hash_equals($secret, $headerSecret)) {
            return true;
        }

        $querySecret = (string) ($request->query('token') ?: $request->query('secret') ?: '');

        if ($querySecret !== '' && hash_equals($secret, $querySecret)) {
            return true;
        }

        $signature = (string) ($request->header('X-Wati-Signature')
            ?: $request->header('X-Webhook-Signature')
            ?: '');

        if ($signature === '') {
            return false;
        }

        $signature = preg_replace('/^sha256=/i', '', $signature) ?: $signature;
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
