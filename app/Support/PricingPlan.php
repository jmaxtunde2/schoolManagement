<?php

namespace App\Support;

/**
 * Offres commerciales CoriSchool.
 *
 * Les tarifs sont affichés à titre informatif : aucun module de facturation n'existe
 * à ce jour, ces offres servent la landing page et la position commerciale.
 * Le coût des SMS reste volontairement hors abonnement
 * (crédits SMS prépayés par école) pour éviter une consommation illimitée non maîtrisée.
 */
class PricingPlan
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function plans(): array
    {
        return [
            [
                'key' => 'starter',
                'name' => 'Starter',
                'price' => '1 000 FCFA',
                'period' => 'par élève / par an',
                'tagline' => 'Pour les petites écoles qui veulent abandonner le cahier de notes.',
                'featured' => false,
                'features' => [
                    'Élèves, classes et matières',
                    'Enseignants et affectations',
                    'Évaluations, notes et moyennes',
                    'Bulletins scolaires',
                    'Gestion des présences',
                    'Emploi du temps',
                    'Portail parent',
                    'Notifications et SMS',
                    'Authentification 2FA',
                    'Tableau de bord direction',
                    'Espace enseignant',
                    'Historique académique de base',
                    'Exports standards',
                ],
            ],
            [
                'key' => 'pro',
                'name' => 'Pro',
                'price' => '1 500 FCFA',
                'period' => 'par élève / par an',
                'tagline' => 'Tout Starter, plus le pilotage analytique et la gestion financière.',
                'featured' => true,
                'features' => [
                    'Tout ce que contient Starter',
                    'Statistiques avancées',
                    'Suivi des performances',
                    'Historique académique complet',
                    'Exports avancés',
                    'Gestion des frais scolaires',
                    'Paiements et reçus',
                    'Tableaux de bord analytiques',
                ],
            ],
            [
                'key' => 'enterprise',
                'name' => 'Enterprise',
                'price' => '2 500 FCFA',
                'period' => 'par élève / par an',
                'tagline' => 'Pour les groupes scolaires multi-écoles et multi-campus.',
                'featured' => false,
                'features' => [
                    'Tout ce que contient Pro',
                    'Groupes scolaires',
                    'Multi-écoles et multi-campus',
                    'Administration centralisée',
                    'Tableaux de bord consolidés',
                    'API et intégrations',
                    'Domaine personnalisé',
                    'Migration de données',
                    'Personnalisation',
                    'Support prioritaire et SLA',
                ],
            ],
        ];
    }

    /**
     * Les frais SMS sont facturés à part : ils dépendent du volume réel
     * de messages envoyés par l'école, pas du nombre d'élèves.
     */
    public static function smsNote(): string
    {
        return "Les SMS sont consommés par volume et restent séparables de l'abonnement : une école ne paie que ce qu'elle envoie.";
    }

    /**
     * Questions/réponses de la landing page.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public static function faq(): array
    {
        return [
            [
                'question' => 'Combien coûte CoriSchool ?',
                'answer' => "L'abonnement dépend du nombre d'élèves et de l'offre choisie : 1 000 FCFA par élève et par an (Starter), 1 500 FCFA (Pro) ou 2 500 FCFA (Enterprise).",
            ],
            [
                'question' => 'Comment inscrire mon école ?',
                'answer' => "Cliquez sur « Créer mon école », renseignez l'identité de l'établissement et le compte de votre administrateur. L'école et son espace de travail sont créés immédiatement.",
            ],
            [
                'question' => 'Mes enseignants ont-ils leur propre compte ?',
                'answer' => 'Oui. Chaque membre du personnel peut disposer de son propre compte : le directeur gère le personnel depuis Administration → Personnel, et les enseignants accèdent à leurs classes, notes et bulletins.',
            ],
            [
                'question' => 'Les parents peuvent-ils consulter les résultats ?',
                'answer' => "Oui. Chaque parent dispose d'un espace dédié pour consulter les notes, les bulletins, l'assiduité et l'emploi du temps de son enfant.",
            ],
            [
                'question' => 'Les SMS sont-ils inclus ?',
                'answer' => "Les canaux SMS sont inclus dans l'abonnement, mais les messages envoyés sont consommés par volume via des crédits SMS. Le coût reste ainsi maîtrisé, sans consommation illimitée.",
            ],
            [
                'question' => 'Mes données sont-elles séparées des autres écoles ?',
                'answer' => "Oui. Chaque école est cloisonnée par school_id côté serveur : élèves, enseignants, parents, notes, emplois du temps et données financières d'une école ne sont jamais accessibles depuis une autre.",
            ],
            [
                'question' => 'CoriSchool fonctionne-t-il sur téléphone ?',
                'answer' => "Oui. Les tableaux de bord, les listes et les formulaires sont adaptés au mobile, la tablette et l'ordinateur.",
            ],
        ];
    }
}
