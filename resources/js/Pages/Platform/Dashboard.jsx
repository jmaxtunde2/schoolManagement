import { Head } from '@inertiajs/react';
import {
    BadgeDollarSign,
    Bell,
    Building2,
    CheckCircle2,
    GraduationCap,
    Users,
    Wallet,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';

export default function Dashboard({ stats, schools }) {
    const statistics = [
        {
            label: 'Établissements',
            value: stats.schools,
            icon: Building2,
            iconClass: 'bg-blue-50 text-blue-600',
        },
        {
            label: 'Actifs',
            value: stats.active_schools,
            icon: CheckCircle2,
            iconClass: 'bg-emerald-50 text-emerald-600',
        },
        {
            label: 'Élèves',
            value: stats.students,
            icon: GraduationCap,
            iconClass: 'bg-violet-50 text-violet-600',
        },
        {
            label: 'Payants',
            value: stats.paid_contributions,
            icon: Users,
            iconClass: 'bg-cyan-50 text-cyan-600',
        },
        {
            label: 'Collecté',
            value: `${stats.collected.toLocaleString('fr-FR')} F`,
            icon: Wallet,
            iconClass: 'bg-amber-50 text-amber-600',
        },
        {
            label: 'Part Coriyase',
            value: `${stats.coriyase_share.toLocaleString('fr-FR')} F`,
            icon: BadgeDollarSign,
            iconClass: 'bg-teal-50 text-teal-600',
        },
        {
            label: 'Notifications',
            value: stats.notifications,
            icon: Bell,
            iconClass: 'bg-rose-50 text-rose-600',
        },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title="Plateforme Coriyase" />

            {/* Statistiques */}
            <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-7">
                {statistics.map(
                    ({
                        label,
                        value,
                        icon: Icon,
                        iconClass,
                    }) => (
                        <Card
                            key={label}
                            className="p-4 transition-shadow hover:shadow-md"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-xs font-medium text-slate-500">
                                        {label}
                                    </p>

                                    <p className="mt-1 truncate text-xl font-semibold text-slate-900">
                                        {value}
                                    </p>
                                </div>

                                <div
                                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${iconClass}`}
                                >
                                    <Icon
                                        className="h-5 w-5"
                                        strokeWidth={2}
                                    />
                                </div>
                            </div>
                        </Card>
                    )
                )}
            </div>

            {/* Établissements */}
            <Card className="mt-6">
                <Card.Header title="Établissements" />

                <Card.Body>
                    <div className="divide-y divide-slate-100">
                        {schools.map((school) => (
                            <div
                                key={school.id}
                                className="flex items-center justify-between py-3"
                            >
                                <div className="flex min-w-0 items-center gap-3">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                        <Building2
                                            className="h-4 w-4"
                                            strokeWidth={2}
                                        />
                                    </div>

                                    <div className="min-w-0">
                                        <p className="truncate font-medium text-slate-900">
                                            {school.name}
                                        </p>

                                        <p className="text-xs text-slate-500">
                                            {school.users_count}{' '}
                                            utilisateur(s)
                                        </p>
                                    </div>
                                </div>

                                <span
                                    className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ${
                                        school.active
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-500'
                                    }`}
                                >
                                    {school.active
                                        ? 'Actif'
                                        : 'Suspendu'}
                                </span>
                            </div>
                        ))}
                    </div>
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}