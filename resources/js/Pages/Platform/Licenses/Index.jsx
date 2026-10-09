import { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronUp,
    Filter,
    Search,
    Key,
    RotateCcw,
    DollarSign,
    CalendarDays,
    CheckCircle2,
    AlertCircle,
    Loader2,
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

const statusConfig = {
    active: { icon: CheckCircle2, color: 'bg-emerald-100 text-emerald-700', label: 'Active', iconColor: 'text-emerald-600' },
    expired: { icon: AlertCircle, color: 'bg-red-100 text-red-700', label: 'Expirée', iconColor: 'text-red-600' },
    pending: { icon: Loader2, color: 'bg-amber-100 text-amber-700', label: 'En attente', iconColor: 'text-amber-600' },
    suspended: { icon: XCircle, color: 'bg-slate-100 text-slate-700', label: 'Suspendue', iconColor: 'text-slate-400' },
};

export default function LicensesIndex({ licenses, filters, statusOptions, typeOptions }) {
    const [showFilters, setShowFilters] = useState(false);

    const stats = useMemo(() => {
        const rows = licenses.data ?? [];
        return {
            total: licenses.total ?? 0,
            active: rows.filter((l) => l.status === 'active').length,
            expired: rows.filter((l) => l.status === 'expired').length,
            pending: rows.filter((l) => l.status === 'pending').length,
            totalAmount: rows.reduce((acc, l) => acc + (l.amount ?? 0), 0),
        };
    }, [licenses]);

    const columns = [
        {
            key: 'school',
            header: 'École',
            render: (license) => (
                <div className="min-w-0">
                    <Link
                        href={route('platform.schools.show', license.school.id)}
                        className="font-medium text-slate-900 hover:text-blue-600 truncate block"
                    >
                        {license.school.name}
                    </Link>
                    <p className="text-xs text-slate-500">{license.school.slug}</p>
                </div>
            ),
        },
        {
            key: 'type_label',
            header: 'Type',
            render: (license) => (
                <span className="text-slate-700">{license.type_label}</span>
            ),
        },
        {
            key: 'status',
            header: 'Statut',
            render: (license) => {
                const config = statusConfig[license.status] || { icon: Key, color: 'bg-slate-100 text-slate-700', label: license.status, iconColor: 'text-slate-400' };
                return (
                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${config.color}`}>
                        <config.icon className={`h-3 w-3 ${config.iconColor}`} strokeWidth={2} />
                        {config.label}
                    </span>
                );
            },
        },
        {
            key: 'period',
            header: 'Période',
            className: 'hidden lg:table-cell',
            render: (license) => {
                if (!license.starts_at || !license.ends_at) return <span className="text-slate-400">—</span>;
                return (
                    <div>
                        <p className="text-sm text-slate-700">{license.starts_at} → {license.ends_at}</p>
                        {license.days_remaining !== null && license.days_remaining > 0 && (
                            <span className="text-xs text-amber-600">{license.days_remaining} jour(s) restant(s)</span>
                        )}
                        {license.is_expired && <span className="text-xs text-red-600">Expirée</span>}
                    </div>
                );
            },
        },
        {
            key: 'amount',
            header: 'Montant',
            className: 'hidden lg:table-cell',
            render: (license) => (
                <span className="text-slate-700 font-medium">{Number(license.amount).toLocaleString('fr-FR')} {license.currency}</span>
            ),
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-32 text-right',
            render: (license) => (
                <Link href={route('platform.licenses.show', license.id)}>
                    <Button variant="ghost" size="sm" className="gap-1">
                        <Eye className="h-4 w-4" strokeWidth={2} />
                    </Button>
                </Link>
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title="Gestion des licences" />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 text-white shadow-lg">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <Key className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Gestion des licences
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                Suivez les licences, statuts et renouvellements de toutes les écoles.
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                <DollarSign className="h-5 w-5" strokeWidth={2} />
                            </div>

                            <div>
                                <p className="text-xs text-white/70">
                                    Total licences
                                </p>

                                <p className="text-2xl font-bold">
                                    {licenses.total}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Statistiques */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-5 mt-6">
                <StatCard
                    icon={Key}
                    label="Total"
                    value={stats.total}
                    description="Licences enregistrées"
                    className="from-purple-500 to-indigo-600"
                />
                <StatCard
                    icon={CheckCircle2}
                    label="Actives"
                    value={stats.active}
                    description="Licences valides"
                    className="from-emerald-500 to-teal-600"
                />
                <StatCard
                    icon={AlertCircle}
                    label="Expirées"
                    value={stats.expired}
                    description="À renouveler"
                    className="from-red-500 to-orange-600"
                />
                <StatCard
                    icon={Loader2}
                    label="En attente"
                    value={stats.pending}
                    description="Paiement en cours"
                    className="from-amber-500 to-orange-600"
                />
                <StatCard
                    icon={DollarSign}
                    label="Montant total"
                    value={Number(stats.totalAmount).toLocaleString('fr-FR')} FCFA
                    description="Valeur des licences"
                    className="from-blue-500 to-cyan-600"
                />
            </div>

            {/* Liste */}
            <Card className="overflow-hidden mt-6">
                <Card.Header
                    title="Liste des licences"
                    description={`${licenses.total} licence(s) enregistrée(s)`}
                    actions={
                        <div className="flex items-center gap-2 flex-wrap">
                            <Filter className="hidden h-4 w-4 text-slate-400 sm:block" />

                            <Select
                                className="w-full sm:w-48"
                                value={filters.status || ''}
                                onChange={(e) => {
                                    const params = new URLSearchParams(window.location.search);
                                    if (e.target.value) params.set('status', e.target.value); else params.delete('status');
                                    window.location.href = route('platform.licenses.index') + '?' + params.toString();
                                }}
                            >
                                <option value="">Tous les statuts</option>
                                {Object.entries(statusOptions).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </Select>

                            <Select
                                className="w-full sm:w-48"
                                value={filters.type || ''}
                                onChange={(e) => {
                                    const params = new URLSearchParams(window.location.search);
                                    if (e.target.value) params.set('type', e.target.value); else params.delete('type');
                                    window.location.href = route('platform.licenses.index') + '?' + params.toString();
                                }}
                            >
                                <option value="">Tous les types</option>
                                {Object.entries(typeOptions).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
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
                                    if (e.target.value) params.set('search', e.target.value); else params.delete('search');
                                    window.location.href = route('platform.licenses.index') + '?' + params.toString();
                                }}
                                placeholder="Rechercher par école..."
                                className="pl-10"
                            />
                        </div>

                        {(filters.search || filters.status || filters.type) && (
                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
                                <p className="text-sm text-slate-500">
                                    <span className="font-semibold text-slate-700">
                                        {licenses.data.length}
                                    </span>{' '}
                                    résultat
                                    {licenses.data.length > 1 ? 's' : ''}{' '}
                                    sur cette page
                                </p>

                                <button
                                    type="button"
                                    onClick={() => window.location.href = route('platform.licenses.index')}
                                    className="inline-flex items-center gap-1.5 text-sm font-medium text-purple-600 hover:text-purple-700"
                                >
                                    <RotateCcw className="h-4 w-4" strokeWidth={2} />
                                    Réinitialiser
                                </button>
                            </div>
                        )}
                    </div>

                    <Table
                        columns={columns}
                        rows={licenses.data}
                        keyField="id"
                        emptyState={
                            <EmptyState
                                icon={Key}
                                title="Aucune licence"
                                description="Aucune licence ne correspond aux critères sélectionnés."
                            />
                        }
                    />

                    {licenses.last_page > 1 && (
                        <div className="mt-4 border-t border-slate-100 pt-4">
                            <Pagination
                                currentPage={licenses.current_page}
                                lastPage={licenses.last_page}
                                baseUrl={route('platform.licenses.index')}
                                params={filters}
                            />
                        </div>
                    )}
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}