import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CalendarRange,
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

export default function AcademicPeriodsIndex({ school, periods, years }) {
    const [showCreate, setShowCreate] = useState(false);
    const [editing, setEditing] = useState(null);
    const [confirming, setConfirming] = useState(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        academic_year_id: '',
        starts_at: '',
        ends_at: '',
        is_active: true,
        type: 'trimestre',
    });

    const stats = {
        total: periods.total ?? 0,
        active: periods.data?.filter(p => p.is_active).length ?? 0,
        trimestres: periods.data?.filter(p => p.type === 'trimestre').length ?? 0,
        semestres: periods.data?.filter(p => p.type === 'semestre').length ?? 0,
    };

    function openCreate() {
        reset();
        const currentYear = years.find(y => y.is_current) || years[0];
        setData({
            name: '',
            academic_year_id: currentYear?.id ?? '',
            starts_at: '',
            ends_at: '',
            is_active: true,
            type: 'trimestre',
        });
        setShowCreate(true);
    }

    function openEdit(period) {
        setEditing(period);
        setData({
            name: period.name,
            academic_year_id: period.academic_year_id,
            starts_at: period.starts_at?.split('T')[0] ?? '',
            ends_at: period.ends_at?.split('T')[0] ?? '',
            is_active: period.is_active,
            type: period.type,
        });
    }

    function submitCreate(event) {
        event.preventDefault();
        post(route('platform.schools.academic-periods.store', school.id), {
            onSuccess: () => {
                setShowCreate(false);
                reset();
            },
        });
    }

    function submitEdit(event) {
        event.preventDefault();
        put(route('platform.schools.academic-periods.update', school.id, editing.id), {
            onSuccess: () => {
                setEditing(null);
                reset();
            },
        });
    }

    function handleToggle(period) {
        router.post(route('platform.schools.academic-periods.toggle', school.id, period.id));
    }

    function confirmDelete(period) {
        setConfirming({
            title: `Supprimer « ${period.name} » ?`,
            description: 'Cette action est irréversible.',
            confirmLabel: 'Supprimer',
            variant: 'destructive',
            onConfirm: () => {
                router.delete(route('platform.schools.academic-periods.destroy', school.id, period.id));
            },
        });
    }

    const columns = [
        {
            key: 'name',
            header: 'Période',
            render: (period) => (
                <div className="flex items-center gap-3">
                    <div className={`flex h-9 w-9 items-center justify-center rounded-lg ${
                        period.type === 'trimestre' ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600'
                    }`}>
                        <CalendarDays className="h-4 w-4" strokeWidth={2} />
                    </div>
                    <div>
                        <p className="font-medium text-slate-900">{period.name}</p>
                        <p className="text-xs text-slate-500">{period.academic_year?.name}</p>
                    </div>
                </div>
            ),
        },
        {
            key: 'type',
            header: 'Type',
            className: 'hidden lg:table-cell',
            render: (period) => (
                <Badge tone={period.type === 'trimestre' ? 'blue' : 'purple'}>
                    {period.type === 'trimestre' ? 'Trimestre' : 'Semestre'}
                </Badge>
            ),
        },
        {
            key: 'period',
            header: 'Dates',
            className: 'hidden lg:table-cell',
            render: (period) => (
                <span className="text-slate-700">
                    {period.starts_at} – {period.ends_at}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Statut',
            render: (period) => (
                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${
                    period.is_active
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-100 text-slate-500'
                }`}>
                    {period.is_active ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-40 text-right',
            render: (period) => (
                <div className="flex items-center justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => openEdit(period)}
                        className="gap-1"
                    >
                        <Edit className="h-4 w-4" strokeWidth={2} />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleToggle(period)}
                        className="gap-1"
                    >
                        <CalendarDays className="h-4 w-4" strokeWidth={2} />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => confirmDelete(period)}
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
            <Head title={`Périodes scolaires — ${school.name}`} />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-blue-600 text-white shadow-lg mb-6">
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
                                    <CalendarRange className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Périodes scolaires
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                {school.name} — {school.slug}
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                <CalendarRange className="h-5 w-5" strokeWidth={2} />
                            </div>

                            <div>
                                <p className="text-xs text-white/70">
                                    Total périodes
                                </p>

                                <p className="text-2xl font-bold">
                                    {periods.total}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
                <StatCard
                    icon={CalendarRange}
                    label="Total"
                    value={stats.total}
                    description="Périodes enregistrées"
                    className="from-indigo-500 to-purple-600"
                />
                <StatCard
                    icon={CalendarDays}
                    label="Actives"
                    value={stats.active}
                    description="Périodes en cours"
                    className="from-emerald-500 to-teal-600"
                />
                <StatCard
                    icon={CalendarDays}
                    label="Trimestres"
                    value={stats.trimestres}
                    description="Périodes trimestrielles"
                    className="from-blue-500 to-indigo-600"
                />
                <StatCard
                    icon={CalendarRange}
                    label="Semestres"
                    value={stats.semestres}
                    description="Périodes semestrielles"
                    className="from-purple-500 to-pink-600"
                />
            </div>

            {/* Liste */}
            <Card className="overflow-hidden">
                <Card.Header
                    title="Liste des périodes"
                    description={`${periods.total} période(s) enregistrée(s)`}
                    actions={
                        <Button onClick={openCreate} className="gap-2">
                            <Plus className="h-4 w-4" strokeWidth={2} />
                            Nouvelle période
                        </Button>
                    }
                />

                <Card.Body>
                    <Table
                        columns={columns}
                        rows={periods.data}
                        keyField="id"
                        emptyState={
                            <EmptyState
                                icon={CalendarRange}
                                title="Aucune période"
                                description="Créez votre première période scolaire."
                                action={
                                    <Button onClick={openCreate} className="gap-2">
                                        <Plus className="h-4 w-4" strokeWidth={2} />
                                        Créer une période
                                    </Button>
                                }
                            />
                        }
                    />

                    {periods.last_page > 1 && (
                        <div className="mt-4 border-t border-slate-100 pt-4">
                            <Pagination
                                currentPage={periods.current_page}
                                lastPage={periods.last_page}
                                baseUrl={route('platform.schools.academic-periods.index', school.id)}
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
                            <h2 className="text-xl font-semibold text-slate-900">Nouvelle période</h2>
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
                                    placeholder="Ex: 1er Trimestre, Semestre 1"
                                    error={errors.name}
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Année scolaire *</label>
                                <Select
                                    value={data.academic_year_id}
                                    onChange={(e) => setData('academic_year_id', e.target.value)}
                                    options={years.map(y => ({ value: y.id, label: y.name }))}
                                    error={errors.academic_year_id}
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

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Type</label>
                                <Select
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                >
                                    <option value="trimestre">Trimestre</option>
                                    <option value="semestre">Semestre</option>
                                </Select>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_active_create"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                />
                                <label htmlFor="is_active_create" className="text-sm text-slate-700">
                                    Active
                                </label>
                            </div>

                            {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            {errors.academic_year_id && <p className="text-sm text-red-600">{errors.academic_year_id}</p>}
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
                            <h2 className="text-xl font-semibold text-slate-900">Modifier la période</h2>
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

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Année scolaire *</label>
                                <Select
                                    value={data.academic_year_id}
                                    onChange={(e) => setData('academic_year_id', e.target.value)}
                                    options={years.map(y => ({ value: y.id, label: y.name }))}
                                    error={errors.academic_year_id}
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

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Type</label>
                                <Select
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                >
                                    <option value="trimestre">Trimestre</option>
                                    <option value="semestre">Semestre</option>
                                </Select>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_active_edit"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                />
                                <label htmlFor="is_active_edit" className="text-sm text-slate-700">
                                    Active
                                </label>
                            </div>

                            {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            {errors.academic_year_id && <p className="text-sm text-red-600">{errors.academic_year_id}</p>}
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