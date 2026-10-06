import { Head } from '@inertiajs/react';
import {
    Building2,
    CalendarDays,
    GraduationCap,
    Info,
    Receipt,
    School as SchoolIcon,
    TrendingUp,
    UserCog,
    Users,
    WalletCards,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';

const statusTones = {
    paid: 'green',
    pending: 'amber',
};

const statusLabels = {
    paid: 'Réglée',
    pending: 'En attente',
};

export default function ManagementDashboard({
    role,
    role_label: roleLabel,
    school,
    currentAcademicYear: currentYear,
    stats,
    billing,
}) {
    const money = (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F`;

    const cards = [
        {
            label: 'Élèves',
            value: stats.students,
            icon: GraduationCap,
            cardClass: 'bg-gradient-to-br from-emerald-500 to-green-600',
        },
        {
            label: 'Enseignants',
            value: stats.teachers,
            icon: Users,
            cardClass: 'bg-gradient-to-br from-teal-500 to-cyan-600',
        },
        {
            label: 'Classes',
            value: stats.classes,
            icon: SchoolIcon,
            cardClass: 'bg-gradient-to-br from-sky-500 to-blue-600',
        },
        {
            label: 'Personnel',
            value: stats.staff,
            icon: UserCog,
            cardClass: 'bg-gradient-to-br from-violet-500 to-purple-600',
        },
        {
            label: 'Parents',
            value: stats.parents,
            icon: Users,
            cardClass: 'bg-gradient-to-br from-amber-500 to-orange-600',
        },
        {
            label: 'Créneaux',
            value: stats.timetable_slots,
            icon: CalendarDays,
            cardClass: 'bg-gradient-to-br from-slate-500 to-slate-600',
        },
    ];

    return (
        <AuthenticatedLayout title="Tableau de bord">
            <Head title="Tableau de bord" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-emerald-100">
                                <Building2 className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    {roleLabel}
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                {school.name}
                            </h1>

                            <p className="mt-1 text-sm text-emerald-50">
                                {currentYear
                                    ? `Année scolaire en cours : ${currentYear.name}`
                                    : "Aucune année scolaire n'est encore marquée comme en cours."}
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                            <Info className="h-5 w-5 shrink-0" />

                            <p className="text-sm font-medium">
                                Tableau de bord en lecture seule. Les
                                modifications restent réservées à
                                l'administrateur de l'établissement.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Effectif de l'établissement */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    {cards.map((card) => {
                        const Icon = card.icon;

                        return (
                            <Card
                                key={card.label}
                                className={`overflow-hidden border-0 p-5 text-white shadow-sm ${card.cardClass}`}
                            >
                                <div className="relative">
                                    <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 shadow-sm backdrop-blur-sm">
                                        <Icon
                                            className="h-6 w-6 text-white"
                                            strokeWidth={2}
                                        />
                                    </div>

                                    <p className="text-3xl font-bold tracking-tight">
                                        {card.value}
                                    </p>

                                    <p className="mt-1 text-sm font-medium text-white/80">
                                        {card.label}
                                    </p>
                                </div>
                            </Card>
                        );
                    })}
                </div>

                {/* Comptabilité : uniquement pour le comptable */}
                {billing && (
                    <>
                        <Card>
                            <Card.Header
                                title="Encaissements de l'année"
                                description="Répartition des contributions réglées entre CoriYase et l'établissement."
                            />

                            <Card.Body>
                                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                            Contributions réglées
                                        </p>
                                        <p className="mt-1 text-2xl font-bold text-slate-800">
                                            {billing.summary.paid_contributions}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            sur {billing.summary.students} élèves
                                        </p>
                                    </div>

                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                            Total encaissé
                                        </p>
                                        <p className="mt-1 text-2xl font-bold text-emerald-700">
                                            {money(billing.summary.collected)}
                                        </p>
                                    </div>

                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                            Part CoriYase
                                        </p>
                                        <p className="mt-1 text-2xl font-bold text-slate-800">
                                            {money(billing.summary.coriyase_share)}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            {billing.settings.coriyase_share} F
                                            par élève
                                        </p>
                                    </div>

                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                                            Part établissement
                                        </p>
                                        <p className="mt-1 text-2xl font-bold text-slate-800">
                                            {money(billing.summary.school_share)}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            {billing.settings.school_share} F
                                            par élève
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-4 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                                    <TrendingUp className="h-4 w-4" />

                                    <span>
                                        {billing.summary.included_sms} SMS
                                        inclus pour {billing.settings.included_sms_per_paid_student}{' '}
                                        par élève réglé. Recharge unitaire
                                        : {billing.settings.extra_sms_credit_unit} F.
                                    </span>
                                </div>
                            </Card.Body>
                        </Card>

                        <Card className="overflow-hidden">
                            <Card.Header
                                title="Contributions récentes"
                                description="Dernières opérations enregistrées pour l'établissement."
                            />

                            <Card.Body className="p-0">
                                {billing.recent_contributions.length === 0 ? (
                                    <div className="p-6">
                                        <EmptyState
                                            title="Aucune contribution enregistrée"
                                            description="Les contributions des élèves apparaîtront ici dès leur première saisie."
                                        />
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-slate-200">
                                            <thead className="bg-slate-50">
                                                <tr>
                                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        Élève
                                                    </th>
                                                    <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        Dû
                                                    </th>
                                                    <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        Réglé
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        Statut
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        Date
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody className="divide-y divide-slate-100">
                                                {billing.recent_contributions.map(
                                                    (contribution) => (
                                                        <tr
                                                            key={contribution.id}
                                                            className="transition-colors hover:bg-slate-50"
                                                        >
                                                            <td className="px-6 py-4 text-sm font-medium text-slate-800">
                                                                {
                                                                    contribution.student
                                                                }
                                                            </td>
                                                            <td className="px-6 py-4 text-right text-sm text-slate-600">
                                                                {money(
                                                                    contribution.amount_due
                                                                )}
                                                            </td>
                                                            <td className="px-6 py-4 text-right text-sm font-medium text-slate-800">
                                                                {money(
                                                                    contribution.amount_paid
                                                                )}
                                                            </td>
                                                            <td className="px-6 py-4">
                                                                <Badge
                                                                    tone={
                                                                        statusTones[
                                                                            contribution.status
                                                                        ] ??
                                                                        'slate'
                                                                    }
                                                                >
                                                                    {
                                                                        statusLabels[
                                                                            contribution.status
                                                                        ] ??
                                                                        contribution.status
                                                                    }
                                                                </Badge>
                                                            </td>
                                                            <td className="px-6 py-4 text-sm text-slate-500">
                                                                {contribution.paid_at ??
                                                                    '—'}
                                                            </td>
                                                        </tr>
                                                    )
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </Card.Body>
                        </Card>

                        <Card>
                            <Card.Header
                                title="Grille tarifaire en vigueur"
                                description="Paramètres utilisés pour le calcul des contributions."
                            />

                            <Card.Body>
                                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <dt className="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                                            <WalletCards className="h-4 w-4" />
                                            Frais d'installation
                                        </dt>
                                        <dd className="mt-1 text-lg font-bold text-slate-800">
                                            {money(billing.settings.installation_fee)}
                                        </dd>
                                    </div>

                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <dt className="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                                            <Receipt className="h-4 w-4" />
                                            Contribution annuelle
                                        </dt>
                                        <dd className="mt-1 text-lg font-bold text-slate-800">
                                            {money(
                                                billing.settings.annual_student_fee
                                            )}
                                        </dd>
                                    </div>

                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <dt className="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-slate-500">
                                            <SchoolIcon className="h-4 w-4" />
                                            Communication SMS
                                        </dt>
                                        <dd className="mt-1 text-lg font-bold text-slate-800">
                                            {billing.settings
                                                .communication_enabled
                                                ? 'Activée'
                                                : 'Désactivée'}
                                        </dd>
                                    </div>
                                </dl>
                            </Card.Body>
                        </Card>
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
