<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class EnsureTwoFactorConfigured { public function handle(Request $request,Closure $next){$u=$request->user(); if($u && !$u->two_factor_confirmed_at && !$request->routeIs('two-factor.*')) return redirect()->route('two-factor.setup'); return $next($request);} }
