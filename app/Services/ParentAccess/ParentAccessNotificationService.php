<?php

namespace App\Services\ParentAccess;

use App\Models\ParentGuardian;
use App\Models\School;
use App\Services\Sms\SmsManager;
use App\Services\Sms\WhatsAppProviderManager;
use Throwable;

/**
 * Envoie le code d'accès au parent.
 *
 * Canal par défaut : SMS via le fournisseur actif (SMS_PROVIDER=emc).
 * Le canal WhatsApp reste disponible via PARENT_ACCESS_CHANNEL=whatsapp et
 * conserve les deux modèles approuvés (code de vérification + espace parent).
 */
class ParentAccessNotificationService
{
    public function __construct(
        private readonly WhatsAppProviderManager $whatsapp,
        private readonly SmsManager $sms,
    ) {}

    public function channel(): string
    {
        $channel = strtolower(trim((string) config('corischool.parent_access.channel', 'sms')));

        return $channel === 'whatsapp' ? 'whatsapp' : 'sms';
    }

    public function label(): string
    {
        return $this->channel() === 'whatsapp' ? 'WhatsApp' : 'SMS';
    }

    public function configured(): bool
    {
        return $this->channel() === 'whatsapp'
            ? $this->whatsapp->configuredFor($this->driver())
            : $this->smsConfigured();
    }

    private function driver(): ?string
    {
        $driver = config('corischool.parent_access.whatsapp_provider');

        return filled($driver) ? (string) $driver : null;
    }

    /**
     * @return array{sent: bool, sent_count: int, errors: array<int, string>}
     */
    public function sendAccessCode(ParentGuardian $parent, School $school, string $plainCode): array
    {
        return $this->channel() === 'whatsapp'
            ? $this->sendWhatsAppAccessCode($parent, $school, $plainCode)
            : $this->sendSmsAccessCode($parent, $school, $plainCode);
    }

    /**
     * @return array{sent: bool, sent_count: int, errors: array<int, string>}
     */
    private function sendSmsAccessCode(ParentGuardian $parent, School $school, string $plainCode): array
    {
        if (! $this->smsConfigured()) {
            return [
                'sent' => false,
                'sent_count' => 0,
                'errors' => ['Envoi SMS non configuré : aucun message envoyé.'],
            ];
        }

        if (! filled($parent->phone)) {
            return [
                'sent' => false,
                'sent_count' => 0,
                'errors' => ['Aucun numéro de téléphone renseigné.'],
            ];
        }

        try {
            $response = $this->sms->provider()->send(
                $parent->phone,
                $this->smsMessage($school, $plainCode)
            );

            if ($response->success) {
                return [
                    'sent' => true,
                    'sent_count' => 1,
                    'errors' => [],
                ];
            }

            return [
                'sent' => false,
                'sent_count' => 0,
                'errors' => ['SMS : '.($response->error ?? "échec d'envoi.")],
            ];
        } catch (Throwable $exception) {
            return [
                'sent' => false,
                'sent_count' => 0,
                'errors' => ['SMS : '.$exception->getMessage()],
            ];
        }
    }

    /**
     * @return array{sent: bool, sent_count: int, errors: array<int, string>}
     */
    private function sendWhatsAppAccessCode(ParentGuardian $parent, School $school, string $plainCode): array
    {
        if (! $this->whatsapp->configuredFor($this->driver())) {
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
                $response = $this->whatsapp->providerFor($this->driver())->send($parent->phone, '', $message['metadata']);

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

    private function smsConfigured(): bool
    {
        return match (strtolower((string) config('services.sms.provider', 'emc'))) {
            'emc' => (bool) config('services.sms.emc.enabled', true)
                && (
                    filled(config('services.sms.emc.api_key'))
                    || (filled(config('services.sms.emc.username')) && filled(config('services.sms.emc.password')))
                ),
            'multitexter' => filled(config('services.sms.multitexter.email'))
                && filled(config('services.sms.multitexter.password')),
            'clicksend' => filled(config('services.sms.clicksend.username'))
                && filled(config('services.sms.clicksend.api_key')),
            'esms_africa' => filled(config('services.sms.token')),
            'whatsapp' => $this->whatsapp->configured(),
            default => true,
        };
    }

    private function smsMessage(School $school, string $plainCode): string
    {
        $template = (string) config(
            'corischool.parent_access.sms_message',
            'CoriSchool : votre accès parent : {code}. Visitez votre espace ici : {url}.'
        );

        return str_replace(
            ['{code}', '{url}'],
            [$plainCode, $this->smsUrl($school)],
            $template
        );
    }

    private function smsUrl(School $school): string
    {
        $template = (string) config(
            'corischool.parent_access.sms_url',
            'https://corischool.com/ecole/{slug}'
        );

        return str_replace('{slug}', $school->slug, $template);
    }
}
