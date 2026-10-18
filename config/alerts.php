<?php

/**
 * Paramètres du système d'alertes intelligentes (phase 3).
 *
 * Les seuils sont regroupés ici afin d'être configurables sans toucher au
 * code. Les alertes sont des notifications internes agrégées : une seule
 * alerte par type et par école, jamais une par élève, afin de ne pas noyer
 * le tableau de bord sous une centaine de notifications.
 */

return [

    'thresholds' => [
        // Moyenne générale en deçà de laquelle un élève est en difficulté.
        'average_below' => 10.0,

        // Nombre minimal d'élèves en difficulté déclenchant l'alerte.
        'struggling_students_min' => 1,

        // Absences mensuelles cumulées par élève considérées comme excessives.
        'absences_min' => 3,

        // Nombre minimal d'élèves concernés déclenchant l'alerte d'absentéisme.
        'absences_students_min' => 1,

        // Période analysée pour la situation financière (« month » = mois courant).
        'finance_period' => 'month',
    ],

    /*
    |--------------------------------------------------------------------------
    | Envoi hebdomadaire
    |
    | Le message de synthèse destiné au fondateur/directeur est généré chaque
    | semaine (dimanche 16h00) afin de préparer l'allocution du lundi après le
    | drapeau. `send_email` active l'e-mail en plus de la notification in-app.
    |--------------------------------------------------------------------------
    */

    'send_email' => env('CORI_ALERTS_EMAIL', false),

    // Durée de conservation des alertes lues (jours), au-delà de laquelle
    // elles sont purgées comme les autres notifications internes.
    'retention_days' => 90,

    /*
    |--------------------------------------------------------------------------
    | Suivi des élèves en difficulté (phase 5)
    |
    | Critères configurables servant à identifier les élèves nécessitant une
    | attention particulière. Ces indicateurs sont purement scolaires : ils ne
    | constituent en aucun cas un diagnostic médical ou psychologique.
    |--------------------------------------------------------------------------
    */

    'difficulty' => [
        // Moyenne générale sous laquelle la performance est préoccupante.
        'low_average' => 10.0,

        // Baisse (en points) entre la période précédente et la période en cours.
        'drop_points' => 2.0,

        // Nombre de matières sous le seuil pour un profil « multi-difficultés ».
        'weak_subject_min' => 2,

        // Absences cumulées sur la période courante.
        'absences_min' => 3,

        // Retards cumulés sur la période courante.
        'lates_min' => 3,
    ],
];

