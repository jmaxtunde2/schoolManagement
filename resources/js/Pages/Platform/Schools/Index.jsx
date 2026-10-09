import { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Building2,
    ChevronDown,
    ChevronUp,
    Filter,
    Search,
    Plus,
    RotateCcw,
    Users,
    GraduationCap,
    Key,
    FileText,
    CheckCircle2,
    XCircle,
    Eye,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';
import StatCard from '@/Components/UI/StatCard';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';

const statusConfig = {
    active: { icon: CheckCircle2, color: 'bg-emerald-100 text-emerald-700', label: 'Actif', iconColor: 'text-emerald-600' },
    suspended: { icon: XCircle, color: 'bg-slate-100 text-slate-500', label: 'Suspendu', iconColor: 'text-slate-400' },
};

export default function SchoolsIndex({ schools, filters }) {
    const [showFilters, setShowFilters] = useState(false);
    const [confirming, setConfirming] = useState(null);
    const [confirmingProcessing, setConfirmingProcessing] = useState(false);

    const stats = useMemo(() => {
        const rows = schools.data ?? [];
        return {
            total: schools.total ?? 0,
            active: rows.filter((s) => s.active).length,
            suspended: rows.filter((s) => !s.active).length,
            totalUsers: rows.reduce((acc, s) => acc + (s.users_count ?? 0), 0),
            totalStudents: rows.reduce((acc, s) => acc + (s.students_count ?? 0), 0),
        };
    }, [schools]);

    const handleToggle = (school) => {
        setConfirming({
            mode: 'toggle',
            school,
            title: school.active ? 'Suspendre cette école' : 'Réactiver cette école',
            description: school.active
                ? 'Les utilisateurs ne pourront plus se connecter.'
                : "L'école redeviendra accessible.",
            confirmLabel: school.active ? 'Suspendre' : 'Réactiver',
            variant: school.active ? 'destructive' : 'primary',
            onConfirm: () => {
                setConfirmingProcessing(true);
                router.post(
                    route('platform.schools.toggle', school.id),
                    {},
                    {
                        onFinish: () => {
                            setConfirmingProcessing(false);
                            setConfirming(null);
                            window.location.reload();
                        },
                    }
                );
            },
        });
    };

    const handleDelete = (school) => {
        setConfirming({
            mode: 'delete',
            school,
            title: `Supprimer « ${school.name} » ?`,
            description: "Cette action est irréversible. Toutes les données de l'école seront perdues.",
            confirmLabel: 'Supprimer',
            variant: 'destructive',
            onConfirm: () => {
                setConfirmingProcessing(true);
                router.delete(
                    route('platform.schools.destroy', school.id),
                    {
                        onFinish: () => {
                            setConfirmingProcessing(false);
                            setConfirming(null);
                            window.location.href = route('platform.schools.index');
                        },
                    }
                );
            },
        });
    };

    const columns = [
        {
            key: 'name',
            header: 'École',
            render: (school) => (
                <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                        <Building2 className="h-4 w-4" strokeWidth={2} />
                    </div>
                    <div className="min-w-0">
                        <Link
                            href={route('platform.schools.show', school.id)}
                            className="font-medium text-slate-900 hover:text-blue-600 truncate block"
                        >
                            {school.name}
                        </Link>
                        <p className="text-xs text-slate-500">{school.slug}</p>
                    </div>
                </div>
            ),
        },
        {
            key: 'users_count',
            header: 'Utilisateurs',
            className: 'hidden lg:table-cell',
            render: (school) => (
                <span className="text-slate-700">{school.users_count}</span>
            ),
        },
        {
            key: 'students_count',
            header: 'Élèves',
            className: 'hidden lg:table-cell',
            render: (school) => (
                <span className="text-slate-700">{school.students_count}</span>
            ),
        },
        {
            key: 'status',
            header: 'Statut',
            render: (school) => {
                const config = statusConfig[school.active ? 'active' : 'suspended'];
                return (
                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${config.color}`}>
                        <config.icon className={`h-3 w-3 ${config.iconColor}`} strokeWidth={2} />
                        {config.label}
                    </span>
                );
            },
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-48 text-right',
            render: (school) => (
                <div className="flex items-center justify-end gap-2">
                    <Link href={route('platform.schools.show', school.id)}>
                        <Button variant="ghost" size="sm" className="gap-1">
                            <Eye className="h-4 w-4" strokeWidth={2} />
                        </Button>
                    </Link>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleToggle(school)}
                        disabled={confirmingProcessing}
                        className="gap-1"
                    >
                        {school.active ? <XCircle className="h-4 w-4" strokeWidth={2} /> : <CheckCircle2 className="h-4 w-4" strokeWidth={2} />}
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title="Gestion des écoles" />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white shadow-lg">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <Building2 className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Gestion des écoles
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                Consultez, recherchez et gérez les établissements de la plateforme.
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                <Users className="h-5 w-5" strokeWidth={2} />
                            </div>

                            <div>
                                <p className="text-xs text-white/70">
                                    Total établissements
                                </p>

                                <p className="text-2xl font-bold">
                                    {schools.total}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Statistiques */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4 mt-6">
                <StatCard
                    icon={Building2}
                    label="Total"
                    value={stats.total}
                    description="Établissements enregistrés"
                    className="from-blue-500 to-indigo-600"
                />
                <StatCard
                    icon={CheckCircle2}
                    label="Actifs"
                    value={stats.active}
                    description="Écoles accessibles"
                    className="from-emerald-500 to-teal-600"
                />
                <StatCard
                    icon={XCircle}
                    label="Suspendus"
                    value={stats.suspended}
                    description="Écoles en pause"
                    className="from-slate-500 to-slate-700"
                />
                <StatCard
                    icon={Users}
                    label="Utilisateurs"
                    value={stats.totalUsers}
                    description="Comptes sur la plateforme"
                    className="from-purple-500 to-pink-600"
                />
            </div>

            {/* Liste */}
            <Card className="overflow-hidden mt-6">
                <Card.Header
                    title="Liste des établissements"
                    description={`${schools.total} établissement(s) enregistré(s)`}
                    actions={
                        <div className="flex items-center gap-2">
                            <Filter className="hidden h-4 w-4 text-slate-400 sm:block" />

                            <Select
                                className="w-full sm:w-56"
                                value={filters.status || ''}
                                onChange={(e) => {
                                    const params = new URLSearchParams(window.location.search);
                                    if (e.target.value) {
                                        params.set('status', e.target.value);
                                    } else {
                                        params.delete('status');
                                    }
                                    window.location.href = route('platform.schools.index') + '?' + params.toString();
                                }}
                            >
                                <option value="">Tous les statuts</option>
                                <option value="true">Actives</option>
                                <option value="false">Suspendues</option>
                            </Select>
                        </div>
                    }
                />

                <Card.Body>
                    {/* Recherche */}
                    <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" strokeWidth={2} />

                            <Input
                                value={filters.search || ''}
                                onChange={(e) => {
                                    const params = new URLSearchParams(window.location.search);
                                    if (e.target.value) {
                                        params.set('search', e.target.value);
                                    } else {
                                        params.delete('search');
                                    }
                                    window.location.href = route('platform.schools.index') + '?' + params.toString();
                                }}
                                placeholder="Rechercher par nom, slug..."
                                className="pl-10"
                            />
                        </div>

                        {(filters.search || filters.status) && (
                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
                                <p className="text-sm text-slate-500">
                                    <span className="font-semibold text-slate-700">
                                        {schools.data.length}
                                    </span>{' '}
                                    résultat
                                    {schools.data.length > 1 ? 's' : ''}{' '}
                                    sur cette page
                                </p>

                                <button
                                    type="button"
                                    onClick={() => window.location.href = route('platform.schools.index')}
                                    className="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-700"
                                >
                                    <RotateCcw className="h-4 w-4" strokeWidth={2} />
                                    Réinitialiser
                                </button>
                            </div>
                        )}
                    </div>

                    <Table
                        columns={columns}
                        rows={schools.data}
                        keyField="id"
                        emptyState={
                            <EmptyState
                                icon={Building2}
                                title="Aucun établissement"
                                description="Aucune école ne correspond aux critères sélectionnés."
                            />
                        }
                    />

                    {schools.last_page > 1 && (
                        <div className="mt-4 border-t border-slate-100 pt-4">
                            <Pagination
                                currentPage={schools.current_page}
                                lastPage={schools.last_page}
                                baseUrl={route('platform.schools.index')}
                                params={filters}
                            />
                        </div>
                    )}
                </Card.Body>
            </Card>

            {/* Actions rapides */}
            <div className="mt-6 flex items-center justify-between">
                <p className="text-sm text-slate-500">
                    Actions rapides
                </p>
                <Link href={route('demo.request')}>
                    <Button variant="primary" className="gap-2">
                        <Plus className="h-4 w-4" strokeWidth={2} />
                        Nouvelle demande de démo
                    </Button>
                </Link>
            </div>

            <ConfirmDialog
                show={confirming?.mode === 'toggle'}
                onClose={() => setConfirming(null)}
                onConfirm={confirming?.onConfirm}
                title={confirming?.title}
                description={confirming?.description}
                confirmLabel={confirming?.confirmLabel}
                variant={confirming?.variant}
                loading={confirmingProcessing}
            />
            <ConfirmDialog
                show={confirming?.mode === 'delete'}
                onClose={() => setConfirming(null)}
                onConfirm={confirming?.onConfirm}
                title={confirming?.title}
                description={confirming?.description}
                confirmLabel={confirming?.confirmLabel}
                variant={confirming?.variant}
                loading={confirmingProcessing}
            />
        </AuthenticatedLayout>
    );
}