<?php

namespace App\Http\Middleware;

use App\Services\TwoFactor\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class RequireFreshTwoFactor
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function handle(Request $request, Closure $next, string $purpose = 'sensitive')
    {
        $pendingKey = 'two_factor_pending_actions.'.$purpose;

        if ($this->twoFactor->sensitiveVerified($request, $purpose)) {
            $encryptedAction = $request->session()->get($pendingKey);

            if (is_string($encryptedAction)) {
                try {
                    $action = Crypt::decrypt($encryptedAction);

                    if (
                        is_array($action)
                        && ($action['url'] ?? null) === $request->getRequestUri()
                        && ($action['method'] ?? null) === strtolower($request->method())
                    ) {
                        $request->session()->put('_previous.url', $action['return']);
                        $request->session()->forget($pendingKey);
                    }
                } catch (\Throwable) {
                    $request->session()->forget($pendingKey);
                }
            }

            return $next($request);
        }

        $referer = $request->headers->get('referer');
        $return = parse_url($referer ?: '', PHP_URL_PATH);
        $query = parse_url($referer ?: '', PHP_URL_QUERY);

        if (! is_string($return) || ! str_starts_with($return, '/') || str_starts_with($return, '//')) {
            $return = route($request->user()->role->homeRoute(), [], false);
            $query = null;
        }

        if ($query) {
            $return .= '?'.$query;
        }

        $action = [
            'url' => $request->getRequestUri(),
            'method' => strtolower($request->method()),
            'data' => $request->except(['_token', '_method']),
            'return' => $return,
            'created_at' => now()->timestamp,
        ];

        $request->session()->put($pendingKey, Crypt::encrypt($action));

        return redirect()->route('two-factor.challenge', [
            'purpose' => $purpose,
            'return' => $return,
        ])->with('error', 'Un code Authenticator est requis pour cette action.');
    }
}
