<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;

/**
 * Construit les messages consolidés (une seule évaluation regroupée par élève).
 *
 * Les messages externes sont limités à un seul segment SMS : si le détail
 * ne tient pas, une alerte concise est envoyée et le détail reste dans
 * l'espace parent.
 */
class NotificationMessageBuilder
{
    /**
     * @param  array<int, array{subject: string, title: string, score: ?float, max: float, absent: bool}>  $entries
     */
    public function inAppMessage(string $schoolName, string $studentName, array $entries): string
    {
        $lines = ['Nouvelles notes disponibles', '', $studentName, ''];

        foreach ($entries as $entry) {
            $lines[] = sprintf('%s : %s', $entry['subject'], $this->formatScore($entry));
        }

        return implode("\n", $lines);
    }

    /**
     * Build the evaluation marks parameter for Meta's WhatsApp template.
     * Keep it on one line and collapse whitespace that Meta rejects.
     *
     * @param  array<int, array{subject: string, title: string, score: ?float, max: float, absent: bool}>  $entries
     */
    public function whatsappMarks(array $entries): string
    {
        $notes = array_map(function (array $entry): string {
            $subject = preg_replace('/[\r\n\t ]+/u', ' ', $entry['subject']) ?? $entry['subject'];
            $note = sprintf('%s : %s', trim($subject), $this->formatScore($entry));

            return trim(preg_replace('/[\r\n\t ]+/u', ' ', $note) ?? $note);
        }, $entries);

        return implode(', ', $notes);
    }

    /**
     * @param  array<int, array{subject: string, title: string, score: ?float, max: float, absent: bool}>  $entries
     * @return array{message: string, detailed: bool}
     */
    public function externalMessage(
        CommunicationChannel $channel,
        string $schoolName,
        string $studentName,
        array $entries
    ): array {
        if ($channel === CommunicationChannel::Email) {
            return ['message' => $this->detailed($schoolName, $studentName, $entries), 'detailed' => true];
        }

        if ($channel === CommunicationChannel::Whatsapp) {
            return ['message' => $this->detailed($schoolName, $studentName, $entries), 'detailed' => true];
        }

        $detailed = $this->detailed($schoolName, $studentName, $entries);
        $limit = (int) config('communication.sms_max_length', 160);

        if (mb_strlen($detailed) <= $limit) {
            return ['message' => $detailed, 'detailed' => true];
        }

        return ['message' => $this->alert($schoolName, $studentName), 'detailed' => false];
    }

    /**
     * @param  array<int, array{subject: string, title: string, score: ?float, max: float, absent: bool}>  $entries
     */
    private function detailed(string $schoolName, string $studentName, array $entries): string
    {
        $notes = array_map(
            fn (array $entry) => sprintf('%s %s', $entry['subject'], $this->formatScore($entry)),
            $entries
        );

        return sprintf(
            'CoriSchool via %s%sNouvelles notes de %s : %s. Consultez votre espace parent : %s',
            $schoolName,
            "\n",
            $studentName,
            implode(', ', $notes),
            (string) config('communication.portal_url', 'corischool.com')
        );
    }

    private function alert(string $schoolName, string $studentName): string
    {
        return sprintf(
            'CoriSchool via %s : De nouvelles notes de %s sont disponibles. Consultez votre espace parent : %s',
            $schoolName,
            $studentName,
            (string) config('communication.portal_url', 'corischool.com')
        );
    }

    /**
     * @param  array{score: ?float, max: float, absent: bool}  $entry
     */
    private function formatScore(array $entry): string
    {
        if ($entry['absent']) {
            return 'Absent';
        }

        return sprintf('%s/%s', $this->number($entry['score']), $this->number($entry['max']));
    }

    private function number(?float $value): string
    {
        $value = (float) $value;

        return $value === floor($value)
            ? (string) (int) $value
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
