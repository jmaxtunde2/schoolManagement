<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\Announcements\AnnouncementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Résout l'audience d'une annonce et met en file un envoi par destinataire.
 * Exécuté aussi bien pour un envoi immédiat que pour une annonce programmée.
 */
class DispatchAnnouncementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $announcementId) {}

    public function handle(AnnouncementService $service): void
    {
        $announcement = Announcement::withoutGlobalScopes()->find($this->announcementId);

        if (! $announcement) {
            return;
        }

        $service->dispatch($announcement);
    }
}
