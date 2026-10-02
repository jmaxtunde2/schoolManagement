import { useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    CalendarRange,
    Search,
    CheckCircle2,
    Archive,
    CalendarDays,
    Clock3,
    X,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/UI/Modal';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import EmptyState from '@/Components/UI/EmptyState';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Checkbox from '@/Components/UI/Checkbox';

const empty = {
    name: '',
    starts_on: '',
    ends_on: '',
    is_current: false,
};

export default function AcademicYearsIndex({ years }) {
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
    } = useForm(empty);

    /*
    |--------------------------------------------------------------------------
    | Statistiques
    |--------------------------------------------------------------------------
    */

    const currentYears = useMemo(
        () => years.filter((year) => year.is_current),
        [years]
    );

    const archivedYears = useMemo(
        () => years.filter((year) => !year.is_current),
        [years]
    );

    const datedYears = useMemo(
        () =>
            years.filter(
                (year) =>
                    year.starts_on &&
                    year.ends_on
            ),
        [years]
    );

    /*
    |--------------------------------------------------------------------------
    | Recherche + filtre
    |--------------------------------------------------------------------------
    */

    const filteredYears = useMemo(() => {
        const term = search.trim().toLowerCase();

        return years.filter((year) => {
            if (
                statusFilter === 'current' &&
                !year.is_current
            ) {
                return false;
            }

            if (
                statusFilter === 'archived' &&
                year.is_current
            ) {
                return false;
            }

            if (!term) {
                return true;
            }

            return [
                year.name,
                year.starts_on,
                year.ends_on,
                year.is_current
                    ? 'en cours'
                    : 'archivée',
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value)
                        .toLowerCase()
                        .includes(term)
                );
        });
    }, [years, search, statusFilter]);

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    function openCreate() {
        reset();
        setEditing(null);
        setShowForm(true);
    }

    function openEdit(year) {
        setData({
            name: year.name,
            starts_on: year.starts_on ?? '',
            ends_on: year.ends_on ?? '',
            is_current: year.is_current,
        });

        setEditing(year);
        setShowForm(true);
    }

    function closeForm() {
        setShowForm(false);
        setEditing(null);
        reset();
    }

    function submit(e) {
        e.preventDefault();

        const options = {
            onSuccess: () => closeForm(),
        };

        if (editing) {
            put(
                route(
                    'admin.academic-years.update',
                    editing.id
                ),
                options
            );
        } else {
            post(
                route('admin.academic-years.store'),
                options
            );
        }
    }

    function confirmDelete() {
        if (!toDelete) {
            return;
        }

        setDeleting(true);

        router.delete(
            route(
                'admin.academic-years.destroy',
                toDelete.id
            ),
            {
                onFinish: () => {
                    setDeleting(false);
                    setToDelete(null);
                },
            }
        );
    }

    function resetFilters() {
        setSearch('');
        setStatusFilter('all');
    }

    return (
        <AuthenticatedLayout title="Années scolaires">
            <Head title="Années scolaires" />

            <div className="space-y-6">
                {/* =====================================================
                    HEADER
                ====================================================== */}

                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-blue-100">
                                <CalendarRange className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Organisation scolaire
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Années scolaires
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-blue-100 sm:text-base">
                                Gérez les différentes années
                                scolaires de votre établissement
                                et définissez l'année actuellement
                                active.
                            </p>
                        </div>

                        <Button
                            onClick={openCreate}
                            className="bg-white text-indigo-700 shadow-sm hover:bg-indigo-50"
                        >
                            <Plus className="h-4 w-4" />
                            Nouvelle année
                        </Button>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                {/* =====================================================
                    STATISTIQUES
                ====================================================== */}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={CalendarRange}
                        label="Total années"
                        value={years.length}
                        description="Années scolaires enregistrées"
                        className="from-blue-500 to-indigo-600"
                    />

                    <StatCard
                        icon={CheckCircle2}
                        label="Année en cours"
                        value={currentYears.length}
                        description="Année actuellement active"
                        className="from-emerald-500 to-teal-600"
                    />

                    <StatCard
                        icon={Archive}
                        label="Archivées"
                        value={archivedYears.length}
                        description="Anciennes années scolaires"
                        className="from-slate-500 to-slate-700"
                    />

                    <StatCard
                        icon={CalendarDays}
                        label="Dates définies"
                        value={datedYears.length}
                        description="Avec début et fin renseignés"
                        className="from-amber-500 to-orange-600"
                    />
                </div>

                {/* =====================================================
                    TABLEAU
                ====================================================== */}

                <Card>
                    <Card.Header
                        title="Liste des années scolaires"
                        actions={
                            <Button
                                onClick={openCreate}
                                size="sm"
                            >
                                <Plus className="h-4 w-4" />
                                Ajouter
                            </Button>
                        }
                    />

                    <Card.Body>
                        {/* Recherche + filtre */}

                        <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div className="relative w-full lg:max-w-xl">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                    <Input
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(
                                                e.target.value
                                            )
                                        }
                                        placeholder="Rechercher une année scolaire..."
                                        className="pl-10"
                                    />
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <FilterButton
                                        active={
                                            statusFilter ===
                                            'all'
                                        }
                                        onClick={() =>
                                            setStatusFilter(
                                                'all'
                                            )
                                        }
                                    >
                                        Toutes
                                    </FilterButton>

                                    <FilterButton
                                        active={
                                            statusFilter ===
                                            'current'
                                        }
                                        onClick={() =>
                                            setStatusFilter(
                                                'current'
                                            )
                                        }
                                    >
                                        <CheckCircle2 className="h-4 w-4" />
                                        En cours
                                    </FilterButton>

                                    <FilterButton
                                        active={
                                            statusFilter ===
                                            'archived'
                                        }
                                        onClick={() =>
                                            setStatusFilter(
                                                'archived'
                                            )
                                        }
                                    >
                                        <Archive className="h-4 w-4" />
                                        Archivées
                                    </FilterButton>
                                </div>
                            </div>

                            {(search ||
                                statusFilter !== 'all') && (
                                <div className="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                                    <p className="text-sm text-slate-500">
                                        <span className="font-semibold text-slate-700">
                                            {
                                                filteredYears.length
                                            }
                                        </span>{' '}
                                        résultat
                                        {filteredYears.length >
                                        1
                                            ? 's'
                                            : ''}
                                    </p>

                                    <button
                                        type="button"
                                        onClick={
                                            resetFilters
                                        }
                                        className="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                    >
                                        <X className="h-4 w-4" />
                                        Réinitialiser
                                    </button>
                                </div>
                            )}
                        </div>

                        <Table
                            rows={filteredYears}
                            emptyState={
                                search ||
                                statusFilter !== 'all' ? (
                                    <EmptyState
                                        icon={Search}
                                        title="Aucun résultat"
                                        description="Aucune année scolaire ne correspond aux critères sélectionnés."
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
                                        icon={CalendarRange}
                                        title="Aucune année scolaire"
                                        description="Créez la première année scolaire de l'établissement."
                                        action={
                                            <Button
                                                onClick={
                                                    openCreate
                                                }
                                            >
                                                Créer une année
                                            </Button>
                                        }
                                    />
                                )
                            }
                            columns={[
                                {
                                    key: 'name',
                                    header: 'Année',
                                    render: (year) => (
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-sm">
                                                <CalendarRange className="h-5 w-5" />
                                            </div>

                                            <div>
                                                <div className="font-semibold text-slate-800">
                                                    {year.name}
                                                </div>

                                                <div className="text-xs text-slate-400">
                                                    Année scolaire
                                                </div>
                                            </div>
                                        </div>
                                    ),
                                },

                                {
                                    key: 'starts_on',
                                    header: 'Début',
                                    render: (year) =>
                                        year.starts_on ? (
                                            <div className="flex items-center gap-2 text-sm text-slate-600">
                                                <CalendarDays className="h-4 w-4 text-blue-500" />
                                                {formatDate(
                                                    year.starts_on
                                                )}
                                            </div>
                                        ) : (
                                            <span className="text-slate-400">
                                                Non défini
                                            </span>
                                        ),
                                },

                                {
                                    key: 'ends_on',
                                    header: 'Fin',
                                    render: (year) =>
                                        year.ends_on ? (
                                            <div className="flex items-center gap-2 text-sm text-slate-600">
                                                <Clock3 className="h-4 w-4 text-orange-500" />
                                                {formatDate(
                                                    year.ends_on
                                                )}
                                            </div>
                                        ) : (
                                            <span className="text-slate-400">
                                                Non défini
                                            </span>
                                        ),
                                },

                                {
                                    key: 'is_current',
                                    header: 'Statut',
                                    render: (year) =>
                                        year.is_current ? (
                                            <Badge tone="green">
                                                <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-green-500" />
                                                En cours
                                            </Badge>
                                        ) : (
                                            <Badge>
                                                <Archive className="mr-1 h-3.5 w-3.5" />
                                                Archivée
                                            </Badge>
                                        ),
                                },

                                {
                                    key: 'actions',
                                    header: '',
                                    render: (year) => (
                                        <div className="flex justify-end gap-1">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openEdit(
                                                        year
                                                    )
                                                }
                                                className="rounded-lg p-2 text-slate-500 transition hover:bg-blue-50 hover:text-blue-600"
                                                aria-label="Modifier"
                                                title="Modifier"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setToDelete(
                                                        year
                                                    )
                                                }
                                                className="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                aria-label="Supprimer"
                                                title="Supprimer"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    ),
                                },
                            ]}
                        />
                    </Card.Body>
                </Card>
            </div>

            {/* =========================================================
                MODAL
            ========================================================== */}

            <Modal
                show={showForm}
                onClose={closeForm}
                title={
                    editing
                        ? "Modifier l'année scolaire"
                        : 'Nouvelle année scolaire'
                }
                size="lg"
                footer={
                    <>
                        <Button
                            variant="secondary"
                            onClick={closeForm}
                        >
                            Annuler
                        </Button>

                        <Button
                            onClick={submit}
                            loading={processing}
                        >
                            {editing
                                ? 'Enregistrer les modifications'
                                : "Créer l'année"}
                        </Button>
                    </>
                }
            >
                <form
                    onSubmit={submit}
                    className="space-y-6"
                >
                    {/* Informations générales */}

                    <div className="rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
                        <div className="mb-5 flex items-start gap-3">
                            <div className="rounded-xl bg-blue-100 p-2.5 text-blue-600">
                                <CalendarRange className="h-5 w-5" />
                            </div>

                            <div>
                                <h3 className="font-semibold text-slate-800">
                                    Informations générales
                                </h3>

                                <p className="mt-1 text-sm text-slate-500">
                                    Définissez l'identité et la
                                    période de l'année scolaire.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <Field
                                label="Nom de l'année"
                                required
                                error={errors.name}
                                hint="Ex : 2026-2027"
                            >
                                <Input
                                    value={data.name}
                                    onChange={(e) =>
                                        setData(
                                            'name',
                                            e.target.value
                                        )
                                    }
                                    placeholder="2026-2027"
                                />
                            </Field>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field
                                    label="Date de début"
                                    error={
                                        errors.starts_on
                                    }
                                >
                                    <Input
                                        type="date"
                                        value={
                                            data.starts_on
                                        }
                                        onChange={(e) =>
                                            setData(
                                                'starts_on',
                                                e.target.value
                                            )
                                        }
                                    />
                                </Field>

                                <Field
                                    label="Date de fin"
                                    error={
                                        errors.ends_on
                                    }
                                >
                                    <Input
                                        type="date"
                                        value={
                                            data.ends_on
                                        }
                                        onChange={(e) =>
                                            setData(
                                                'ends_on',
                                                e.target.value
                                            )
                                        }
                                    />
                                </Field>
                            </div>
                        </div>
                    </div>

                    {/* Année active */}

                    <div className="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5">
                        <div className="flex items-start gap-3">
                            <div className="rounded-xl bg-emerald-100 p-2.5 text-emerald-600">
                                <CheckCircle2 className="h-5 w-5" />
                            </div>

                            <div className="flex-1">
                                <h3 className="font-semibold text-slate-800">
                                    Année active
                                </h3>

                                <p className="mt-1 text-sm text-slate-500">
                                    L'année en cours sera utilisée
                                    par défaut pour les opérations
                                    scolaires.
                                </p>

                                <div className="mt-4 rounded-xl border border-white bg-white/80 p-3">
                                    <Checkbox
                                        label="Définir comme année en cours"
                                        checked={
                                            data.is_current
                                        }
                                        onChange={(e) =>
                                            setData(
                                                'is_current',
                                                e.target.checked
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </Modal>

            {/* =========================================================
                SUPPRESSION
            ========================================================== */}

            <ConfirmDialog
                show={!!toDelete}
                onClose={() =>
                    setToDelete(null)
                }
                onConfirm={confirmDelete}
                loading={deleting}
                title="Supprimer l'année scolaire"
                description={
                    toDelete
                        ? `Supprimer « ${toDelete.name} » ? Cette action est irréversible.`
                        : ''
                }
                variant="danger"
                confirmLabel="Supprimer"
            />
        </AuthenticatedLayout>
    );
}

/*
|--------------------------------------------------------------------------
| StatCard
|--------------------------------------------------------------------------
*/

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
            <div className="relative z-10">
                <div className="mb-4 flex items-center justify-between">
                    <div className="rounded-xl bg-white/15 p-2.5 backdrop-blur-sm">
                        <Icon className="h-5 w-5" />
                    </div>

                    <span className="text-3xl font-bold">
                        {value}
                    </span>
                </div>

                <p className="font-semibold">
                    {label}
                </p>

                <p className="mt-1 text-xs text-white/75">
                    {description}
                </p>
            </div>

            <div className="absolute -bottom-10 -right-10 h-28 w-28 rounded-full bg-white/10" />
        </div>
    );
}

/*
|--------------------------------------------------------------------------
| FilterButton
|--------------------------------------------------------------------------
*/

function FilterButton({
    active,
    onClick,
    children,
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={[
                'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition',
                active
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50',
            ].join(' ')}
        >
            {children}
        </button>
    );
}

/*
|--------------------------------------------------------------------------
| Format date
|--------------------------------------------------------------------------
*/

function formatDate(date) {
    if (!date) {
        return '—';
    }

    const parsed = new Date(`${date}T00:00:00`);

    if (Number.isNaN(parsed.getTime())) {
        return date;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(parsed);
}