<?php

use App\Console\Commands\DispatchScheduledAnnouncements;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Annonces parents programmées : mises en file dès que leur date est atteinte.
Schedule::command(DispatchScheduledAnnouncements::class)->everyMinute()->withoutOverlapping();
