<?php

/**
 * Paramètres commerciaux CoriSchool.
 *
 * Le modèle économique est : année 1 = 150 000 FCFA (initial),
 * années suivantes = 70 000 FCFA (renewal). Ces montants sont la
 * seule source de vérité utilisée par les licences, paiements et
 * écrans commerciaux.
 */

return [

    'billing' => [
        'currency' => 'XOF',
        'initial_amount' => 150000,
        'renewal_amount' => 70000,

        // Seuil avant l'expiration d'une licence (en jours) au-delà duquel
        // la licence est considérée comme « expirant bientôt ».
        'expiring_soon_days' => 30,
    ],

];
