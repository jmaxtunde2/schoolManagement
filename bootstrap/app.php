<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureRecentGoogleReauthentication;
use App\Http\Middleware\EnsureUserHasSchool;
use App\Http\Middleware\EnsureTwoFactorConfigured;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Middleware\RequireFreshTwoFactor;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [HandleInertiaRequests::class]);

        $middleware->alias([
            'role' => EnsureRole::class,
            'school' => EnsureUserHasSchool::class,
            'google.reauth' => EnsureRecentGoogleReauthentication::class,
            '2fa.configured' => EnsureTwoFactorConfigured::class,
            '2fa.verified' => EnsureTwoFactorVerified::class,
            '2fa.fresh' => RequireFreshTwoFactor::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route(auth()->user()->role->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
