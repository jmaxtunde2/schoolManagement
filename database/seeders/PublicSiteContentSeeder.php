<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PublicSiteContentSeeder extends Seeder
{
    private const SLUG = 'csp-bilingue-saint-padre-pio';

    public function run(): void
    {
        $school = School::withoutGlobalScopes()->where('slug', self::SLUG)->first();

        if (! $school) {
            $this->command?->error("École introuvable (slug: " . self::SLUG . ")");
            return;
        }

        $setting = SchoolSetting::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->firstOrNew();

        $publicSite = [
            'mission' => 'Former des citoyens responsables, critiques et engagés, capables de s\'adapter aux défis du monde moderne tout en cultivant les valeurs humaines fondamentales.',
            'vision' => 'Devenir un établissement de référence en Afrique de l\'Ouest, reconnu pour l\'excellence académique, l\'innovation pédagogique et l\'épanouissement personnel de chaque élève.',
            'values' => [
                'Excellence',
                'Intégrité',
                'Respect',
                'Solidarité',
                'Innovation',
            ],
            'features' => [
                ['title' => 'Programme bilingue français-anglais', 'description' => 'Immersion progressive dès la 6ème pour une maîtrise opérationnelle des deux langues.'],
                ['title' => 'Sciences et technologies', 'description' => 'Laboratoires équipés, initiation au codage et robotique dès le collège.'],
                ['title' => 'Accompagnement personnalisé', 'description' => 'Suivi individualisé, tutorat et classes à effectifs réduits pour la réussite de tous.'],
                ['title' => 'Vie scolaire épanouissante', 'description' => 'Clubs, sport, arts, voyages éducatives et engagement citoyen.'],
                ['title' => 'Orientation et insertion', 'description' => 'Conseil en orientation, stages en entreprise et réseau d\'anciens élèves actif.'],
                ['title' => 'Cadre sécurisé et verdoyant', 'description' => 'Infrastructures modernes, espaces verts, restauration équilibrée et transport scolaire.'],
            ],
            'results' => [
                ['title' => '92 % de réussite au BAC', 'note' => 'Session 2024 — toutes séries confondues'],
                ['title' => '95 % de réussite au BEPC', 'note' => 'Résultats officiels du Ministère de l\'Enseignement Secondaire'],
            ],
            'tuition_currency' => 'FCFA',
            'tuition_note' => 'Paiement en trois tranches possible (septembre, janvier, avril). Réduction de 10 % pour le 2ème enfant, 15 % pour le 3ème et suivants.',
            'tuition_fees' => [
                ['label' => "Frais d'inscription (nouveaux élèves)", 'amount' => '50 000', 'note' => 'Non remboursables, payables à l\'inscription'],
                ['label' => 'Scolarité annuelle - 6ème / 5ème', 'amount' => '350 000', 'note' => 'Manuels et fournitures de base inclus'],
                ['label' => 'Scolarité annuelle - 4ème / 3ème', 'amount' => '380 000', 'note' => 'Manuels, fournitures et préparation aux examens inclus'],
                ['label' => 'Uniforme complet', 'amount' => '25 000', 'note' => 'Obligatoire : polo, pantalon/jupe, sweat, chaussettes'],
                ['label' => 'Transport scolaire (aller-retour)', 'amount' => '120 000', 'note' => 'Par an, selon zone de desserte'],
                ['label' => 'Restauration (déjeuner)', 'amount' => '180 000', 'note' => 'Par an, menus équilibrés validés par nutritionniste'],
            ],
            'curriculum_intro' => 'Notre programme suit le référentiel national du Ministère de l\'Éducation, enrichi par une approche bilingue et des modules transversaux (citoyenneté, numérique, développement durable).',
            'curriculum' => [
                ['title' => '6ème - Cycle d\'adaptation', 'description' => 'Français, Anglais, Mathématiques, SVT, Physique-Chimie, Histoire-Géographie, EPS, Arts plastiques, Education musicale, Technologie, Vie de classe. Approfondissement des acquis du primaire et découverte des disciplines du secondaire.'],
                ['title' => '5ème - Cycle central', 'description' => 'Mêmes disciplines + Latin (option). Consolidation des fondamentaux, initiation à la démarche scientifique et au raisonnement abstrait. Début de l\'orientation progressive.'],
                ['title' => '4ème - Cycle central', 'description' => 'Mêmes disciplines + Latin (option). Approfondissement des connaissances, développement de l\'autonomie et de l\'esprit critique. Projet d\'orientation individualisé.'],
                ['title' => '3ème - Cycle d\'orientation', 'description' => 'Mêmes disciplines + Latin (option). Préparation intensive au BEPC (Brevet d\'Études du Premier Cycle). Bilan d\'orientation et choix de la voie (générale, technologique, professionnelle).'],
                ['title' => 'Seconde - Détermination', 'description' => 'Tronc commun : Français, Anglais, Mathématiques, SVT, Physique-Chimie, Histoire-Géographie, EPS, EMC, Numérique. Enseignements de spécialité au choix (3) : Maths, Physique-Chimie, SVT, SES, HGGSP, LLCE Anglais, NSI.'],
                ['title' => 'Première - Spécialisation', 'description' => 'Tronc commun réduit. 3 spécialités conservées (ou 2 + option). Français (épreuve anticipée du BAC). Accompagnement personnalisé à l\'orientation post-bac.'],
                ['title' => 'Terminale - Terminal', 'description' => '2 spécialités conservées. Philosophie, Grand Oral, Épreuves écrites de spécialité. Préparation intensive au Baccalauréat. Dossier Parcoursup / orientation supérieur.'],
            ],
            'calendar_intro' => 'Le calendrier scolaire suit les directives du Ministère de l\'Éducation. Les dates indiquées sont prévisionnelles et peuvent être ajustées selon les arrêtés officiels.',
            'calendar' => [
                ['label' => 'Rentrée administrative (personnel)', 'date' => 'Lundi 1er septembre 2026', 'description' => 'Réunion pédagogique, préparation des classes et dossiers'],
                ['label' => 'Rentrée des élèves - 6ème / 2nde', 'date' => 'Lundi 8 septembre 2026', 'description' => 'Accueil à 7h30, remise des emplois du temps, présentation de l\'équipe'],
                ['label' => 'Rentrée des élèves - Autres niveaux', 'date' => 'Mardi 9 septembre 2026', 'description' => 'Reprise des cours selon emploi du temps normal'],
                ['label' => 'Réunion parents-professeurs (1er trimestre)', 'date' => 'Samedi 17 octobre 2026', 'description' => 'Bilan d\'intégration et remise des bulletins de mi-trimestre'],
                ['label' => 'Congés de la Toussaint', 'date' => 'Samedi 25 octobre → Dimanche 2 novembre 2026', 'description' => 'Reprise le lundi 3 novembre'],
                ['label' => 'Conseil de classe 1er trimestre', 'date' => 'Vendredi 12 décembre 2026', 'description' => 'Délibérations et orientation'],
                ['label' => 'Congés de Noël', 'date' => 'Samedi 20 décembre 2026 → Dimanche 4 janvier 2027', 'description' => 'Reprise le lundi 5 janvier 2027'],
                ['label' => 'Remise des bulletins 1er trimestre', 'date' => 'Vendredi 9 janvier 2027', 'description' => 'Remise aux parents lors de la réunion'],
                ['label' => 'Semaine de l\'orientation (3ème/Terminale)', 'date' => 'Lundi 19 → Vendredi 23 janvier 2027', 'description' => 'Forums métiers, témoignages d\'anciens, visite d\'établissements supérieurs'],
                ['label' => 'Congés de février', 'date' => 'Samedi 13 février → Dimanche 21 février 2027', 'description' => 'Reprise le lundi 22 février'],
                ['label' => 'Conseil de classe 2ème trimestre', 'date' => 'Vendredi 19 mars 2027', 'description' => 'Délibérations et décisions d\'orientation'],
                ['label' => 'Épreuves communes 1er groupe (3ème/Terminale)', 'date' => 'Lundi 29 mars → Vendredi 2 avril 2027', 'description' => 'Examens blancs dans conditions réelles'],
                ['label' => 'Congés de Pâques', 'date' => 'Samedi 3 avril → Dimanche 18 avril 2027', 'description' => 'Reprise le lundi 19 avril'],
                ['label' => 'Fête du travail', 'date' => 'Samedi 1er mai 2027', 'description' => 'Jour férié chômé'],
                ['label' => 'Conseil de classe 3ème trimestre', 'date' => 'Vendredi 11 juin 2027', 'description' => 'Délibérations finales et passage en classe supérieure'],
                ['label' => 'Examens officiels - BEPC', 'date' => 'Lundi 14 → Vendredi 18 juin 2027', 'description' => 'Épreuves écrites et orales'],
                ['label' => 'Examens officiels - BAC', 'date' => 'Lundi 21 juin → Vendredi 2 juillet 2027', 'description' => 'Épreuves écrites, pratiques et orales'],
                ['label' => 'Remise des bulletins annuels', 'date' => 'Vendredi 2 juillet 2027', 'description' => 'Dernière journée de classe, remise des bulletins et certificats'],
                ['label' => 'Cérémonie de remise des diplômes', 'date' => 'Samedi 10 juillet 2027', 'description' => 'Célébration des lauréats BEPC et BAC, discours, remise des prix'],
                ['label' => 'Début des vacances d\'été', 'date' => 'Lundi 12 juillet 2027', 'description' => 'Bonne vacances à tous ! Reprise prévue début septembre 2027'],
            ],
        ];

        $setting->fill([
            'school_id' => $school->id,
            'school_name' => $school->name,
            'options' => array_merge($setting->options ?? [], ['public_site' => $publicSite]),
        ])->save();

        $this->command?->info('Contenu du site public (Vie scolaire) ajouté pour ' . $school->name);
    }
}