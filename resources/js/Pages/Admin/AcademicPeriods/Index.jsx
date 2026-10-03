import { useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    CalendarRange,
    CalendarDays,
    Search,
    Lock,
    LockOpen,
    Layers,
    X,
    CheckCircle2,
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
import Select from '@/Components/UI/Select';
import Checkbox from '@/Components/UI/Checkbox';

const empty = {
    academic_year_id: '',
    name: '',
    position: '',
    starts_at: '',
    ends_at: '',
    is_closed: false,
};

export default function AcademicPeriodsIndex({ years, periods }) {
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const [search, setSearch] = useState('');
    const [yearFilter, setYearFilter] = useState('all');
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

    const stats = useMemo(
        () => ({
            total: periods.length,
            open: periods.filter((period) => !period.is_closed).length,
            closed: periods.filter((period) => period.is_closed).length,
            dated: periods.filter((period) => period.starts_at && period.ends_at)
                .length,
        }),
        [periods]
    );

    /*
    |--------------------------------------------------------------------------
    | Recherche + filtres
    |--------------------------------------------------------------------------
    */

    const filteredPeriods = useMemo(() => {
        const term = search.trim().toLowerCase();

        return periods.filter((period) => {
            if (yearFilter !== 'all' && String(period.academic_year_id) !== yearFilter) {
                return false;
            }

            if (statusFilter === 'open' && period.is_closed) {
                return false;
            }

            if (statusFilter === 'closed' && !period.is_closed) {
                return false;
            }

            if (!term) {
                return true;
            }

            return [period.name, period.academic_year]
                .filter(Boolean)
                .some((value) =>
                    String(value).toLowerCase().includes(term)
                );
        });
    }, [periods, search, yearFilter, statusFilter]);

    const hasFilters = search !== '' || yearFilter !== 'all' || statusFilter !== 'all';

    function resetFilters() {
        setSearch('');
        setYearFilter('all');
        setStatusFilter('all');
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    function openCreate() {
        reset();

        setData({
            ...empty,
            academic_year_id:
                yearFilter !== 'all' ? yearFilter : years[0]?.id ?? '',
            position: String(periods.length + 1),
        });

        setEditing(null);
        setShowForm(true);
    }

    function openEdit(period) {
        setData({
            academic_year_id: period.academic_year_id,
            name: period.name,
            position: period.position,
            starts_at: period.starts_at ?? '',
            ends_at: period.ends_at ?? '',
            is_closed: period.is_closed,
        });

        setEditing(period);
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
            put(route('admin.academic-periods.update', editing.id), options);
        } else {
            post(route('admin.academic-periods.store'), options);
        }
    }

    function confirmDelete() {
        if (!toDelete) {
            return;
        }

        setDeleting(true);

        router.delete(route('admin.academic-periods.destroy', toDelete.id), {
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    }

    function toggle(period) {
        router.post(route('admin.academic-periods.toggle', period.id), {
            preserveScroll: true,
        });
    }

    return (
        <AuthenticatedLayout title="Périodes scolaires">
            <Head title="Périodes scolaires" />

            <div className="space-y-6">
                {/* =====================================================
                    HEADER
                ====================================================== */}

                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-emerald-50">
                                <Layers className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Découpage scolaire
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Périodes scolaires
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-emerald-50 sm:text-base">
                                Découpez chaque année scolaire en
                                trimestres ou semestres. Ces périodes
                                portent les évaluations, les absences
                                et les bulletins.
                            </p>
                        </div>

                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Button
                                onClick={openCreate}
                                className="bg-white text-emerald-700 shadow-sm hover:bg-emerald-50"
                                disabled={years.length === 0}
                            >
                                <Plus className="h-4 w-4" />
                                Nouvelle période
                            </Button>
                        </div>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                {/* =====================================================
                    STATISTIQUES
                ====================================================== */}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={Layers}
                        label="Total périodes"
                        value={stats.total}
                        description="Périodes configurées"
                        className="from-emerald-500 to-teal-600"
                    />

                    <StatCard
                        icon={CheckCircle2}
                        label="Ouvertes"
                        value={stats.open}
                        description="Acceptent de nouveaux bulletins"
                        className="from-blue-500 to-indigo-600"
                    />

                    <StatCard
                        icon={Lock}
                        label="Clôturées"
                        value={stats.closed}
                        description="Données figées"
                        className="from-slate-500 to-slate-700"
                    />

                    <StatCard
                        icon={CalendarDays}
                        label="Dates définies"
                        value={stats.dated}
                        description="Avec début et fin renseignés"
                        className="from-amber-500 to-orange-600"
                    />
                </div>

                {/* =====================================================
                    AUCUNE ANNÉE SCOLAIRE
                ====================================================== */}

                {years.length === 0 && (
                    <Card>
                        <Card.Body>
                            <EmptyState
                                icon={CalendarRange}
                                title="Aucune année scolaire"
                                description="Créez d'abord une année scolaire avant de découper l'année en périodes."
                                action={
                                    <LinkToAcademicYears />
                                }
                            />
                        </Card.Body>
                    </Card>
                )}

                {/* =====================================================
                    TABLEAU
                ====================================================== */}

                {years.length > 0 && (
                    <Card>
                        <Card.Header
                            title="Liste des périodes"
                            actions={
                                <Button onClick={openCreate} size="sm">
                                    <Plus className="h-4 w-4" />
                                    Ajouter
                                </Button>
                            }
                        />

                        <Card.Body>
                            <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div className="relative w-full lg:max-w-md">
                                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                        <Input
                                            value={search}
                                            onChange={(e) =>
                                                setSearch(e.target.value)
                                            }
                                            placeholder="Rechercher une période..."
                                            className="pl-10"
                                        />
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <Select
                                            value={yearFilter}
                                            onChange={(e) =>
                                                setYearFilter(e.target.value)
                                            }
                                            className="w-auto min-w-[170px]"
                                            aria-label="Filtrer par année scolaire"
                                        >
                                            <option value="all">
                                                Toutes les années
                                            </option>

                                            {years.map((year) => (
                                                <option
                                                    key={year.id}
                                                    value={year.id}
                                                >
                                                    {year.name}
                                                </option>
                                            ))}
                                        </Select>

                                        <FilterButton
                                            active={statusFilter === 'all'}
                                            onClick={() =>
                                                setStatusFilter('all')
                                            }
                                        >
                                            Toutes
                                        </FilterButton>

                                        <FilterButton
                                            active={statusFilter === 'open'}
                                            onClick={() =>
                                                setStatusFilter('open')
                                            }
                                        >
                                            <LockOpen className="h-4 w-4" />
                                            Ouvertes
                                        </FilterButton>

                                        <FilterButton
                                            active={
                                                statusFilter === 'closed'
                                            }
                                            onClick={() =>
                                                setStatusFilter('closed')
                                            }
                                        >
                                            <Lock className="h-4 w-4" />
                                            Clôturées
                                        </FilterButton>
                                    </div>
                                </div>

                                {hasFilters && (
                                    <div className="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                                        <p className="text-sm text-slate-500">
                                            <span className="font-semibold text-slate-700">
                                                {filteredPeriods.length}
                                            </span>{' '}
                                            résultat
                                            {filteredPeriods.length > 1
                                                ? 's'
                                                : ''}
                                        </p>

                                        <button
                                            type="button"
                                            onClick={resetFilters}
                                            className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700"
                                        >
                                            <X className="h-4 w-4" />
                                            Réinitialiser
                                        </button>
                                    </div>
                                )}
                            </div>

                            <Table
                                rows={filteredPeriods}
                                emptyState={
                                    hasFilters ? (
                                        <EmptyState
                                            icon={Search}
                                            title="Aucun résultat"
                                            description="Aucune période ne correspond aux critères sélectionnés."
                                            action={
                                                <Button
                                                    variant="secondary"
                                                    onClick={resetFilters}
                                                >
                                                    Réinitialiser
                                                </Button>
                                            }
                                        />
                                    ) : (
                                        <EmptyState
                                            icon={Layers}
                                            title="Aucune période scolaire"
                                            description="Découpez l'année scolaire en trimestres ou semestres pour pouvoir saisir des notes et éditer des bulletins."
                                            action={
                                                <Button onClick={openCreate}>
                                                    <Plus className="h-4 w-4" />
                                                    Créer une période
                                                </Button>
                                            }
                                        />
                                    )
                                }
                                columns={[
                                    {
                                        key: 'name',
                                        header: 'Période',
                                        render: (period) => (
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                                                    <Layers className="h-5 w-5" />
                                                </div>

                                                <div>
                                                    <div className="font-semibold text-slate-800">
                                                        {period.name}
                                                    </div>

                                                    <div className="text-xs text-slate-400">
                                                        {period.academic_year ??
                                                            '—'}
                                                    </div>
                                                </div>
                                            </div>
                                        ),
                                    },

                                    {
                                        key: 'position',
                                        header: 'Ordre',
                                        render: (period) => (
                                            <span className="inline-flex h-7 min-w-7 items-center justify-center rounded-lg bg-slate-100 px-2 text-sm font-semibold text-slate-700">
                                                {period.position}
                                            </span>
                                        ),
                                    },

                                    {
                                        key: 'dates',
                                        header: 'Période',
                                        render: (period) =>
                                            period.starts_at &&
                                            period.ends_at ? (
                                                <div className="text-sm text-slate-600">
                                                    <div className="flex items-center gap-2">
                                                        <CalendarDays className="h-4 w-4 text-emerald-500" />
                                                        {formatDate(
                                                            period.starts_at
                                                        )}
                                                    </div>

                                                    <div className="mt-0.5 flex items-center gap-2 text-xs text-slate-400">
                                                        <CalendarDays className="h-3.5 w-3.5 text-orange-400" />
                                                        {formatDate(
                                                            period.ends_at
                                                        )}
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="text-slate-400">
                                                    Dates non définies
                                                </span>
                                            ),
                                    },

                                    {
                                        key: 'usage',
                                        header: 'Données',
                                        render: (period) => (
                                            <div className="flex flex-wrap gap-1.5 text-xs">
                                                {period.evaluations_count > 0 && (
                                                    <Badge tone="blue">
                                                        {period.evaluations_count}{' '}
                                                        éval.
                                                    </Badge>
                                                )}

                                                {period.attendance_count >
                                                    0 && (
                                                    <Badge tone="amber">
                                                        {
                                                            period.attendance_count
                                                        }{' '}
                                                        abs.
                                                    </Badge>
                                                )}

                                                {period.report_cards_count >
                                                    0 && (
                                                    <Badge tone="green">
                                                        {
                                                            period.report_cards_count
                                                        }{' '}
                                                        bulletins
                                                    </Badge>
                                                )}

                                                {!period.is_used && (
                                                    <Badge tone="slate">
                                                        Vide
                                                    </Badge>
                                                )}
                                            </div>
                                        ),
                                    },

                                    {
                                        key: 'status',
                                        header: 'Statut',
                                        render: (period) =>
                                            period.is_closed ? (
                                                <Badge tone="slate">
                                                    <Lock className="mr-1 h-3.5 w-3.5" />
                                                    Close
                                                </Badge>
                                            ) : (
                                                <Badge tone="green">
                                                    <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-green-500" />
                                                    Ouverte
                                                </Badge>
                                            ),
                                    },

                                    {
                                        key: 'actions',
                                        header: '',
                                        render: (period) => (
                                            <div className="flex justify-end gap-1">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        toggle(period)
                                                    }
                                                    className={`rounded-lg p-2 transition ${
                                                        period.is_closed
                                                            ? 'text-slate-400 hover:bg-emerald-50 hover:text-emerald-600'
                                                            : 'text-emerald-600 hover:bg-emerald-50'
                                                    }`}
                                                    aria-label={
                                                        period.is_closed
                                                            ? 'Rouvrir la période'
                                                            : 'Clôturer la période'
                                                    }
                                                    title={
                                                        period.is_closed
                                                            ? 'Rouvrir la période'
                                                            : 'Clôturer la période'
                                                    }
                                                >
                                                    {period.is_closed ? (
                                                        <LockOpen className="h-4 w-4" />
                                                    ) : (
                                                        <Lock className="h-4 w-4" />
                                                    )}
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        openEdit(period)
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
                                                        setToDelete(period)
                                                    }
                                                    className={`rounded-lg p-2 transition ${
                                                        period.is_used
                                                            ? 'cursor-not-allowed text-slate-300'
                                                            : 'text-slate-400 hover:bg-red-50 hover:text-red-600'
                                                    }`}
                                                    disabled={
                                                        period.is_used
                                                    }
                                                    aria-label="Supprimer"
                                                    title={
                                                        period.is_used
                                                            ? 'Période utilisée : clôturez-la plutôt'
                                                            : 'Supprimer'
                                                    }
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
                )}
            </div>

            {/* =========================================================
                MODAL
            ========================================================== */}

            <Modal
                show={showForm}
                onClose={closeForm}
                title={
                    editing ? 'Modifier la période' : 'Nouvelle période scolaire'
                }
                size="lg"
                footer={
                    <>
                        <Button variant="secondary" onClick={closeForm}>
                            Annuler
                        </Button>

                        <Button onClick={submit} loading={processing}>
                            {editing
                                ? 'Enregistrer les modifications'
                                : 'Créer la période'}
                        </Button>
                    </>
                }
            >
                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5">
                        <div className="mb-5 flex items-start gap-3">
                            <div className="rounded-xl bg-emerald-100 p-2.5 text-emerald-600">
                                <Layers className="h-5 w-5" />
                            </div>

                            <div>
                                <h3 className="font-semibold text-slate-800">
                                    Informations générales
                                </h3>

                                <p className="mt-1 text-sm text-slate-500">
                                    Rattachez la période à une année
                                    scolaire et définissez son ordre
                                    d'affichage.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <Field
                                label="Année scolaire"
                                required
                                error={errors.academic_year_id}
                            >
                                <Select
                                    value={data.academic_year_id}
                                    onChange={(e) =>
                                        setData(
                                            'academic_year_id',
                                            e.target.value
                                        )
                                    }
                                    error={errors.academic_year_id}
                                >
                                    <option value="">
                                        Sélectionnez une année
                                    </option>

                                    {years.map((year) => (
                                        <option key={year.id} value={year.id}>
                                            {year.name}
                                            {year.is_current
                                                ? ' (en cours)'
                                                : ''}
                                        </option>
                                    ))}
                                </Select>
                            </Field>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Field
                                    label="Nom"
                                    required
                                    error={errors.name}
                                    hint="Ex : Trimestre 1"
                                >
                                    <Input
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Trimestre 1"
                                        error={errors.name}
                                    />
                                </Field>

                                <Field
                                    label="Ordre"
                                    required
                                    error={errors.position}
                                    hint="1 = première période"
                                >
                                    <Input
                                        type="number"
                                        min="1"
                                        max="60"
                                        value={data.position}
                                        onChange={(e) =>
                                            setData(
                                                'position',
                                                e.target.value
                                            )
                                        }
                                        error={errors.position}
                                    />
                                </Field>

                                <div className="flex items-end pb-1">
                                    <Checkbox
                                        label="Période close"
                                        checked={data.is_closed}
                                        onChange={(e) =>
                                            setData(
                                                'is_closed',
                                                e.target.checked
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
                        <div className="flex items-start gap-3">
                            <div className="rounded-xl bg-blue-100 p-2.5 text-blue-600">
                                <CalendarDays className="h-5 w-5" />
                            </div>

                            <div className="flex-1">
                                <h3 className="font-semibold text-slate-800">
                                    Dates
                                </h3>

                                <p className="mt-1 text-sm text-slate-500">
                                    Utilisées pour regrouper les absences
                                    de la période sur les bulletins.
                                </p>

                                <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Date de début"
                                        error={errors.starts_at}
                                    >
                                        <Input
                                            type="date"
                                            value={data.starts_at}
                                            onChange={(e) =>
                                                setData(
                                                    'starts_at',
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Date de fin"
                                        error={errors.ends_at}
                                    >
                                        <Input
                                            type="date"
                                            value={data.ends_at}
                                            onChange={(e) =>
                                                setData(
                                                    'ends_at',
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </Field>
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
                onClose={() => setToDelete(null)}
                onConfirm={confirmDelete}
                loading={deleting}
                title="Supprimer la période"
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

function StatCard({ icon: Icon, label, value, description, className }) {
    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${className} p-5 text-white shadow-md`}
        >
            <div className="relative z-10">
                <div className="mb-4 flex items-center justify-between">
                    <div className="rounded-xl bg-white/15 p-2.5 backdrop-blur-sm">
                        <Icon className="h-5 w-5" />
                    </div>

                    <span className="text-3xl font-bold">{value}</span>
                </div>

                <p className="font-semibold">{label}</p>

                <p className="mt-1 text-xs text-white/75">{description}</p>
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

function FilterButton({ active, onClick, children }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={[
                'inline-flex min-h-[44px] items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition',
                active
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50',
            ].join(' ')}
        >
            {children}
        </button>
    );
}

/*
|--------------------------------------------------------------------------
| Raccourci vers les années scolaires
|--------------------------------------------------------------------------
*/

function LinkToAcademicYears() {
    return (
        <Link
            href={route('admin.academic-years.index')}
            className="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700"
        >
            <CalendarRange className="h-4 w-4" />
            Gérer les années scolaires
        </Link>
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
