<?php

namespace App\Services\ParentAccess;

use App\Jobs\SendParentAccessCodeJob;

/**
 * Met les envois de codes d'accès en file par lots, comme les notifications
 * de notes, afin de lisser la charge auprès du fournisseur SMS/WhatsApp.
 */
class ParentAccessCodeBatchService
{
    public function __construct(private readonly ParentAccessNotificationService $notifications) {}

    public function configured(): bool
    {
        return $this->notifications->configured();
    }

    public function label(): string
    {
        return $this->notifications->label();
    }

    /**
     * @param  array<int, int>  $codeIds
     */
    public function queue(array $codeIds): int
    {
        if ($codeIds === []) {
            return 0;
        }

        $size = max(1, (int) config('communication.batch_size', 25));
        $interval = max(0, (int) config('communication.batch_interval_minutes', 10));
        $queued = 0;

        foreach (array_chunk($codeIds, $size) as $index => $chunk) {
            $delay = now()->addMinutes($index * $interval);

            foreach ($chunk as $codeId) {
                SendParentAccessCodeJob::dispatch($codeId)->delay($delay);
                $queued++;
            }
        }

        return $queued;
    }
}
