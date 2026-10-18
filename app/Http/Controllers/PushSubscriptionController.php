<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestion des abonnements Web Push de l'utilisateur connecté (PWA).
 *
 * Un abonnement correspond à un navigateur/appareil. L'endpoint est unique
 * au monde : il peut être ré-enregistré (même appareil, autre compte).
 */
class PushSubscriptionController extends Controller
{
    /** Clé publique VAPID, nécessaire au `pushManager.subscribe()` côté client. */
    public function vapidKey(): JsonResponse
    {
        return response()->json([
            'public_key' => config('webpush.vapid.public_key'),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:20'],
        ]);

        PushSubscription::withoutGlobalScopes()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()->id,
                'school_id' => $request->user()->school_id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'domain' => $request->getHost(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ],
        );

        return $this->respond($request, 'Notifications activées sur cet appareil.');
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        PushSubscription::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return $this->respond($request, 'Notifications désactivées sur cet appareil.');
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
