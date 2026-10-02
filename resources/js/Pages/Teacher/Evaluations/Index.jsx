import { Head, Link } from '@inertiajs/react';
import {
    Plus,
    ClipboardList,
    CheckCircle2,
    Clock3,
    RotateCcw,
    FileEdit,
    ArrowRight,
    CalendarDays,
    GraduationCap,
    BookOpen,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import EmptyState from '@/Components/UI/EmptyState';

const statusTones = {
    draft: 'slate',
    in_progress: 'amber',
    validated: 'green',
    returned: 'red',
};

const statusLabels = {
    draft: 'Brouillon',
    in_progress: 'En cours',
    validated: 'Validée',
    returned: 'Retournée pour correction',
};

const statusIcons = {
    draft: FileEdit,
    in_progress: Clock3,
    validated: CheckCircle2,
    returned: RotateCcw,
};

export default function TeacherEvaluationsIndex({
    evaluations,
}) {
    const rows = evaluations?.data ?? [];

    const total = evaluations?.total ?? rows.length;

    const draftCount = rows.filter(
        (evaluation) => evaluation.status === 'draft'
    ).length;

    const inProgressCount = rows.filter(
        (evaluation) => evaluation.status === 'in_progress'
    ).length;

    const validatedCount = rows.filter(
        (evaluation) => evaluation.status === 'validated'
    ).length;

    return (
        <AuthenticatedLayout title="Mes évaluations">
            <Head title="Mes évaluations" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-violet-600 via-fuchsia-600 to-pink-600 p-6 text-white shadow-lg sm:p-8">
                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10 blur-2xl" />
                    <div className="absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-pink-300/10 blur-3xl" />

                    <div className="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold backdrop-blur">
                                <ClipboardList className="h-4 w-4" />
                                Suivi pédagogique
                            </div>

                            <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                                Mes évaluations
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-fuchsia-50 sm:text-base">
                                Consultez vos évaluations, suivez leur statut
                                et accédez directement à la saisie des notes.
                            </p>
                        </div>

                        <Link
                            href={route('teacher.evaluations.create')}
                            className="shrink-0"
                        >
                            <Button
                                size="lg"
                                className="w-full border-0 bg-white text-fuchsia-700 shadow-lg hover:bg-fuchsia-50 sm:w-auto"
                            >
                                <Plus className="h-5 w-5" />
                                Nouvelle évaluation
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Statistics */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard
                        icon={ClipboardList}
                        label="Total"
                        value={total}
                        description="Évaluations"
                        tone="violet"
                    />

                    <StatCard
                        icon={FileEdit}
                        label="Brouillons"
                        value={draftCount}
                        description="À compléter"
                        tone="slate"
                    />

                    <StatCard
                        icon={Clock3}
                        label="En cours"
                        value={inProgressCount}
                        description="À finaliser"
                        tone="amber"
                    />

                    <StatCard
                        icon={CheckCircle2}
                        label="Validées"
                        value={validatedCount}
                        description="Évaluations terminées"
                        tone="emerald"
                    />
                </div>

                {/* Table */}
                <Card className="overflow-hidden">
                    <div className="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-violet-50/40 p-5">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                                    <ClipboardList className="h-5 w-5" />
                                </div>

                                <div>
                                    <h2 className="font-semibold text-slate-900">
                                        Liste des évaluations
                                    </h2>

                                    <p className="text-xs text-slate-500">
                                        {total} évaluation
                                        {total > 1 ? 's' : ''} enregistrée
                                        {total > 1 ? 's' : ''}
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route(
                                    'teacher.evaluations.create'
                                )}
                                className="sm:hidden"
                            >
                                <Button className="w-full">
                                    <Plus className="h-4 w-4" />
                                    Nouvelle évaluation
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <Card.Body>
                        {rows.length === 0 ? (
                            <EmptyState
                                icon={ClipboardList}
                                title="Aucune évaluation"
                                description="Créez votre première évaluation pour commencer à saisir des notes."
                                action={
                                    <Link
                                        href={route(
                                            'teacher.evaluations.create'
                                        )}
                                    >
                                        <Button>
                                            <Plus className="h-4 w-4" />
                                            Nouvelle évaluation
                                        </Button>
                                    </Link>
                                }
                            />
                        ) : (
                            <>
                                {/* Desktop table */}
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b border-slate-100 text-left">
                                                <th className="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Évaluation
                                                </th>
                                                <th className="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Classe
                                                </th>
                                                <th className="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Matière
                                                </th>
                                                <th className="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Date
                                                </th>
                                                <th className="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Statut
                                                </th>
                                                <th className="px-4 py-3" />
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-slate-100">
                                            {rows.map((evaluation) => {
                                                const StatusIcon =
                                                    statusIcons[
                                                        evaluation.status
                                                    ] || ClipboardList;

                                                return (
                                                    <tr
                                                        key={evaluation.id}
                                                        className="group transition hover:bg-slate-50/80"
                                                    >
                                                        <td className="px-4 py-4">
                                                            <Link
                                                                href={route(
                                                                    'teacher.evaluations.grades',
                                                                    evaluation.id
                                                                )}
                                                                className="flex items-center gap-3"
                                                            >
                                                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600 transition group-hover:bg-violet-600 group-hover:text-white">
                                                                    <StatusIcon className="h-4 w-4" />
                                                                </div>

                                                                <div className="min-w-0">
                                                                    <p className="max-w-[260px] truncate text-sm font-semibold text-slate-800">
                                                                        {
                                                                            evaluation.title
                                                                        }
                                                                    </p>

                                                                    <p className="mt-0.5 text-xs text-slate-400">
                                                                        Évaluation
                                                                        #{evaluation.id}
                                                                    </p>
                                                                </div>
                                                            </Link>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <div className="flex items-center gap-2">
                                                                <GraduationCap className="h-4 w-4 text-blue-500" />

                                                                <span className="text-sm font-medium text-slate-700">
                                                                    {
                                                                        evaluation
                                                                            .class_room
                                                                            ?.name
                                                                    }
                                                                </span>
                                                            </div>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <div className="flex items-center gap-2">
                                                                <BookOpen className="h-4 w-4 text-emerald-500" />

                                                                <span className="text-sm text-slate-600">
                                                                    {
                                                                        evaluation
                                                                            .subject
                                                                            ?.name
                                                                    }
                                                                </span>
                                                            </div>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <div className="flex items-center gap-2 text-sm text-slate-600">
                                                                <CalendarDays className="h-4 w-4 text-slate-400" />
                                                                {
                                                                    evaluation.evaluation_date
                                                                }
                                                            </div>
                                                        </td>

                                                        <td className="px-4 py-4">
                                                            <Badge
                                                                tone={
                                                                    statusTones[
                                                                        evaluation.status
                                                                    ]
                                                                }
                                                            >
                                                                {
                                                                    statusLabels[
                                                                        evaluation.status
                                                                    ]
                                                                }
                                                            </Badge>
                                                        </td>

                                                        <td className="px-4 py-4 text-right">
                                                            <Link
                                                                href={route(
                                                                    'teacher.evaluations.grades',
                                                                    evaluation.id
                                                                )}
                                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-violet-100 hover:text-violet-600"
                                                                title="Ouvrir"
                                                            >
                                                                <ArrowRight className="h-4 w-4" />
                                                            </Link>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Mobile cards */}
                                <div className="space-y-3 md:hidden">
                                    {rows.map((evaluation) => {
                                        const StatusIcon =
                                            statusIcons[
                                                evaluation.status
                                            ] || ClipboardList;

                                        return (
                                            <Link
                                                key={evaluation.id}
                                                href={route(
                                                    'teacher.evaluations.grades',
                                                    evaluation.id
                                                )}
                                                className="group block rounded-2xl border border-slate-100 p-4 transition hover:border-violet-200 hover:bg-violet-50/30 hover:shadow-sm"
                                            >
                                                <div className="flex items-start gap-3">
                                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                                                        <StatusIcon className="h-5 w-5" />
                                                    </div>

                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-start justify-between gap-3">
                                                            <p className="truncate text-sm font-semibold text-slate-800">
                                                                {
                                                                    evaluation.title
                                                                }
                                                            </p>

                                                            <ArrowRight className="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-1 group-hover:text-violet-500" />
                                                        </div>

                                                        <div className="mt-2 grid grid-cols-2 gap-2">
                                                            <Info
                                                                icon={
                                                                    GraduationCap
                                                                }
                                                                label="Classe"
                                                                value={
                                                                    evaluation
                                                                        .class_room
                                                                        ?.name
                                                                }
                                                            />

                                                            <Info
                                                                icon={BookOpen}
                                                                label="Matière"
                                                                value={
                                                                    evaluation
                                                                        .subject
                                                                        ?.name
                                                                }
                                                            />
                                                        </div>

                                                        <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                                                            <div className="flex items-center gap-1.5 text-xs text-slate-500">
                                                                <CalendarDays className="h-3.5 w-3.5" />
                                                                {
                                                                    evaluation.evaluation_date
                                                                }
                                                            </div>

                                                            <Badge
                                                                tone={
                                                                    statusTones[
                                                                        evaluation.status
                                                                    ]
                                                                }
                                                            >
                                                                {
                                                                    statusLabels[
                                                                        evaluation.status
                                                                    ]
                                                                }
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                </div>
                                            </Link>
                                        );
                                    })}
                                </div>
                            </>
                        )}

                        <div className="mt-5">
                            <Pagination meta={evaluations} />
                        </div>
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
    description,
    tone,
}) {
    const tones = {
        violet: {
            wrapper:
                'border-violet-100 bg-gradient-to-br from-violet-50 to-fuchsia-50',
            icon: 'bg-violet-100 text-violet-600',
            value: 'text-violet-700',
        },
        slate: {
            wrapper:
                'border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100/60',
            icon: 'bg-slate-200 text-slate-600',
            value: 'text-slate-700',
        },
        amber: {
            wrapper:
                'border-amber-100 bg-gradient-to-br from-amber-50 to-orange-50',
            icon: 'bg-amber-100 text-amber-600',
            value: 'text-amber-700',
        },
        emerald: {
            wrapper:
                'border-emerald-100 bg-gradient-to-br from-emerald-50 to-teal-50',
            icon: 'bg-emerald-100 text-emerald-600',
            value: 'text-emerald-700',
        },
    };

    const styles = tones[tone] || tones.violet;

    return (
        <div
            className={`rounded-2xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md ${styles.wrapper}`}
        >
            <div className="flex items-center justify-between gap-3">
                <div
                    className={`flex h-10 w-10 items-center justify-center rounded-xl ${styles.icon}`}
                >
                    <Icon className="h-5 w-5" />
                </div>

                <span
                    className={`text-2xl font-bold tracking-tight ${styles.value}`}
                >
                    {value}
                </span>
            </div>

            <div className="mt-4">
                <p className="text-sm font-semibold text-slate-800">
                    {label}
                </p>

                <p className="mt-0.5 text-xs text-slate-500">
                    {description}
                </p>
            </div>
        </div>
    );
}

function Info({
    icon: Icon,
    label,
    value,
}) {
    return (
        <div className="rounded-xl bg-slate-50 p-2.5">
            <div className="flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                <Icon className="h-3.5 w-3.5" />
                {label}
            </div>

            <p className="mt-1 truncate text-xs font-semibold text-slate-700">
                {value || '—'}
            </p>
        </div>
    );
}