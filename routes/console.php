<?php

use App\Console\Commands\CheckSmsBalances;
use App\Console\Commands\CleanupUserNotifications;
use App\Console\Commands\DispatchScheduledAnnouncements;
use App\Console\Commands\NotifyValidatedEvaluations;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Annonces parents programmées : mises en file dès que leur date est atteinte.
Schedule::command(DispatchScheduledAnnouncements::class)->everyMinute()->withoutOverlapping();

// Notifications parentales consolidées : une fois par jour, à l'heure configurée.
Schedule::command(NotifyValidatedEvaluations::class)
    ->dailyAt(config('communication.daily_hour', '17:00'))
    ->withoutOverlapping();

// Solde SMS fournisseur : contrôle nocturne et bascule des canaux.
Schedule::command(CheckSmsBalances::class)
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Notifications in-app : purge des éléments au-delà de la période de conservation.
Schedule::command(CleanupUserNotifications::class)
    ->dailyAt('03:00')
    ->withoutOverlapping();
