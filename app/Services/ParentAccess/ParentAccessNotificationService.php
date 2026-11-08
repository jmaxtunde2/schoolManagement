<?php

namespace App\Services\ParentAccess;

use App\Models\ParentGuardian;
use App\Models\School;
use App\Services\Sms\WhatsAppProviderManager;
use Throwable;

/**
 * Envoie au parent les deux modèles WhatsApp approuvés par Meta :
 *   1. verification_code_parent : le code d'accès seul (jamais mélangé à
 *      d'autres informations, exigence de Meta) ;
 *   2. espace_parent : le message d'invitation avec le bouton vers l'espace.
 */
class ParentAccessNotificationService
{
    public function __construct(private readonly WhatsAppProviderManager $whatsapp) {}

    public function configured(): bool
    {
        return $this->whatsapp->configured();
    }

    /**
     * @return array{sent: bool, sent_count: int, errors: array<int, string>}
     */
    public function sendAccessCode(ParentGuardian $parent, School $school, string $plainCode): array
    {
        if (! $this->configured()) {
            return [
                'sent' => false,
                'sent_count' => 0,
                'errors' => ['Envoi WhatsApp non configuré : aucun message envoyé.'],
            ];
        }

        if (! filled($parent->phone)) {
            return [
                'sent' => false,
                'sent_count' => 0,
                'errors' => ['Aucun numéro de téléphone renseigné.'],
            ];
        }

        $language = (string) config('services.whatsapp.parent_access_template_language', 'fr');

        $messages = [
            [
                'label' => 'code de vérification',
                'metadata' => [
                    'whatsapp' => [
                        'template' => 'verification_code_parent',
                        'language' => $language,
                        'body_parameters' => [$plainCode],
                    ],
                ],
            ],
            [
                'label' => 'espace parent',
                'metadata' => [
                    'whatsapp' => [
                        'template' => 'espace_parent',
                        'language' => $language,
                        'body_parameters' => [$parent->name, $school->name],
                        'url_parameter' => $school->slug,
                    ],
                ],
            ],
        ];

        $sent = 0;
        $errors = [];

        foreach ($messages as $message) {
            try {
                $response = $this->whatsapp->provider()->send($parent->phone, '', $message['metadata']);

                if ($response->success) {
                    $sent++;

                    continue;
                }

                $errors[] = "{$message['label']} : ".($response->error ?? "échec d'envoi.");
            } catch (Throwable $exception) {
                $errors[] = "{$message['label']} : ".$exception->getMessage();
            }
        }

        return [
            'sent' => $sent === count($messages),
            'sent_count' => $sent,
            'errors' => $errors,
        ];
    }
}
