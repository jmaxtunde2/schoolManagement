<?php

namespace App\Console\Commands;

use App\Models\UserNotification;
use Illuminate\Console\Command;

/**
 * Supprime les notifications in-app relues et anciennes afin de ne pas
 * saturer l'espace parent. À planifier chaque nuit.
 */
class CleanupUserNotifications extends Command
{
    protected $signature = 'corischool:cleanup-user-notifications';

    protected $description = 'Nettoie les notifications in-app antérieures à la période de conservation';

    public function handle(): int
    {
        $days = max(1, (int) config('communication.in_app_retention_days', 15));
        $cutoff = now()->subDays($days);

        $deleted = UserNotification::withoutGlobalScopes()
            ->where('type', 'evaluation.grades')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("{$deleted} notification(s) in-app nettoyée(s) (antérieure(s) au {$cutoff->toDateString()}).");

        return self::SUCCESS;
    }
}
