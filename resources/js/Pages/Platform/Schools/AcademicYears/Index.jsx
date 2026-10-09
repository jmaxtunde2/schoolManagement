import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Edit,
    Filter,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    X,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Badge from '@/Components/UI/Badge';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import EmptyState from '@/Components/UI/EmptyState';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import StatCard from '@/Components/UI/StatCard';

export default function AcademicYearsIndex({ school, years }) {
    const [showCreate, setShowCreate] = useState(false);
    const [editing, setEditing] = useState(null);
    const [confirming, setConfirming] = useState(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        starts_at: '',
        ends_at: '',
        is_current: false,
    });

    const stats = {
        total: years.total ?? 0,
        current: years.data?.filter(y => y.is_current).length ?? 0,
        past: years.data?.filter(y => !y.is_current && new Date(y.ends_at) < new Date()).length ?? 0,
        future: years.data?.filter(y => !y.is_current && new Date(y.starts_at) > new Date()).length ?? 0,
    };

    function openCreate() {
        reset();
        setData({
            name: '',
            starts_at: new Date().toISOString().split('T')[0],
            ends_at: new Date(new Date().getFullYear() + 1, 6, 30).toISOString().split('T')[0],
            is_current: false,
        });
        setShowCreate(true);
    }

    function openEdit(year) {
        setEditing(year);
        setData({
            name: year.name,
            starts_at: year.starts_at?.split('T')[0] ?? '',
            ends_at: year.ends_at?.split('T')[0] ?? '',
            is_current: year.is_current,
        });
    }

    function submitCreate(event) {
        event.preventDefault();
        post(route('platform.schools.academic-years.store', school.id), {
            onSuccess: () => {
                setShowCreate(false);
                reset();
            },
        });
    }

    function submitEdit(event) {
        event.preventDefault();
        put(route('platform.schools.academic-years.update', school.id, editing.id), {
            onSuccess: () => {
                setEditing(null);
                reset();
            },
        });
    }

    function handleToggle(year) {
        router.post(route('platform.schools.academic-years.toggle', school.id, year.id));
    }

    function confirmDelete(year) {
        setConfirming({
            title: `Supprimer « ${year.name} » ?`,
            description: 'Cette action est irréversible.',
            confirmLabel: 'Supprimer',
            variant: 'destructive',
            onConfirm: () => {
                router.delete(route('platform.schools.academic-years.destroy', school.id, year.id));
            },
        });
    }

    const columns = [
        {
            key: 'name',
            header: 'Année scolaire',
            render: (year) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                        <CalendarDays className="h-4 w-4" strokeWidth={2} />
                    </div>
                    <div>
                        <p className="font-medium text-slate-900">{year.name}</p>
                        <p className="text-xs text-slate-500">
                            {year.starts_at} → {year.ends_at}
                        </p>
                    </div>
                </div>
            ),
        },
        {
            key: 'period',
            header: 'Période',
            className: 'hidden lg:table-cell',
            render: (year) => (
                <span className="text-slate-700">
                    {year.starts_at} – {year.ends_at}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Statut',
            render: (year) => (
                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${
                    year.is_current
                        ? 'bg-emerald-100 text-emerald-700'
                        : new Date(year.ends_at) < new Date()
                            ? 'bg-slate-100 text-slate-500'
                            : new Date(year.starts_at) > new Date()
                                ? 'bg-amber-100 text-amber-700'
                                : 'bg-blue-100 text-blue-700'
                }`}>
                    {year.is_current ? (
                        <>
                            <CheckCircle2 className="h-3 w-3" strokeWidth={2} />
                            Actuelle
                        </>
                    ) : new Date(year.ends_at) < new Date() ? (
                        'Terminée'
                    ) : new Date(year.starts_at) > new Date() ? (
                        'À venir'
                    ) : (
                        'En cours'
                    )}
                </span>
            ),
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-40 text-right',
            render: (year) => (
                <div className="flex items-center justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => openEdit(year)}
                        className="gap-1"
                    >
                        <Edit className="h-4 w-4" strokeWidth={2} />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleToggle(year)}
                        className="gap-1"
                    >
                        <CheckCircle2 className="h-4 w-4" strokeWidth={2} />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => confirmDelete(year)}
                        className="gap-1 text-red-600 hover:text-red-700"
                    >
                        <Trash2 className="h-4 w-4" strokeWidth={2} />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title={`Années scolaires — ${school.name}`} />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white shadow-lg mb-6">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <Link
                                href={route('platform.schools.show', school.id)}
                                className="mb-4 inline-flex items-center gap-2 text-white/80 hover:text-white transition-colors"
                            >
                                <ArrowLeft className="h-5 w-5" strokeWidth={2} />
                                Retour à l'école
                            </Link>

                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <CalendarDays className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Années scolaires
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                {school.name} — {school.slug}
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                <CalendarDays className="h-5 w-5" strokeWidth={2} />
                            </div>

                            <div>
                                <p className="text-xs text-white/70">
                                    Total années
                                </p>

                                <p className="text-2xl font-bold">
                                    {years.total}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
                <StatCard
                    icon={CalendarDays}
                    label="Total"
                    value={stats.total}
                    description="Années enregistrées"
                    className="from-blue-500 to-indigo-600"
                />
                <StatCard
                    icon={CheckCircle2}
                    label="Actuelle"
                    value={stats.current}
                    description="Année en cours"
                    className="from-emerald-500 to-teal-600"
                />
                <StatCard
                    icon={CalendarDays}
                    label="Passées"
                    value={stats.past}
                    description="Années terminées"
                    className="from-slate-500 to-slate-700"
                />
                <StatCard
                    icon={CalendarDays}
                    label="Futures"
                    value={stats.future}
                    description="Années à venir"
                    className="from-amber-500 to-orange-600"
                />
            </div>

            {/* Liste */}
            <Card className="overflow-hidden">
                <Card.Header
                    title="Liste des années scolaires"
                    description={`${years.total} année(s) enregistrée(s)`}
                    actions={
                        <Button onClick={openCreate} className="gap-2">
                            <Plus className="h-4 w-4" strokeWidth={2} />
                            Nouvelle année
                        </Button>
                    }
                />

                <Card.Body>
                    <Table
                        columns={columns}
                        rows={years.data}
                        keyField="id"
                        emptyState={
                            <EmptyState
                                icon={CalendarDays}
                                title="Aucune année scolaire"
                                description="Créez votre première année scolaire pour commencer."
                                action={
                                    <Button onClick={openCreate} className="gap-2">
                                        <Plus className="h-4 w-4" strokeWidth={2} />
                                        Créer une année
                                    </Button>
                                }
                            />
                        }
                    />

                    {years.last_page > 1 && (
                        <div className="mt-4 border-t border-slate-100 pt-4">
                            <Pagination
                                currentPage={years.current_page}
                                lastPage={years.last_page}
                                baseUrl={route('platform.schools.academic-years.index', school.id)}
                                params={{}}
                            />
                        </div>
                    )}
                </Card.Body>
            </Card>

            {/* Modal Créer */}
            {showCreate && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold text-slate-900">Nouvelle année scolaire</h2>
                            <Button variant="ghost" size="sm" onClick={() => setShowCreate(false)}>
                                <X className="h-5 w-5" strokeWidth={2} />
                            </Button>
                        </div>

                        <form onSubmit={submitCreate} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Nom *</label>
                                <Input
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Ex: 2024-2025"
                                    error={errors.name}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Début *</label>
                                    <Input
                                        type="date"
                                        value={data.starts_at}
                                        onChange={(e) => setData('starts_at', e.target.value)}
                                        error={errors.starts_at}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Fin *</label>
                                    <Input
                                        type="date"
                                        value={data.ends_at}
                                        onChange={(e) => setData('ends_at', e.target.value)}
                                        error={errors.ends_at}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_current"
                                    checked={data.is_current}
                                    onChange={(e) => setData('is_current', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                />
                                <label htmlFor="is_current" className="text-sm text-slate-700">
                                    Définir comme année actuelle
                                </label>
                            </div>

                            {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            {errors.starts_at && <p className="text-sm text-red-600">{errors.starts_at}</p>}
                            {errors.ends_at && <p className="text-sm text-red-600">{errors.ends_at}</p>}

                            <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <Button type="button" variant="secondary" onClick={() => setShowCreate(false)}>
                                    Annuler
                                </Button>
                                <Button type="submit" loading={processing} className="gap-2">
                                    <Plus className="h-4 w-4" strokeWidth={2} />
                                    Créer
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal Éditer */}
            {editing && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-semibold text-slate-900">Modifier l'année</h2>
                            <Button variant="ghost" size="sm" onClick={() => setEditing(null)}>
                                <X className="h-5 w-5" strokeWidth={2} />
                            </Button>
                        </div>

                        <form onSubmit={submitEdit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Nom *</label>
                                <Input
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    error={errors.name}
                                />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Début *</label>
                                    <Input
                                        type="date"
                                        value={data.starts_at}
                                        onChange={(e) => setData('starts_at', e.target.value)}
                                        error={errors.starts_at}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 mb-1">Fin *</label>
                                    <Input
                                        type="date"
                                        value={data.ends_at}
                                        onChange={(e) => setData('ends_at', e.target.value)}
                                        error={errors.ends_at}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_current_edit"
                                    checked={data.is_current}
                                    onChange={(e) => setData('is_current', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                />
                                <label htmlFor="is_current_edit" className="text-sm text-slate-700">
                                    Définir comme année actuelle
                                </label>
                            </div>

                            {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            {errors.starts_at && <p className="text-sm text-red-600">{errors.starts_at}</p>}
                            {errors.ends_at && <p className="text-sm text-red-600">{errors.ends_at}</p>}

                            <div className="flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <Button type="button" variant="secondary" onClick={() => setEditing(null)}>
                                    Annuler
                                </Button>
                                <Button type="submit" loading={processing} className="gap-2">
                                    Enregistrer
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            <ConfirmDialog
                show={!!confirming}
                onClose={() => setConfirming(null)}
                onConfirm={confirming?.onConfirm}
                title={confirming?.title}
                description={confirming?.description}
                confirmLabel={confirming?.confirmLabel}
                variant={confirming?.variant}
            />
        </AuthenticatedLayout>
    );
}