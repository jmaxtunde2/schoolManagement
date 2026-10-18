const labels = {
    'report_card.generated': 'Bulletin généré',
    'report_card.regenerated': 'Bulletin régénéré',
    'report_card.published': 'Bulletin publié',
    'report_card.comments_updated': 'Appréciations du bulletin modifiées',
    'report_card.pdf_generated': 'PDF du bulletin généré',
    'report_card.downloaded': 'Bulletin téléchargé',
    'attendance.created': 'Présence enregistrée',
    'attendance.updated': 'Présence modifiée',
    'attendance.justified': 'Absence justifiée',
    'attendance.justification_submitted': 'Justification d’absence soumise',
    'evaluation.submitted': 'Évaluation soumise',
    'evaluation.validated': 'Évaluation validée',
    'evaluation.returned': 'Évaluation renvoyée',
    'staff.created': 'Membre du personnel créé',
    'staff.updated': 'Membre du personnel modifié',
    'staff.access_updated': 'Accès du personnel modifié',
    'staff.deleted': 'Membre du personnel supprimé',
    'user.created': 'Utilisateur créé',
    'user.access_updated': 'Accès utilisateur modifié',
    'settings.updated': 'Configuration modifiée',
    'contribution.saved': 'Contribution enregistrée',
    'contribution.deleted': 'Contribution supprimée',
    'expense.created': 'Dépense créée',
    'expense.approved': 'Dépense approuvée',
    'expense.rejected': 'Dépense rejetée',
    'expense.deleted': 'Dépense supprimée',
    'salary.saved': 'Fiche de paie enregistrée',
    'sale.created': 'Vente enregistrée',
    'sale.deleted': 'Vente supprimée',
    'preinscription.approved': 'Préinscription approuvée',
    'preinscription.rejected': 'Préinscription rejetée',
    'academic_period.created': 'Période académique créée',
    'academic_period.updated': 'Période académique modifiée',
    'academic_period.deleted': 'Période académique supprimée',
    'academic_period.toggled': 'Période académique activée/désactivée',
    'academic_year.created': 'Année scolaire créée',
    'academic_year.updated': 'Année scolaire modifiée',
    'academic_year.deleted': 'Année scolaire supprimée',
    'academic_year.toggled': 'Année scolaire activée/désactivée',
};

export function auditActionLabel(action) {
    if (!action) {
        return 'Action';
    }

    if (labels[action]) {
        return labels[action];
    }

    const [entity, verb] = action.split('.');

    const entities = {
        report_card: 'Bulletin',
        attendance: 'Présence',
        evaluation: 'Évaluation',
        staff: 'Personnel',
        user: 'Utilisateur',
        contribution: 'Contribution',
        expense: 'Dépense',
        salary: 'Paie',
        sale: 'Vente',
        preinscription: 'Préinscription',
        student: 'Élève',
        teacher: 'Enseignant',
        academic_period: 'Période académique',
        academic_year: 'Année scolaire',
    };

    const verbs = {
        created: 'créé',
        updated: 'modifié',
        deleted: 'supprimé',
        approved: 'approuvé',
        rejected: 'rejeté',
        validated: 'validé',
        returned: 'renvoyé',
        submitted: 'soumis',
        published: 'publié',
        generated: 'généré',
        toggled: 'activé/désactivé',
        saved: 'enregistré',
        justified: 'justifié',
    };

    const entityLabel = entities[entity] ?? entity;
    const verbLabel = verbs[verb] ?? verb;

    return `${entityLabel} ${verbLabel}`.trim();
}

export default auditActionLabel;
