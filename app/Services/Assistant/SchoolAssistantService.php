<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent as Intent;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

/**
 * Orchestrateur du CoriSchool Assistant.
 *
 * Enchaînement (voir architecture de la phase 1) :
 *   question → intention → service sécurisé → données du school_id courant →
 *   réponse structurée.
 *
 * L'assistant ne génère jamais de SQL et n'invente jamais de chiffre : lorsque
 * les données sont incomplètes, il l'indique explicitement.
 */
class SchoolAssistantService
{
    public function __construct(private AssistantIntentResolver $resolver) {}

    /**
     * @return array<string, mixed>
     */
    public function answer(User $user, string $question): array
    {
        $context = AssistantContext::for($user);
        App::instance(AssistantContext::class, $context);

        $resolved = $this->resolver->resolve($question);

        return $this->respond(
            $context,
            $resolved['intent'],
            $resolved['scope'],
            $resolved['threshold'],
        );
    }

    /**
     * Suggestions affichées à l'accueil et après chaque réponse.
     *
     * @return array<int, string>
     */
    public function suggestionsFor(User $user): array
    {
        $suggestions = [
            "Combien avons-nous d'élèves ?",
            'Quelle classe a la meilleure moyenne ?',
            'Quelles matières présentent les plus faibles résultats ?',
            "Quels enseignants n'ont pas encore saisi leurs notes ?",
        ];

        if (in_array($user->role, [Role::Admin, Role::Accountant], true)) {
            $suggestions[] = 'Combien avons-nous dépensé ce mois-ci ?';
            $suggestions[] = 'Quel est notre solde actuel ?';
        }

        $suggestions[] = 'Quels élèves ont plusieurs absences ce mois-ci ?';

        return $suggestions;
    }

    /**
     * @return array<string, mixed>
     */
    private function respond(AssistantContext $context, Intent $intent, string $scope, ?float $threshold): array
    {
        if ($intent->isFinancial() && ! $context->canViewFinance) {
            return $this->payload($context, $intent, '🔒 Accès restreint',
                "Je ne peux pas afficher les données financières : votre rôle n'y donne pas accès.",
                ['source' => null, 'available' => false]);
        }

        try {
            return match ($intent) {
                Intent::StudentCount => $this->studentCount($context),
                Intent::StudentByClass => $this->studentByClass($context),
                Intent::ClassCount => $this->classCount($context),
                Intent::TeacherCount => $this->teacherCount($context),
                Intent::StudentsBelowAverage => $this->studentsBelowAverage($context, $threshold ?? 10.0),
                Intent::BestClass => $this->bestClass($context),
                Intent::WorstClass => $this->worstClass($context),
                Intent::ClassAverage => $this->classAverage($context),
                Intent::WeakestSubjects => $this->weakestSubjects($context),
                Intent::PendingEvaluations => $this->pendingEvaluations($context),
                Intent::Revenue => $this->revenue($context, $scope),
                Intent::Expenses => $this->expenses($context, $scope),
                Intent::ExpensesByCategory => $this->expensesByCategory($context, $scope),
                Intent::Balance => $this->balance($context),
                Intent::RepeatedAbsences => $this->repeatedAbsences($context, $threshold),
                Intent::AttendanceSummary => $this->attendanceSummary($context, $scope),
                Intent::Help => $this->help($context),
            };
        } catch (AssistantDataUnavailableException $exception) {
            return $this->unavailable($context, $exception->intent, $exception->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function studentCount(AssistantContext $context): array
    {
        $stats = App::make(SchoolAnalyticsService::class)->studentCount();

        return $this->payload(
            $context,
            Intent::StudentCount,
            '👥 Effectifs',
            sprintf(
                'Votre établissement compte %d élève(s) actif(s) sur un total de %d inscrit(s).',
                $stats['active'],
                $stats['total'],
            ),
            [
                'metrics' => [
                    ['label' => 'Élèves actifs', 'value' => $stats['active']],
                    ['label' => 'Total inscrits', 'value' => $stats['total']],
                    ['label' => 'Filles', 'value' => $stats['female']],
                    ['label' => 'Garçons', 'value' => $stats['male']],
                ],
                'source' => null,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function studentByClass(AssistantContext $context): array
    {
        $rows = App::make(SchoolAnalyticsService::class)->distributionByClass();
        $total = (int) array_sum(array_column($rows, 'total'));

        return $this->payload(
            $context,
            Intent::StudentByClass,
            '🧑‍🏫 Répartition par classe',
            "Répartition des {$total} élèves actifs par classe.",
            [
                'metrics' => [
                    ['label' => 'Classes', 'value' => count($rows)],
                    ['label' => 'Élèves', 'value' => $total],
                ],
                'table' => [
                    'columns' => [
                        ['key' => 'name', 'label' => 'Classe'],
                        ['key' => 'total', 'label' => 'Effectif'],
                    ],
                    'rows' => $rows,
                ],
                'source' => null,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function classCount(AssistantContext $context): array
    {
        $count = App::make(SchoolAnalyticsService::class)->classCount();

        return $this->payload(
            $context,
            Intent::ClassCount,
            '🏫 Classes',
            "Votre établissement compte {$count} classe(s).",
            [
                'metrics' => [['label' => 'Classes', 'value' => $count]],
                'source' => null,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function teacherCount(AssistantContext $context): array
    {
        $count = App::make(SchoolAnalyticsService::class)->teacherCount();

        return $this->payload(
            $context,
            Intent::TeacherCount,
            '👨‍🏫 Enseignants',
            "Votre établissement compte {$count} enseignant(s).",
            [
                'metrics' => [['label' => 'Enseignants', 'value' => $count]],
                'source' => null,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function studentsBelowAverage(AssistantContext $context, float $threshold): array
    {
        $service = App::make(SchoolAcademicService::class);

        if ($service->period() === null) {
            throw new AssistantDataUnavailableException(Intent::StudentsBelowAverage,
                "Je ne peux pas déterminer ce résultat car aucune période scolaire n'est configurée pour l'année en cours.");
        }

        if (! $service->hasData()) {
            throw new AssistantDataUnavailableException(Intent::StudentsBelowAverage,
                "Je ne peux pas déterminer ce résultat car certaines notes n'ont pas encore été saisies ou validées.");
        }

        $rows = $service->studentsBelowAverage($threshold);

        if ($rows === []) {
            return $this->payload(
                $context,
                Intent::StudentsBelowAverage,
                '🎯 Élèves sous la moyenne',
                'Aucun élève n\'a une moyenne inférieure à '.$this->num($threshold).' / 20 pour la période en cours.',
                ['source' => $this->defaultSource($context, Intent::StudentsBelowAverage)],
            );
        }

        return $this->payload(
            $context,
            Intent::StudentsBelowAverage,
            '🎯 Élèves en difficulté',
            count($rows).' élève(s) ont une moyenne inférieure à '.$this->num($threshold).' / 20.',
            [
                'metrics' => [['label' => 'Élèves concernés', 'value' => count($rows)]],
                'table' => [
                    'columns' => [
                        ['key' => 'student', 'label' => 'Élève'],
                        ['key' => 'class', 'label' => 'Classe'],
                        ['key' => 'average', 'label' => 'Moyenne / 20'],
                    ],
                    'rows' => array_map(fn (array $row) => [
                        'student' => $row['student'],
                        'class' => $row['class'],
                        'average' => $this->num($row['average']),
                    ], $rows),
                ],
                'source' => $this->defaultSource($context, Intent::StudentsBelowAverage),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function bestClass(AssistantContext $context): array
    {
        $ranking = $this->classRankingResult($context, Intent::BestClass);

        $best = $ranking[0];

        return $this->payload(
            $context,
            Intent::BestClass,
            '🏆 Meilleure moyenne',
            "La classe {$best['class_name']} obtient la meilleure moyenne : ".$this->num($best['average']).' / 20.',
            [
                'metrics' => [
                    ['label' => 'Classe', 'value' => $best['class_name']],
                    ['label' => 'Moyenne', 'value' => $this->num($best['average']).' / 20'],
                    ['label' => 'Élèves notés', 'value' => $best['students']],
                ],
                'table' => $this->rankingTable($ranking, 8),
                'source' => $this->defaultSource($context, Intent::BestClass),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function worstClass(AssistantContext $context): array
    {
        $ranking = $this->classRankingResult($context, Intent::WorstClass);

        $worst = $ranking[array_key_last($ranking)];

        return $this->payload(
            $context,
            Intent::WorstClass,
            '📉 Classe la plus faible',
            "La classe {$worst['class_name']} présente la plus faible moyenne : ".$this->num($worst['average']).' / 20.',
            [
                'metrics' => [
                    ['label' => 'Classe', 'value' => $worst['class_name']],
                    ['label' => 'Moyenne', 'value' => $this->num($worst['average']).' / 20'],
                    ['label' => 'Élèves notés', 'value' => $worst['students']],
                ],
                'table' => $this->rankingTable($ranking, 8),
                'source' => $this->defaultSource($context, Intent::WorstClass),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function classAverage(AssistantContext $context): array
    {
        $ranking = $this->classRankingResult($context, Intent::ClassAverage);

        return $this->payload(
            $context,
            Intent::ClassAverage,
            '📊 Moyenne par classe',
            'Moyenne générale de chaque classe pour la période en cours.',
            [
                'metrics' => [['label' => 'Classes', 'value' => count($ranking)]],
                'table' => $this->rankingTable($ranking, 20),
                'source' => $this->defaultSource($context, Intent::ClassAverage),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function weakestSubjects(AssistantContext $context): array
    {
        $service = App::make(SchoolAcademicService::class);

        if ($service->period() === null) {
            throw new AssistantDataUnavailableException(Intent::WeakestSubjects,
                "Je ne peux pas déterminer ce résultat car aucune période scolaire n'est configurée pour l'année en cours.");
        }

        if (! $service->hasData()) {
            throw new AssistantDataUnavailableException(Intent::WeakestSubjects,
                "Je ne peux pas déterminer ce résultat car certaines notes n'ont pas encore été saisies ou validées.");
        }

        $rows = array_slice($service->subjectAverages(), 0, 5);

        return $this->payload(
            $context,
            Intent::WeakestSubjects,
            '📚 Matières les plus faibles',
            'Voici les matières aux résultats les plus faibles pour la période en cours.',
            [
                'table' => [
                    'columns' => [
                        ['key' => 'subject', 'label' => 'Matière'],
                        ['key' => 'average', 'label' => 'Moyenne / 20'],
                    ],
                    'rows' => array_map(fn (array $row) => [
                        'subject' => $row['subject'],
                        'average' => $this->num($row['average']),
                    ], $rows),
                ],
                'source' => $this->defaultSource($context, Intent::WeakestSubjects),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function pendingEvaluations(AssistantContext $context): array
    {
        $service = App::make(SchoolTeacherService::class);
        $rows = $service->pendingEvaluations();

        if ($rows === []) {
            return $this->payload(
                $context,
                Intent::PendingEvaluations,
                '✅ Aucune note en attente',
                'Toutes les évaluations du périmètre ont été saisies et validées.',
                ['source' => $this->defaultSource($context, Intent::PendingEvaluations)],
            );
        }

        $teachers = $service->pendingTeacherCount();

        return $this->payload(
            $context,
            Intent::PendingEvaluations,
            '📝 Évaluations en attente',
            "{$teachers} enseignant(s) ont des notes en attente : ".count($rows).' évaluation(s) à saisir ou valider.',
            [
                'metrics' => [
                    ['label' => 'Enseignants concernés', 'value' => $teachers],
                    ['label' => 'Évaluations', 'value' => count($rows)],
                ],
                'table' => [
                    'columns' => [
                        ['key' => 'teacher', 'label' => 'Enseignant'],
                        ['key' => 'class', 'label' => 'Classe'],
                        ['key' => 'subject', 'label' => 'Matière'],
                        ['key' => 'status', 'label' => 'Statut'],
                    ],
                    'rows' => array_slice($rows, 0, 12),
                ],
                'source' => $this->defaultSource($context, Intent::PendingEvaluations),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function revenue(AssistantContext $context, string $scope): array
    {
        $service = App::make(SchoolFinanceService::class);
        $summary = $service->summary($scope);

        return $this->payload(
            $context,
            Intent::Revenue,
            '💰 Recettes',
            "Les recettes s'élèvent à ".$this->money($summary['revenue']).' '.$this->scopeLabel($context, $scope).'.',
            [
                'metrics' => $this->financeMetrics($summary),
                'source' => $this->defaultSource($context, Intent::Revenue),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function expenses(AssistantContext $context, string $scope): array
    {
        $service = App::make(SchoolFinanceService::class);
        $summary = $service->summary($scope);
        $categories = $service->expensesByCategory($scope);

        return $this->payload(
            $context,
            Intent::Expenses,
            '💸 Dépenses',
            "Les dépenses s'élèvent à ".$this->money($summary['expenses']).' '.$this->scopeLabel($context, $scope).'.',
            [
                'metrics' => $this->financeMetrics($summary),
                'table' => $categories === [] ? null : [
                    'columns' => [
                        ['key' => 'label', 'label' => 'Catégorie'],
                        ['key' => 'total', 'label' => 'Montant'],
                    ],
                    'rows' => array_map(fn (array $row) => [
                        'label' => $row['label'],
                        'total' => $this->money($row['total']),
                    ], $categories),
                ],
                'source' => $this->defaultSource($context, Intent::Expenses),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function expensesByCategory(AssistantContext $context, string $scope): array
    {
        $rows = App::make(SchoolFinanceService::class)->expensesByCategory($scope);
        $total = (int) array_sum(array_column($rows, 'total'));

        if ($rows === []) {
            return $this->payload(
                $context,
                Intent::ExpensesByCategory,
                '💸 Dépenses par catégorie',
                'Aucune dépense enregistrée '.$this->scopeLabel($context, $scope).'.',
                ['source' => $this->defaultSource($context, Intent::ExpensesByCategory)],
            );
        }

        return $this->payload(
            $context,
            Intent::ExpensesByCategory,
            '💸 Dépenses par catégorie',
            'Répartition de '.$this->money($total).' de dépenses '.$this->scopeLabel($context, $scope).'.',
            [
                'metrics' => [['label' => 'Total', 'value' => $this->money($total)]],
                'table' => [
                    'columns' => [
                        ['key' => 'label', 'label' => 'Catégorie'],
                        ['key' => 'total', 'label' => 'Montant'],
                    ],
                    'rows' => array_map(fn (array $row) => [
                        'label' => $row['label'],
                        'total' => $this->money($row['total']),
                    ], $rows),
                ],
                'source' => $this->defaultSource($context, Intent::ExpensesByCategory),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function balance(AssistantContext $context): array
    {
        $summary = App::make(SchoolFinanceService::class)->summary('all');

        return $this->payload(
            $context,
            Intent::Balance,
            '🧮 Situation financière',
            'Le solde actuel (recettes − dépenses) est de '.$this->money($summary['balance']).'.',
            [
                'metrics' => $this->financeMetrics($summary),
                'source' => $this->defaultSource($context, Intent::Balance),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function repeatedAbsences(AssistantContext $context, ?float $threshold): array
    {
        $min = $threshold !== null && $threshold >= 2 ? (int) $threshold : 3;
        $service = App::make(SchoolAttendanceService::class);
        $rows = $service->repeatedAbsences($min, now()->startOfMonth(), now()->endOfMonth());

        if ($rows === []) {
            return $this->payload(
                $context,
                Intent::RepeatedAbsences,
                '✅ Assiduité',
                "Aucun élève n'a enregistré au moins {$min} absences ce mois-ci.",
                ['source' => $context->scopeNote()],
            );
        }

        return $this->payload(
            $context,
            Intent::RepeatedAbsences,
            '🔴 Élèves peu assidus',
            count($rows)." élève(s) ont accumulé au moins {$min} absences ce mois-ci.",
            [
                'metrics' => [['label' => 'Élèves concernés', 'value' => count($rows)]],
                'table' => [
                    'columns' => [
                        ['key' => 'student', 'label' => 'Élève'],
                        ['key' => 'class', 'label' => 'Classe'],
                        ['key' => 'absences', 'label' => 'Absences'],
                    ],
                    'rows' => array_slice($rows, 0, 12),
                ],
                'source' => $context->scopeNote(),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function attendanceSummary(AssistantContext $context, string $scope): array
    {
        [$from, $to] = $this->attendanceRange($context, $scope);
        $summary = App::make(SchoolAttendanceService::class)->summary($from, $to);

        return $this->payload(
            $context,
            Intent::AttendanceSummary,
            '📋 Assiduité',
            'Sur '.$this->scopeLabel($context, $scope).", {$summary['absences']} absence(s) et {$summary['retards']} retard(s) ont été enregistrés.",
            [
                'metrics' => [
                    ['label' => 'Absences', 'value' => $summary['absences']],
                    ['label' => 'Retards', 'value' => $summary['retards']],
                    ['label' => 'Minutes de retard', 'value' => $summary['delay_minutes']],
                ],
                'source' => $context->scopeNote(),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function help(AssistantContext $context): array
    {
        return $this->payload(
            $context,
            Intent::Help,
            '👋 Bonjour',
            "Je suis l'assistant de pilotage de CoriSchool. Posez-moi une question sur les effectifs, les résultats, les enseignants, les absences ou les finances de votre établissement.",
            [
                'list' => $this->suggestionsFor($context->user),
                'source' => null,
            ],
        );
    }

    /**
     * @return array<int, array{class_id: int, class_name: string, average: float, students: int}>
     *
     * @throws AssistantDataUnavailableException
     */
    private function classRankingResult(AssistantContext $context, Intent $intent): array
    {
        $service = App::make(SchoolAcademicService::class);

        if ($service->period() === null) {
            throw new AssistantDataUnavailableException($intent,
                "Je ne peux pas déterminer ce résultat car aucune période scolaire n'est configurée pour l'année en cours.");
        }

        if (! $service->hasData()) {
            throw new AssistantDataUnavailableException($intent,
                "Je ne peux pas déterminer ce résultat car certaines notes n'ont pas encore été saisies ou validées.");
        }

        $ranking = $service->classRanking();

        if ($ranking === []) {
            throw new AssistantDataUnavailableException($intent,
                "Aucune moyenne n'a encore pu être calculée pour la période en cours.");
        }

        return $ranking;
    }

    /**
     * @param  array<int, array{class_name: string, average: float}>  $ranking
     * @return array{columns: array<int, array{key: string, label: string}>, rows: array<int, array<string, string>>}
     */
    private function rankingTable(array $ranking, int $limit): array
    {
        $rows = array_map(fn (array $row) => [
            'class_name' => $row['class_name'],
            'average' => $this->num($row['average']).' / 20',
        ], array_slice($ranking, 0, $limit));

        return [
            'columns' => [
                ['key' => 'class_name', 'label' => 'Classe'],
                ['key' => 'average', 'label' => 'Moyenne'],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{revenue: int, expenses: int, balance: int}  $summary
     * @return array<int, array{label: string, value: string, tone: string|null}>
     */
    private function financeMetrics(array $summary): array
    {
        return [
            ['label' => 'Recettes', 'value' => $this->money($summary['revenue']), 'tone' => null],
            ['label' => 'Dépenses', 'value' => $this->money($summary['expenses']), 'tone' => null],
            [
                'label' => 'Solde',
                'value' => $this->money($summary['balance']),
                'tone' => $summary['balance'] >= 0 ? 'positive' : 'negative',
            ],
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function attendanceRange(AssistantContext $context, string $scope): array
    {
        if ($scope === 'year' && $context->academicYear) {
            return [
                Carbon::parse($context->academicYear->starts_on)->startOfDay(),
                now()->endOfDay(),
            ];
        }

        if ($scope === 'period' && $context->currentPeriod?->starts_at && $context->currentPeriod?->ends_at) {
            return [
                Carbon::parse($context->currentPeriod->starts_at)->startOfDay(),
                Carbon::parse($context->currentPeriod->ends_at)->endOfDay(),
            ];
        }

        return [now()->startOfMonth(), now()->endOfMonth()];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function payload(AssistantContext $context, Intent $intent, string $headline, string $text, array $options = []): array
    {
        return [
            'intent' => $intent->value,
            'headline' => $headline,
            'text' => $text,
            'metrics' => $options['metrics'] ?? [],
            'table' => $options['table'] ?? null,
            'list' => $options['list'] ?? null,
            'source' => array_key_exists('source', $options) ? $options['source'] : $this->defaultSource($context, $intent),
            'available' => $options['available'] ?? true,
            'suggestions' => $options['suggestions'] ?? $this->suggestionsFor($context->user),
        ];
    }

    /** @return array<string, mixed> */
    private function unavailable(AssistantContext $context, Intent $intent, string $message): array
    {
        return $this->payload($context, $intent, 'ℹ️ Information indisponible', $message, [
            'source' => null,
            'available' => false,
        ]);
    }

    private function defaultSource(AssistantContext $context, Intent $intent): ?string
    {
        $parts = [];

        $academic = in_array($intent, [
            Intent::StudentsBelowAverage,
            Intent::BestClass,
            Intent::WorstClass,
            Intent::ClassAverage,
            Intent::WeakestSubjects,
            Intent::PendingEvaluations,
        ], true);

        if ($academic) {
            $label = $context->academicYear
                ? "l'année scolaire {$context->academicYear->name}"
                : "l'année en cours";

            if ($context->currentPeriod) {
                $label .= " — {$context->currentPeriod->name}";
            }

            $parts[] = "Données basées sur les évaluations validées de {$label}.";
        }

        if ($intent->isFinancial()) {
            $parts[] = 'Données financières enregistrées dans CoriSchool.';
        }

        if ($context->scopeNote()) {
            $parts[] = $context->scopeNote();
        }

        return $parts === [] ? null : implode(' ', $parts);
    }

    private function scopeLabel(AssistantContext $context, string $scope): string
    {
        return match ($scope) {
            'month' => 'ce mois-ci ('.$this->monthLabel(now()).')',
            'year' => $context->academicYear
                ? "sur l'année scolaire {$context->academicYear->name}"
                : 'sur l\'année en cours',
            'period' => $context->currentPeriod
                ? "sur la période {$context->currentPeriod->name}"
                : 'sur la période en cours',
            default => 'au total',
        };
    }

    private function monthLabel(Carbon $date): string
    {
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin',
            7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];

        return $months[$date->month].' '.$date->year;
    }

    private function money(int $value): string
    {
        return number_format($value, 0, ',', ' ').' FCFA';
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }
}
