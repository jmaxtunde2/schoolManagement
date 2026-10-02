import { useMemo, useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import {
    ClipboardList,
    CheckCircle2,
    Clock3,
    FileEdit,
    GraduationCap,
    BookOpen,
    CalendarDays,
    Users,
    ArrowRight,
    ListChecks,
    Search,
    X,
    Filter,
    Eye,
    RotateCcw,
} from 'lucide-react';

import Button from '@/Components/UI/Button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import Select from '@/Components/UI/Select';
import EmptyState from '@/Components/UI/EmptyState';
import Input from '@/Components/UI/Input';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import Textarea from '@/Components/UI/Textarea';
import { useForm } from '@inertiajs/react';

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

export default function EvaluationsIndex({
    evaluations,
    filters,
    routePrefix = 'admin',
}) {
    const [evaluationToReturn, setEvaluationToReturn] = useState(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        reason: '',
    });

    const [status, setStatus] = useState(
        filters.status ?? ''
    );

    const [search, setSearch] = useState('');

    /*
     * Les statistiques sont calculées sur les données disponibles
     * dans la page courante.
     *
     * Pour obtenir des statistiques globales fiables sur toutes
     * les pages, il faudra idéalement que le contrôleur fournisse
     * summary.total / summary.draft / summary.in_progress /
     * summary.validated.
     */
    const stats = useMemo(() => {
        const rows = evaluations.data ?? [];

        return {
            total: evaluations.total ?? 0,
            draft: rows.filter(
                (evaluation) =>
                    evaluation.status === 'draft'
            ).length,
            inProgress: rows.filter(
                (evaluation) =>
                    evaluation.status === 'in_progress'
            ).length,
            validated: rows.filter(
                (evaluation) =>
                    evaluation.status === 'validated'
            ).length,
        };
    }, [evaluations]);

    const filteredEvaluations = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (!term) {
            return evaluations.data;
        }

        return evaluations.data.filter((evaluation) => {
            return [
                evaluation.title,
                evaluation.status,
                statusLabels[evaluation.status],
                evaluation.class_room?.name,
                evaluation.subject?.name,
                evaluation.teacher?.user?.name,
                evaluation.evaluation_date,
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value)
                        .toLowerCase()
                        .includes(term)
                );
        });
    }, [evaluations.data, search]);

    function applyFilter(value) {
        setStatus(value);

        router.get(
            route(
                `${routePrefix}.evaluations.index`
            ),
            {
                status: value,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    }

    function resetFilters() {
        setSearch('');
        applyFilter('');
    }

    function submitReturn() {
        if (!evaluationToReturn || !data.reason.trim()) {
            return;
        }

        post(
            route(`${routePrefix}.evaluations.return`, evaluationToReturn.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEvaluationToReturn(null);
                    reset();
                },
            }
        );
    }

    function closeReturnDialog() {
        setEvaluationToReturn(null);
        reset();
    }

    return (
        <AuthenticatedLayout title="Évaluations">
            <Head title="Évaluations" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 text-white shadow-lg">
                    <div className="relative p-6">
                        <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                        <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                        <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div className="mb-3 flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                        <ClipboardList className="h-5 w-5" />
                                    </div>

                                    <span className="text-sm font-medium text-white/80">
                                        Gestion pédagogique
                                    </span>
                                </div>

                                <h1 className="text-2xl font-bold sm:text-3xl">
                                    Évaluations
                                </h1>

                                <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                    Consultez, recherchez et suivez
                                    les évaluations créées par les
                                    enseignants jusqu'à leur validation.
                                </p>
                            </div>

                            <div className="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                    <ListChecks className="h-5 w-5" />
                                </div>

                                <div>
                                    <p className="text-xs text-white/70">
                                        Total enregistré
                                    </p>

                                    <p className="text-2xl font-bold">
                                        {evaluations.total}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Statistiques */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard
                        icon={ClipboardList}
                        label="Total"
                        value={stats.total}
                        description="Évaluations enregistrées"
                        className="from-blue-500 to-indigo-600"
                    />

                    <StatCard
                        icon={FileEdit}
                        label="Brouillons"
                        value={stats.draft}
                        description="Évaluations non soumises"
                        className="from-slate-500 to-slate-700"
                    />

                    <StatCard
                        icon={Clock3}
                        label="À valider"
                        value={stats.inProgress}
                        description="En attente de validation"
                        className="from-amber-500 to-orange-600"
                    />

                    <StatCard
                        icon={CheckCircle2}
                        label="Validées"
                        value={stats.validated}
                        description="Évaluations validées"
                        className="from-emerald-500 to-teal-600"
                    />
                </div>

                {/* Liste */}
                <Card className="overflow-hidden">
                    <Card.Header
                        title="Liste des évaluations"
                        description={`${evaluations.total} évaluation(s) enregistrée(s)`}
                        actions={
                            <div className="flex items-center gap-2">
                                <Filter className="hidden h-4 w-4 text-slate-400 sm:block" />

                                <Select
                                    className="w-full sm:w-56"
                                    value={status}
                                    onChange={(e) =>
                                        applyFilter(
                                            e.target.value
                                        )
                                    }
                                >
                                    <option value="">
                                        Tous les statuts
                                    </option>

                                    <option value="draft">
                                        Brouillon
                                    </option>

                                    <option value="in_progress">
                                        En cours de validation
                                    </option>

                                    <option value="validated">
                                        Validée
                                    </option>

                                    <option value="returned">
                                        Retournée pour correction
                                    </option>
                                </Select>
                            </div>
                        }
                    />

                    <Card.Body>
                        {/* Recherche */}
                        <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                <Input
                                    value={search}
                                    onChange={(e) =>
                                        setSearch(
                                            e.target.value
                                        )
                                    }
                                    placeholder="Rechercher par évaluation, classe, matière, enseignant ou date..."
                                    className="pl-10"
                                />
                            </div>

                            {(search || status) && (
                                <div className="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
                                    <p className="text-sm text-slate-500">
                                        <span className="font-semibold text-slate-700">
                                            {
                                                filteredEvaluations.length
                                            }
                                        </span>{' '}
                                        résultat
                                        {filteredEvaluations.length >
                                        1
                                            ? 's'
                                            : ''}{' '}
                                        sur cette page
                                    </p>

                                    <button
                                        type="button"
                                        onClick={
                                            resetFilters
                                        }
                                        className="inline-flex items-center gap-1.5 text-sm font-medium text-teal-600 hover:text-teal-700"
                                    >
                                        <X className="h-4 w-4" />
                                        Réinitialiser
                                    </button>
                                </div>
                            )}
                        </div>

                        <Table
                            rows={filteredEvaluations}
                            emptyState={
                                search || status ? (
                                    <EmptyState
                                        icon={Search}
                                        title="Aucun résultat"
                                        description="Aucune évaluation ne correspond aux critères sélectionnés."
                                        action={
                                            <Button
                                                variant="secondary"
                                                onClick={
                                                    resetFilters
                                                }
                                            >
                                                Réinitialiser
                                            </Button>
                                        }
                                    />
                                ) : (
                                    <EmptyState
                                        icon={ClipboardList}
                                        title="Aucune évaluation"
                                        description="Les évaluations créées par les enseignants apparaîtront ici."
                                    />
                                )
                            }
                            columns={[
                                {
                                    key: 'title',
                                    header: 'Évaluation',
                                    render: (evaluation) => (
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                                <ClipboardList className="h-5 w-5" />
                                            </div>

                                            <div className="min-w-0">
                                                <p className="truncate font-semibold text-slate-800">
                                                    {
                                                        evaluation.title
                                                    }
                                                </p>

                                                <p className="mt-0.5 text-xs text-slate-400">
                                                    Évaluation pédagogique
                                                </p>
                                            </div>
                                        </div>
                                    ),
                                },

                                {
                                    key: 'class_room',
                                    header: 'Classe',
                                    render: (evaluation) => (
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                                <GraduationCap className="h-4 w-4" />
                                            </div>

                                            <span className="font-medium text-slate-700">
                                                {evaluation.class_room?.name ??
                                                    '—'}
                                            </span>
                                        </div>
                                    ),
                                },

                                {
                                    key: 'subject',
                                    header: 'Matière',
                                    render: (evaluation) => (
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                                <BookOpen className="h-4 w-4" />
                                            </div>

                                            <span className="font-medium text-slate-700">
                                                {evaluation.subject?.name ??
                                                    '—'}
                                            </span>
                                        </div>
                                    ),
                                },

                                {
                                    key: 'teacher',
                                    header: 'Enseignant',
                                    render: (evaluation) => (
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-cyan-600">
                                                <Users className="h-4 w-4" />
                                            </div>

                                            <span className="text-sm text-slate-700">
                                                {evaluation.teacher?.user?.name ??
                                                    '—'}
                                            </span>
                                        </div>
                                    ),
                                },

                                {
                                    key: 'evaluation_date',
                                    header: 'Date',
                                    render: (evaluation) => (
                                        <div className="flex items-center gap-2 text-sm text-slate-600">
                                            <CalendarDays className="h-4 w-4 text-cyan-500" />

                                            {evaluation.evaluation_date ??
                                                '—'}
                                        </div>
                                    ),
                                },

                                {
                                    key: 'status',
                                    header: 'Statut',
                                    render: (evaluation) => (
                                        <Badge
                                            tone={
                                                statusTones[
                                                    evaluation
                                                        .status
                                                ] ??
                                                'slate'
                                            }
                                        >
                                            {statusLabels[
                                                evaluation
                                                    .status
                                            ] ??
                                                evaluation.status}
                                        </Badge>
                                    ),
                                },

                                {
                                    key: 'actions',
                                    header: '',
                                    render: (evaluation) => (
                                        <div className="flex items-center justify-end gap-1">
                                            <Link
                                                href={route(
                                                    `${routePrefix}.evaluations.grades`,
                                                    evaluation.id
                                                )}
                                                className="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                                title="Consulter"
                                            >
                                                <Eye className="h-4 w-4" />

                                                <span className="hidden sm:inline">
                                                    Consulter
                                                </span>
                                            </Link>

                                            {evaluation.status ===
                                            'in_progress' ? (
                                                <>
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    `${routePrefix}.evaluations.validate`,
                                                                    evaluation.id
                                                                )
                                                            )
                                                        }
                                                    >
                                                        <CheckCircle2 className="h-4 w-4" />

                                                        <span className="hidden sm:inline">
                                                            Valider
                                                        </span>
                                                    </Button>

                                                    <Button
                                                        size="sm"
                                                        variant="danger"
                                                        onClick={() => {
                                                            reset();
                                                            setEvaluationToReturn(evaluation);
                                                        }}
                                                        title="Retourner pour correction"
                                                    >
                                                        <RotateCcw className="h-4 w-4" />

                                                        <span className="hidden sm:inline">
                                                            Retourner
                                                        </span>
                                                    </Button>
                                                </>
                                            ) : null}

                                            {evaluation.status !==
                                                'in_progress' && (
                                                <Link
                                                    href={route(
                                                        `${routePrefix}.evaluations.grades`,
                                                        evaluation.id
                                                    )}
                                                    className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                                    title="Ouvrir"
                                                >
                                                    <ArrowRight className="h-4 w-4" />
                                                </Link>
                                            )}
                                        </div>
                                    ),
                                },
                            ]}
                        />
                    </Card.Body>

                    <div className="border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
                        <Pagination meta={evaluations} />
                    </div>
                </Card>
            </div>

            <ConfirmDialog
                show={Boolean(evaluationToReturn)}
                onClose={closeReturnDialog}
                onConfirm={submitReturn}
                title="Retourner l’évaluation"
                description={
                    evaluationToReturn
                        ? `Indiquez à l’enseignant les corrections attendues pour « ${evaluationToReturn.title} ».`
                        : undefined
                }
                confirmLabel="Retourner pour correction"
                variant="danger"
                loading={processing}
            >
                <div className="mt-4">
                    <label
                        htmlFor="return-reason"
                        className="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Motif du retour
                    </label>
                    <Textarea
                        id="return-reason"
                        rows={4}
                        value={data.reason}
                        onChange={(event) =>
                            setData('reason', event.target.value)
                        }
                        error={errors.reason}
                        placeholder="Précisez les corrections à effectuer..."
                        maxLength={2000}
                    />
                    {errors.reason && (
                        <p className="mt-1 text-sm text-red-600">
                            {errors.reason}
                        </p>
                    )}
                </div>
            </ConfirmDialog>
        </AuthenticatedLayout>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
    description,
    className,
}) {
    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${className} p-5 text-white shadow-md`}
        >
            <div className="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/10" />

            <div className="relative z-10">
                <div className="mb-4 flex items-center justify-between">
                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm">
                        <Icon className="h-5 w-5" />
                    </div>

                    <span className="text-3xl font-bold">
                        {value}
                    </span>
                </div>

                <p className="font-semibold">
                    {label}
                </p>

                <p className="mt-1 text-xs text-white/70">
                    {description}
                </p>
            </div>
        </div>
    );
}