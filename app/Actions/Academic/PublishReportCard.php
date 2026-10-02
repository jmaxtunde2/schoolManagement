<?php

namespace App\Actions\Academic;

use App\Enums\ReportCardStatus;
use App\Models\ParentGuardian;
use App\Models\ReportCard;
use App\Services\AuditService;
use App\Services\UserNotifications\UserNotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PublishReportCard
{
    public function __construct(
        private AuditService $audit,
        private UserNotificationService $notifications,
    ) {
    }

    public function handle(ReportCard $reportCard, int $publisherId): ReportCard
    {
        if ($reportCard->status !== ReportCardStatus::Generated) {
            throw new RuntimeException('Seul un bulletin généré peut être publié.');
        }

        $reportCard->update([
            'status' => ReportCardStatus::Published,
            'published_at' => now(),
            'published_by' => $publisherId,
        ]);

        $this->audit->log('report_card.published', $reportCard, [
            'student_id' => $reportCard->student_id,
            'academic_period_id' => $reportCard->academic_period_id,
            'version' => $reportCard->version,
        ]);

        $guardianUsers = ParentGuardian::withoutGlobalScopes()
            ->where('school_id', $reportCard->school_id)
            ->whereHas('students', fn ($students) => $students->whereKey($reportCard->student_id))
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        $periodName = $reportCard->period?->name ?? 'la période scolaire';
        foreach ($guardianUsers as $guardianUser) {
            $this->notifications->send(
                user: $guardianUser,
                type: 'report_card.published',
                title: 'Bulletin scolaire disponible',
                message: "Le bulletin de votre enfant pour {$periodName} est maintenant disponible.",
                data: [
                    'report_card_id' => $reportCard->id,
                    'student_id' => $reportCard->student_id,
                    'url' => route('parent.report-cards.show', $reportCard->id),
                ],
            );
        }

        return $reportCard->fresh(['student', 'classRoom', 'period', 'items']);
    }
}