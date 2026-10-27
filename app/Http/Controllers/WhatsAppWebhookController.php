<?php

namespace App\Http\Controllers;

use App\Enums\EvaluationNotificationStatus;
use App\Enums\NotificationStatus;
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

        Log::debug('WhatsApp webhook received', ['payload' => $payload]);

        dispatch(new ProcessWhatsAppWebhookJob($payload));

        return response('EVENT_RECEIVED', 200);
    }
}
