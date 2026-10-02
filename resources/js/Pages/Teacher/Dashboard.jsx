import { Head, Link, usePage } from '@inertiajs/react';
import {
    Plus,
    ClipboardList,
    BookOpen,
    GraduationCap,
    ArrowRight,
    Clock3,
    CheckCircle2,
    FileEdit,
    RotateCcw,
    Users,
    Sparkles,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
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

export default function TeacherDashboard({
    assignments = [],
    recentEvaluations = [],
    draftCount = 0,
}) {
    const { auth } = usePage().props;

    const firstName = auth.user.name?.split(' ')[0] || 'Enseignant';

    const validatedCount = recentEvaluations.filter(
        (evaluation) => evaluation.status === 'validated'
    ).length;

    const inProgressCount = recentEvaluations.filter(
        (evaluation) => evaluation.status === 'in_progress'
    ).length;

    return (
        <AuthenticatedLayout title={`Bonjour, ${firstName}`}>
            <Head title="Tableau de bord enseignant" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg sm:p-8">
                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10 blur-2xl" />
                    <div className="absolute -bottom-20 left-1/3 h-48 w-48 rounded-full bg-cyan-300/10 blur-3xl" />

                    <div className="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="max-w-2xl">
                            <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold backdrop-blur">
                                <Sparkles className="h-4 w-4" />
                                Espace enseignant
                            </div>

                            <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                                Bonjour, {firstName} 👋
                            </h1>

                            <p className="mt-2 max-w-xl text-sm leading-6 text-emerald-50 sm:text-base">
                                Retrouvez vos classes, vos matières et vos
                                évaluations depuis votre espace de travail.
                            </p>
                        </div>

                        <Link
                            href={route('teacher.evaluations.create')}
                            className="shrink-0"
                        >
                            <Button
                                size="lg"
                                className="w-full border-0 bg-white text-emerald-700 shadow-lg hover:bg-emerald-50 sm:w-auto"
                            >
                                <Plus className="h-5 w-5" />
                                Nouvelle évaluation
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard
                        icon={BookOpen}
                        label="Affectations"
                        value={assignments.length}
                        description="Classes et matières"
                        tone="blue"
                    />

                    <StatCard
                        icon={ClipboardList}
                        label="Évaluations"
                        value={recentEvaluations.length}
                        description="Dernières évaluations"
                        tone="violet"
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
                        description="Évaluations validées"
                        tone="emerald"
                    />
                </div>

                {/* Draft alert */}
                {draftCount > 0 && (
                    <div className="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                <FileEdit className="h-5 w-5" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-amber-900">
                                    Évaluations à finaliser
                                </p>

                                <p className="mt-0.5 text-sm text-amber-700">
                                    Vous avez {draftCount} évaluation
                                    {draftCount > 1 ? 's' : ''} en brouillon ou
                                    en cours de validation.
                                </p>
                            </div>
                        </div>

                        <Link
                            href={route('teacher.evaluations.index')}
                            className="inline-flex items-center justify-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-900"
                        >
                            Consulter
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </div>
                )}

                {/* Main content */}
                <div className="grid gap-6 xl:grid-cols-5">
                    {/* Assignments */}
                    <Card className="overflow-hidden xl:col-span-2">
                        <div className="border-b border-slate-100 bg-gradient-to-r from-blue-50 via-indigo-50 to-violet-50 p-5">
                            <div className="flex items-center justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                        <GraduationCap className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <h2 className="font-semibold text-slate-900">
                                            Mes affectations
                                        </h2>
                                        <p className="text-xs text-slate-500">
                                            Classes et matières
                                        </p>
                                    </div>
                                </div>

                                <span className="rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-700 shadow-sm">
                                    {assignments.length}
                                </span>
                            </div>
                        </div>

                        <Card.Body>
                            {assignments.length === 0 ? (
                                <EmptyState
                                    icon={BookOpen}
                                    title="Aucune affectation"
                                    description="Contactez l'administration pour être affecté à une classe et une matière."
                                />
                            ) : (
                                <div className="space-y-3">
                                    {assignments.map((assignment, index) => (
                                        <div
                                            key={`${assignment.class_name}-${assignment.subject_name}-${index}`}
                                            className="group rounded-2xl border border-slate-100 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50/40 hover:shadow-sm"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-sm">
                                                    <BookOpen className="h-4 w-4" />
                                                </div>

                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-semibold text-slate-800">
                                                        {assignment.subject_name}
                                                    </p>

                                                    <div className="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                                        <Users className="h-3.5 w-3.5" />
                                                        {assignment.class_name}
                                                    </div>
                                                </div>

                                                <ArrowRight className="h-4 w-4 text-slate-300 transition group-hover:translate-x-1 group-hover:text-blue-500" />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card.Body>
                    </Card>

                    {/* Recent evaluations */}
                    <Card className="overflow-hidden xl:col-span-3">
                        <div className="border-b border-slate-100 bg-gradient-to-r from-violet-50 via-fuchsia-50 to-pink-50 p-5">
                            <div className="flex items-center justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                                        <ClipboardList className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <h2 className="font-semibold text-slate-900">
                                            Évaluations récentes
                                        </h2>
                                        <p className="text-xs text-slate-500">
                                            Suivi de vos dernières saisies
                                        </p>
                                    </div>
                                </div>

                                <Link
                                    href={route(
                                        'teacher.evaluations.index'
                                    )}
                                    className="hidden items-center gap-1 text-sm font-semibold text-violet-600 transition hover:text-violet-800 sm:inline-flex"
                                >
                                    Voir tout
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            </div>
                        </div>

                        <Card.Body>
                            {recentEvaluations.length === 0 ? (
                                <EmptyState
                                    icon={ClipboardList}
                                    title="Aucune évaluation"
                                    description="Créez votre première évaluation pour commencer à saisir des notes."
                                />
                            ) : (
                                <div className="space-y-2">
                                    {recentEvaluations.map((evaluation) => {
                                        const StatusIcon =
                                            statusIcons[evaluation.status] ||
                                            ClipboardList;

                                        return (
                                            <Link
                                                key={evaluation.id}
                                                href={route(
                                                    'teacher.evaluations.grades',
                                                    evaluation.id
                                                )}
                                                className="group flex items-center gap-3 rounded-2xl border border-transparent p-3 transition hover:border-slate-200 hover:bg-slate-50"
                                            >
                                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition group-hover:bg-violet-100 group-hover:text-violet-600">
                                                    <StatusIcon className="h-4 w-4" />
                                                </div>

                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-semibold text-slate-800">
                                                        {evaluation.title}
                                                    </p>

                                                    <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                                        <span>
                                                            {evaluation.subject
                                                                ?.name || 'Matière'}
                                                        </span>

                                                        <span className="text-slate-300">
                                                            •
                                                        </span>

                                                        <span>
                                                            {evaluation
                                                                .class_room
                                                                ?.name ||
                                                                'Classe'}
                                                        </span>

                                                        <span className="text-slate-300">
                                                            •
                                                        </span>

                                                        <span>
                                                            {
                                                                evaluation.evaluation_date
                                                            }
                                                        </span>
                                                    </div>
                                                </div>

                                                <div className="hidden shrink-0 sm:block">
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

                                                <ArrowRight className="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-1 group-hover:text-violet-500" />
                                            </Link>
                                        );
                                    })}
                                </div>
                            )}

                            <Link
                                href={route('teacher.evaluations.index')}
                                className="mt-4 flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700 sm:hidden"
                            >
                                Voir toutes les évaluations
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </Card.Body>
                    </Card>
                </div>
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
        blue: {
            wrapper: 'border-blue-100 bg-gradient-to-br from-blue-50 to-cyan-50',
            icon: 'bg-blue-100 text-blue-600',
            value: 'text-blue-700',
        },
        violet: {
            wrapper:
                'border-violet-100 bg-gradient-to-br from-violet-50 to-fuchsia-50',
            icon: 'bg-violet-100 text-violet-600',
            value: 'text-violet-700',
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

    const styles = tones[tone] || tones.blue;

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