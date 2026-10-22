<?php

namespace App\Support;

/**
 * Offres commerciales CoriSchool (contenu marketing de la landing page).
 *
 * Ces paliers servent la présentation publique et la position commerciale ; la
 * facturation réelle repose sur la licence d'établissement (voir config/cori.php)
 * et sur les crédits SMS. Le coût des SMS reste volontairement hors abonnement
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
                'key' => 'year1',
                'name' => '1re année',
                'price' => '100 000 FCFA',
                'period' => 'par section',
                'tagline' => 'Installation, configuration et période de test incluses.',
                'featured' => true,
                'features' => [
                    'Installation et configuration complète',
                    'Période de test incluse',
                    'Toutes les fonctionnalités CoriSchool',
                    'Gestion élèves, classes, matières',
                    'Notes, bulletins, présences',
                    'Portail parents et enseignants',
                    'Frais scolaires et paiements',
                    'Export EducMaster (Bénin)',
                    'Banque d\'épreuves partagée',
                    'Jeu éducatif et compétitions',
                    'Authentification 2FA',
                    'Séparation des données par école',
                    'Support technique',
                ],
                'note' => 'Valable par section (Primaire ou Secondaire).',
            ],
            [
                'key' => 'small',
                'name' => 'Jusqu\'à 220 élèves (dès 2e année)',
                'price' => '75 000 FCFA',
                'period' => '/ an',
                'tagline' => 'Tarif forfaitaire pour petites structures.',
                'featured' => false,
                'features' => [
                    'Toutes les fonctionnalités CoriSchool',
                    'Jusqu\'à 220 élèves inclus',
                    'Pas de coût par élève supplémentaire',
                    'Idéal pour écoles primaires',
                ],
                'note' => 'Applicable à partir de la 2e année.',
            ],
            [
                'key' => 'standard',
                'name' => '221 élèves et plus (dès 2e année)',
                'price' => '400 FCFA',
                'period' => '/ élève / an',
                'tagline' => 'Tarification à l\'élève pour écoles moyennes et grandes.',
                'featured' => false,
                'features' => [
                    'Toutes les fonctionnalités CoriSchool',
                    'Facturation au nombre exact d\'élèves',
                    'Évolutif selon vos effectifs',
                ],
                'note' => 'Applicable à partir de la 2e année. 350 FCFA/élève/an (5 000–10 000), 341 FCFA/élève/an (10 000+).',
            ],
        ];
    }

    /**
     * Les frais SMS sont facturés à part : ils dépendent du volume réel
     * de messages envoyés par l'école, pas du nombre d'élèves.
     */
    public static function smsNote(): string
    {
        return 'Les canaux SMS sont inclus dans l\'abonnement ; les messages envoyés sont consommés par volume via des crédits prépayés.';
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
                'answer' => "L’installation de CoriSchool, sa configuration et la période de test coûtent 100 000 FCFA pour la première année par niveau (primaire, secondaire, etc.). À partir de la deuxième année, l’abonnement est calculé en fonction du nombre d’élèves et coûte 400 FCFA par élève et par an",
            ],
            [
                'question' => 'Comment inscrire mon école ?',
                'answer' => "L'inscription se fait sur demande de démonstration : remplissez le formulaire « Demander une démonstration », l'équipe Coriyase vous contacte pour planifier une présentation, puis l'école et le compte administrateur sont activés après validation et règlement de la licence initiale.",
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
                'answer' => "Les canaux SMS (WhatsApp) sont inclus dans l'abonnement, mais les messages envoyés sont consommés par volume via des crédits SMS. Le coût reste ainsi maîtrisé, sans consommation illimitée.",
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
