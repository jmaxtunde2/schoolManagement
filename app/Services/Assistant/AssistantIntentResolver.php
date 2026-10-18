<?php

namespace App\Services\Assistant;

use App\Enums\AssistantIntent as Intent;

/**
 * Résolution d'une question en langage naturel vers une intention contrôlée.
 *
 * La correspondance repose sur des motifs (expressions françaises) plutôt que
 * sur un modèle de langage : aucune requête SQL n'est générée librement et le
 * jeu d'opérations possibles reste strictement borné. L'ordre des règles fait
 * primer les intentions les plus spécifiques.
 */
class AssistantIntentResolver
{
    /**
     * @var array<int, array{intent: Intent, patterns: array<int, string>}>
     */
    private const RULES = [
        ['intent' => Intent::Balance, 'patterns' => [
            '/\b(solde|benefice|situation financiere|resultat financier)\b/',
        ]],
        ['intent' => Intent::ExpensesByCategory, 'patterns' => [
            '/depense.*categorie|sortie.*categorie|categorie.*depense/',
        ]],
        ['intent' => Intent::RepeatedAbsences, 'patterns' => [
            '/(plusieurs|repetees?|nombreuses?|plus de|trois|quatre|3|4).{0,20}absences?/',
            '/absences?.{0,25}(plusieurs|repetees?|nombreuses?|carabine|trois|quatre)/',
        ]],
        ['intent' => Intent::PendingEvaluations, 'patterns' => [
            '/(enseignants?|professeurs?|profs?).{0,40}(notes?|saisi|evaluations?|en attente)/',
            '/(notes?|evaluations?).{0,25}(non saisie|pas encore|en attente|non validee)/',
        ]],
        ['intent' => Intent::StudentsBelowAverage, 'patterns' => [
            '/eleves?.{0,30}(moyenne|note).{0,30}(inferieur|moins de|sous|faible)/',
            '/moyenne.{0,20}(inferieur|moins de|sous)/',
        ]],
        ['intent' => Intent::WeakestSubjects, 'patterns' => [
            '/matieres?.{0,35}(faible|mauvais|problematique|difficile|resultat)/',
            '/matieres? (les plus faibles|faibles|en difficulte)/',
        ]],
        ['intent' => Intent::BestClass, 'patterns' => [
            '/classes?.{0,30}(meilleure|meilleur|top|plus elevee|plus haute|premiere)/',
            '/meilleure.{0,20}classe/',
        ]],
        ['intent' => Intent::WorstClass, 'patterns' => [
            '/classes?.{0,30}(plus faible|moins bonne|pire|derniere)/',
            '/(plus faible|moins bonne|pire).{0,20}classe/',
        ]],
        ['intent' => Intent::Revenue, 'patterns' => [
            '/\b(recette|recettes|revenu|revenus|encaisse|encaissement|entree|entrees)\b/',
        ]],
        ['intent' => Intent::Expenses, 'patterns' => [
            '/\b(depense|depenses|depensee|sortie|sorties|decaisse|decaissee)\b/',
        ]],
        ['intent' => Intent::StudentByClass, 'patterns' => [
            '/\beleves?.{0,30}(par classe|repartition)/',
            '/repartition.{0,20}(eleves?|effectifs?)/',
        ]],
        ['intent' => Intent::ClassAverage, 'patterns' => [
            '/moyenne.{0,20}classes?/',
            '/classes?.{0,20}moyenne/',
        ]],
        ['intent' => Intent::StudentCount, 'patterns' => [
            '/\b(eleves?|effectif|effectifs|inscrits?|scolarises?)\b/',
        ]],
        ['intent' => Intent::TeacherCount, 'patterns' => [
            '/\b(enseignants?|professeurs?|profs?)\b/',
        ]],
        ['intent' => Intent::ClassCount, 'patterns' => [
            '/\bclasses?\b/',
            '/\bsalles?\b/',
        ]],
        ['intent' => Intent::AttendanceSummary, 'patterns' => [
            '/\b(assiduite|absence|absences|retard|retards|presence|presences)\b/',
        ]],
    ];

    /**
     * @return array{intent: Intent, scope: string, threshold: float|null}
     */
    public function resolve(string $question): array
    {
        $text = $this->normalize($question);

        foreach (self::RULES as $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if (preg_match($pattern, $text) === 1) {
                    return [
                        'intent' => $rule['intent'],
                        'scope' => $this->scope($text, $rule['intent']),
                        'threshold' => $this->threshold($text),
                    ];
                }
            }
        }

        return ['intent' => Intent::Help, 'scope' => 'all', 'threshold' => null];
    }

    /** Période demandée : mois courant, année scolaire ou tout l'historique. */
    private function scope(string $text, Intent $intent): string
    {
        if (
            str_contains($text, 'ce mois')
            || str_contains($text, 'du mois')
            || str_contains($text, 'mois en cours')
            || str_contains($text, 'mois courant')
            || str_contains($text, 'ce mois ci')
        ) {
            return 'month';
        }

        if (
            str_contains($text, 'cette annee')
            || str_contains($text, 'de l annee')
            || str_contains($text, 'annee scolaire')
            || str_contains($text, 'annuel')
            || str_contains($text, 'depuis la rentree')
        ) {
            return 'year';
        }

        if (
            str_contains($text, 'ce trimestre')
            || str_contains($text, 'cette periode')
            || str_contains($text, 'ce semestre')
        ) {
            return 'period';
        }

        return match ($intent) {
            Intent::Revenue, Intent::Expenses, Intent::ExpensesByCategory,
            Intent::RepeatedAbsences, Intent::AttendanceSummary => 'month',
            default => 'all',
        };
    }

    /** Seuil numérique cité dans la question (ex. « inférieure à 10 »). */
    private function threshold(string $text): ?float
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)/', $text, $matches) === 1) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    /** Minuscules, sans accents ni ponctuation : facilite la correspondance. */
    private function normalize(string $question): string
    {
        $question = mb_strtolower($question);

        $question = strtr($question, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'õ' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ç' => 'c', 'œ' => 'oe', 'æ' => 'ae',
            '’' => "'", '‘' => "'", '´' => "'",
        ]);

        $question = str_replace(['-', "'"], ' ', $question);
        $question = preg_replace('/[^a-z0-9 ]+/', ' ', $question) ?? $question;

        return trim(preg_replace('/\s+/', ' ', $question) ?? $question);
    }
}
