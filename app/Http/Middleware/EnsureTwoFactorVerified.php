<?php

namespace App\Http\Middleware;

use App\Services\TwoFactor\TwoFactorService;
use Closure;
use Illuminate\Http\Request;

class EnsureTwoFactorVerified
{
    public function __construct(private TwoFactorService $twoFactor) {}

    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u && ! $this->twoFactor->loginVerified($request) && ! $request->routeIs('two-factor.*')) {
            return redirect()->route('two-factor.challenge', ['return' => $request->fullUrl()]);
        }

return $next($request);
    }
}
