<?php

namespace App\Services\Mail;

use App\Models\School;
use App\Models\SchoolMailSetting;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class SchoolMailService
{
    public function send(
        School $school,
        string $to,
        ?string $recipientName,
        string $subject,
        string $message
    ): void {
        $settings = $school->mailSettings()->first();

        if (! $settings instanceof SchoolMailSetting) {
            throw new RuntimeException(
                sprintf(
                    'Aucune configuration SMTP n\'est définie pour l\'école "%s".',
                    $school->name
                )
            );
        }

        if (! $settings->is_active) {
            throw new RuntimeException(
                sprintf(
                    'La configuration SMTP de l\'école "%s" est désactivée.',
                    $school->name
                )
            );
        }

        $requiredFields = [
            'host' => 'Serveur SMTP',
            'from_address' => 'Adresse d\'expédition',
            'from_name' => 'Nom de l\'expéditeur',
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($settings->{$field})) {
                throw new RuntimeException(
                    sprintf(
                        'La configuration SMTP de l\'école "%s" est incomplète : %s.',
                        $school->name,
                        $label
                    )
                );
            }
        }

        $mailerName = 'school_'.$school->id.'_'.uniqid();

        config()->set("mail.mailers.{$mailerName}", [
            'transport' => $settings->mailer ?? 'smtp',
            'host' => $settings->host,
            'port' => (int) $settings->port,
            'username' => $settings->username,
            'password' => $settings->password,
            'encryption' => $settings->encryption ?: null,
            'timeout' => null,
            'local_domain' => null,
            'from' => [
                'address' => $settings->from_address,
                'name' => $settings->from_name,
            ],
            'reply_to' => $settings->reply_to
                ? [
                    'address' => $settings->reply_to,
                    'name' => $settings->from_name,
                ]
                : null,
        ]);

        $previousDefault = config('mail.default');
        config()->set('mail.default', $mailerName);

        try {
            $mailable = new class($subject, $message) extends \Illuminate\Mail\Mailable {
                public function __construct(
                    public string $subjectLine,
                    public string $bodyText
                ) {
                    $this->subject($subjectLine);
                }

                public function build(): self
                {
                    return $this->html($this->bodyText);
                }
            };

            $mailable->to($to, $recipientName ?? $to)
                ->from($settings->from_address, $settings->from_name)
                ->subject($subject);

            if ($settings->reply_to) {
                $mailable->replyTo($settings->reply_to, $settings->from_name);
            }

            Mail::mailer($mailerName)->send($mailable);
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Échec de l\'envoi via le SMTP de l\'école "%s" : %s',
                    $school->name,
                    $this->sanitizeError($exception->getMessage())
                ),
                0,
                $exception
            );
        } finally {
            config()->set('mail.default', $previousDefault);
        }
    }

    protected function sanitizeError(string $message): string
    {
        return preg_replace(
            '/(password|pass|PWD|Authorization: Basic )[\s\S]*?(?=\b|$)/i',
            '[masqué]',
            $message
        ) ?: $message;
    }
}
