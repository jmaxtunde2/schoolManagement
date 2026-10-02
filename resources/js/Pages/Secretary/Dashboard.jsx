import { Head, Link, usePage } from '@inertiajs/react';

import {
    GraduationCap,
    Users,
    School as SchoolIcon,
    ClipboardList,
    Clock3,
    CheckCircle2,
    Eye,
    ArrowRight,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';

const statusTones = {
    draft: 'slate',
    in_progress: 'amber',
    validated: 'green',
    returned: 'red',
};

const statusLabels = {
    draft: 'Brouillon',
    in_progress: 'En cours de validation',
    validated: 'Validée',
    returned: 'Retournée pour correction',
};

export default function Dashboard({ stats, recentEvaluations }) {
    const { auth } = usePage().props;

    const cards = [
        {
            label: 'Élèves',
            value: stats.students,
            icon: GraduationCap,
            cardClass:
                'bg-gradient-to-br from-blue-500 to-blue-600',
        },

        {
            label: 'Enseignants',
            value: stats.teachers,
            icon: Users,
            cardClass:
                'bg-gradient-to-br from-violet-500 to-purple-600',
        },

        {
            label: 'Classes',
            value: stats.classes,
            icon: SchoolIcon,
            cardClass:
                'bg-gradient-to-br from-emerald-500 to-green-600',
        },

        {
            label: 'Évaluations',
            value: stats.evaluations,
            icon: ClipboardList,
            cardClass:
                'bg-gradient-to-br from-amber-400 to-orange-500',
        },

        {
            label: 'À valider',
            value: stats.in_progress,
            icon: Clock3,
            cardClass:
                'bg-gradient-to-br from-cyan-500 to-teal-600',
        },

        {
            label: 'Validées',
            value: stats.validated,
            icon: CheckCircle2,
            cardClass:
                'bg-gradient-to-br from-emerald-500 to-teal-600',
        },
    ];

    return (
        <AuthenticatedLayout
            title={`Bonjour, ${auth.user.name.split(' ')[0]}`}
        >
            <Head title="Tableau de bord" />

            {/* Statistics */}
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                {cards.map((card) => {
                    const Icon = card.icon;

                    return (
                        <Card
                            key={card.label}
                            className={`group relative overflow-hidden border-0 p-5 text-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg ${card.cardClass}`}
                        >
                            {/* Decorative background */}
                            <div className="absolute -right-5 -top-5 h-20 w-20 rounded-full bg-white/10" />

                            <div className="absolute -bottom-8 -left-5 h-24 w-24 rounded-full bg-white/5" />

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

            {/* Recent evaluations */}
            <Card className="mt-6 overflow-hidden">
                <Card.Header
                    title="Évaluations récentes"
                    actions={
                        <Link
                            href={route(
                                'teacher.evaluations.index'
                            )}
                            className="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-80"
                            style={{
                                color: 'var(--color-primary)',
                            }}
                        >
                            Voir tout
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    }
                />

                <Card.Body className="p-0">
                    {recentEvaluations.length === 0 ? (
                        <div className="p-6">
                            <EmptyState
                                title="Aucune évaluation pour l'instant"
                                description="Les évaluations créées par les enseignants apparaîtront ici."
                            />
                        </div>
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {recentEvaluations.map((evaluation) => (
                                <li key={evaluation.id}>
                                    <Link
                                        href={route(
                                            'teacher.evaluations.grades',
                                            evaluation.id
                                        )}
                                        className="group flex items-center justify-between gap-4 px-6 py-4 transition-colors hover:bg-slate-50"
                                    >
                                        {/* Evaluation information */}
                                        <div className="flex min-w-0 items-center gap-4">
                                            {/* Icon */}
                                            <div className="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:flex">
                                                <ClipboardList className="h-5 w-5" />
                                            </div>

                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <p className="truncate text-sm font-semibold text-slate-800 group-hover:text-primary">
                                                        {evaluation.title}
                                                    </p>
                                                </div>

                                                <p className="mt-1 truncate text-xs text-slate-500">
                                                    {evaluation.subject.name}
                                                    {' · '}
                                                    {evaluation.class_room.name}
                                                    {' · '}
                                                    {evaluation.teacher.user.name}
                                                    {' · '}
                                                    {evaluation.evaluation_date}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Status + consultation */}
                                        <div className="flex shrink-0 items-center gap-3">
                                            <Badge
                                                tone={
                                                    statusTones[
                                                        evaluation.status
                                                    ] ?? 'slate'
                                                }
                                            >
                                                {statusLabels[
                                                    evaluation.status
                                                ] ?? evaluation.status}
                                            </Badge>

                                            <span className="hidden items-center gap-1 text-xs font-medium text-slate-500 transition-colors group-hover:text-primary sm:flex">
                                                <Eye className="h-4 w-4" />
                                                Consulter
                                            </span>

                                            <ArrowRight className="h-4 w-4 text-slate-300 transition-all group-hover:translate-x-1 group-hover:text-primary" />
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}