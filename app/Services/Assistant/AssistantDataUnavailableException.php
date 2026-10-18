<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent;
use RuntimeException;

/**
 * Levée lorsqu'une intention ne peut pas être satisfaite faute de données
 * (notes non saisies, période non configurée, etc.). L'orchestrateur la
 * convertit en réponse « information indisponible ».
 */
class AssistantDataUnavailableException extends RuntimeException
{
    public function __construct(
        public readonly AssistantIntent $intent,
        string $message,
    ) {
        parent::__construct($message);
    }
}
