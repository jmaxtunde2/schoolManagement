<?php

namespace App\Console\Commands;

use App\Services\Communication\NotificationAggregationService;
use Illuminate\Console\Command;

/**
 * Consolide les évaluations validées de la journée par élève et met en file
 * d'envoi une notification parent (in-app + canal externe).
 *
 * À planifier quotidiennement (voir routes/console.php).
 */
class NotifyValidatedEvaluations extends Command
{
    protected $signature = 'corischool:notify-validated-evaluations';

    protected $description = 'Regroupe les évaluations validées et notifie les parents';

    public function handle(NotificationAggregationService $aggregation): int
    {
        $stats = $aggregation->aggregate();

        $this->info(sprintf(
            '%d lot(s), %d destinataire(s) externe(s) mis en file, %d lot(s) ignoré(s).',
            $stats['batches'],
            $stats['queued'],
            $stats['skipped'],
        ));

        return self::SUCCESS;
    }
}
