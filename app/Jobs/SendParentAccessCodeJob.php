<?php

namespace App\Jobs;

use App\Models\ParentAccessCode;
use App\Models\School;
use App\Services\ParentAccess\ParentAccessNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envoie au parent le code d'accès et le lien vers l'espace parent.
 * Canal par défaut : SMS (fournisseur SMS actif) ; WhatsApp reste disponible
 * avec les deux modèles approuvés (verification_code_parent et espace_parent).
 */
class SendParentAccessCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public int $parentAccessCodeId) {}

    public function handle(ParentAccessNotificationService $notifications): void
    {
        $code = ParentAccessCode::query()->find($this->parentAccessCodeId);

        if (! $code || ! $code->isValid()) {
            return;
        }

        $parent = $code->parent;
        $school = School::query()->find($code->school_id);

        if (! $parent || ! $school) {
            Log::warning('Parent access code notification skipped: parent or school missing.', [
                'parent_access_code_id' => $this->parentAccessCodeId,
            ]);

            return;
        }

        $result = $notifications->sendAccessCode($parent, $school, $code->code);

        if (! $result['sent']) {
            Log::warning('Parent access code notification incomplete.', [
                'parent_access_code_id' => $this->parentAccessCodeId,
                'parent_id' => $parent->id,
                'school_id' => $school->id,
                'sent_count' => $result['sent_count'],
                'errors' => $result['errors'],
            ]);

            // Ne réessayer que si aucun message n'est parti, afin de ne pas
            // envoyer deux fois le même code à cause d'un échec partiel.
            if ($result['sent_count'] === 0) {
                throw new RuntimeException(implode('; ', $result['errors']));
            }
        }
    }
}
