<?php

namespace App\Console\Commands;

use App\Enums\AnnouncementStatus;
use App\Jobs\DispatchAnnouncementJob;
use App\Models\Announcement;
use Illuminate\Console\Command;

/**
 * Envoie les annonces dont la date d'envoi programmée est atteinte.
 * À planifier chaque minute (voir routes/console.php).
 */
class DispatchScheduledAnnouncements extends Command
{
    protected $signature = 'announcements:dispatch-scheduled';

    protected $description = 'Met en file d\'envoi les annonces programmées arrivées à échéance';

    public function handle(): int
    {
        $announcements = Announcement::withoutGlobalScopes()
            ->where('status', AnnouncementStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($announcements as $announcement) {
            DispatchAnnouncementJob::dispatch($announcement->id);
        }

        $this->info("{$announcements->count()} annonce(s) programmée(s) mise(s) en file.");

        return self::SUCCESS;
    }
}
