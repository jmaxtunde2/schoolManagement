import { useMemo, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronUp,
    Filter,
    Search,
    RotateCcw,
    FileText,
    Clock,
    User,
    Database,
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

export default function AuditLogsIndex({ logs, filters, actions, auditableTypes }) {
    const [showFilters, setShowFilters] = useState(false);

    const stats = useMemo(() => {
        const rows = logs.data ?? [];
        const actionCounts = rows.reduce((acc, log) => {
            acc[log.action] = (acc[log.action] || 0) + 1;
            return acc;
        }, {});
        const topActions = Object.entries(actionCounts)
            .sort(([, a], [, b]) => b - a)
            .slice(0, 5);
        const types = rows.reduce((acc, log) => {
            acc[log.auditable_type] = (acc[log.auditable_type] || 0) + 1;
            return acc;
        }, {});

        return {
            total: logs.total ?? 0,
            uniqueActions: Object.keys(actionCounts).length,
            uniqueTypes: Object.keys(types).length,
            topActions,
        };
    }, [logs]);

    const columns = [
        {
            key: 'created_at',
            header: 'Date',
            className: 'whitespace-nowrap',
            render: (log) => (
                <span className="text-sm text-slate-600 font-mono">{log.created_at}</span>
            ),
        },
        {
            key: 'action',
            header: 'Action',
            render: (log) => (
                <span className="font-mono text-sm text-slate-900">{log.action}</span>
            ),
        },
        {
            key: 'user',
            header: 'Utilisateur',
            className: 'hidden lg:table-cell',
            render: (log) => (
                <span className="text-slate-700">
                    {log.user ? `${log.user.name} (${log.user.email})` : 'Système'}
                </span>
            ),
        },
        {
            key: 'auditable',
            header: 'Entité',
            className: 'hidden lg:table-cell',
            render: (log) => (
                <span className="text-slate-700 font-mono">{log.auditable_type} #{log.auditable_id}</span>
            ),
        },
        {
            key: 'actions',
            header: 'Détails',
            className: 'w-24 text-right',
            render: (log) => (
                <Link href={route('platform.audit-logs.show', log.id)}>
                    <Button variant="ghost" size="sm" className="gap-1">
                        <Eye className="h-4 w-4" strokeWidth={2} />
                    </Button>
                </Link>
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title="Journaux d'audit globaux" />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-700 via-slate-800 to-slate-900 text-white shadow-lg">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <FileText className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Journaux d'audit
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                Historique global de toutes les actions sur la plateforme.
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                <Clock className="h-5 w-5" strokeWidth={2} />
                            </div>

                            <div>
                                <p className="text-xs text-white/70">
                                    Total entrées
                                </p>

                                <p className="text-2xl font-bold">
                                    {logs.total}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Statistiques */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4 mt-6">
                <StatCard
                    icon={FileText}
                    label="Total entrées"
                    value={stats.total}
                    description="Actions enregistrées"
                    className="from-slate-600 to-slate-800"
                />
                <StatCard
                    icon={Database}
                    label="Types d'entités"
                    value={stats.uniqueTypes}
                    description="Modèles audités"
                    className="from-blue-500 to-indigo-600"
                />
                <StatCard
                    icon={Clock}
                    label="Actions uniques"
                    value={stats.uniqueActions}
                    description="Types d'opérations"
                    className="from-purple-500 to-pink-600"
                />
                <StatCard
                    icon={User}
                    label="Top action"
                    value={stats.topActions[0]?.[0] || '—'}
                    description={`${stats.topActions[0]?.[1] || 0} occurrences`}
                    className="from-emerald-500 to-teal-600"
                />
            </div>

            {/* Liste */}
            <Card className="overflow-hidden mt-6">
                <Card.Header
                    title="Historique des actions"
                    description={`${logs.total} entrée(s) — triées par date décroissante`}
                    actions={
                        <div className="flex items-center gap-2 flex-wrap">
                            <Filter className="hidden h-4 w-4 text-slate-400 sm:block" />

                            <Select
                                className="w-full sm:w-56"
                                value={filters.auditable_type || ''}
                                onChange={(e) => {
                                    const params = new URLSearchParams(window.location.search);
                                    if (e.target.value) params.set('auditable_type', e.target.value); else params.delete('auditable_type');
                                    window.location.href = route('platform.audit-logs.index') + '?' + params.toString();
                                }}
                            >
                                <option value="">Tous les types</option>
                                {auditableTypes.map((t) => (
                                    <option key={t} value={t}>{t}</option>
                                ))}
                            </Select>

                            <div className="relative hidden sm:block">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" strokeWidth={2} />
                                <Input
                                    value={filters.action || ''}
                                    onChange={(e) => {
                                        const params = new URLSearchParams(window.location.search);
                                        if (e.target.value) params.set('action', e.target.value); else params.delete('action');
                                        window.location.href = route('platform.audit-logs.index') + '?' + params.toString();
                                    }}
                                    placeholder="Filtrer par action..."
                                    className="pl-10 w-56"
                                />
                            </div>
                        </div>
                    }
                />

                <Card.Body>
                    {/* Recherche avancée */}
                    <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <div className="grid gap-4 sm:grid-cols-4">
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" strokeWidth={2} />
                                <Input
                                    value={filters.action || ''}
                                    onChange={(e) => {
                                        const params = new URLSearchParams(window.location.search);
                                        if (e.target.value) params.set('action', e.target.value); else params.delete('action');
                                        window.location.href = route('platform.audit-logs.index') + '?' + params.toString();
                                    }}
                                    placeholder="Action (ex: school.created)..."
                                    className="pl-10"
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-slate-500 mb-1">Du</label>
                                <Input
                                    type="date"
                                    value={filters.date_from || ''}
                                    onChange={(e) => {
                                        const params = new URLSearchParams(window.location.search);
                                        if (e.target.value) params.set('date_from', e.target.value); else params.delete('date_from');
                                        window.location.href = route('platform.audit-logs.index') + '?' + params.toString();
                                    }}
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-slate-500 mb-1">Au</label>
                                <Input
                                    type="date"
                                    value={filters.date_to || ''}
                                    onChange={(e) => {
                                        const params = new URLSearchParams(window.location.search);
                                        if (e.target.value) params.set('date_to', e.target.value); else params.delete('date_to');
                                        window.location.href = route('platform.audit-logs.index') + '?' + params.toString();
                                    }}
                                />
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => window.location.href = route('platform.audit-logs.index')}
                                    className="flex-1 inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-700"
                                >
                                    <RotateCcw className="h-4 w-4" strokeWidth={2} />
                                    Réinitialiser
                                </button>
                            </div>
                        </div>

                        {(filters.action || filters.auditable_type || filters.date_from || filters.date_to) && (
                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
                                <p className="text-sm text-slate-500">
                                    <span className="font-semibold text-slate-700">
                                        {logs.data.length}
                                    </span>{' '}
                                    résultat
                                    {logs.data.length > 1 ? 's' : ''}{' '}
                                    sur cette page
                                </p>
                            </div>
                        )}
                    </div>

                    <Table
                        columns={columns}
                        rows={logs.data}
                        keyField="id"
                        emptyState={
                            <EmptyState
                                icon={FileText}
                                title="Aucun journal d'audit"
                                description="Aucune entrée ne correspond aux critères sélectionnés."
                            />
                        }
                    />

                    {logs.last_page > 1 && (
                        <div className="mt-4 border-t border-slate-100 pt-4">
                            <Pagination
                                currentPage={logs.current_page}
                                lastPage={logs.last_page}
                                baseUrl={route('platform.audit-logs.index')}
                                params={filters}
                            />
                        </div>
                    )}
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}