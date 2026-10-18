import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Clock,
    Eye,
    FileText,
    Filter,
    RotateCcw,
    Search,
    ShieldCheck,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import EmptyState from '@/Components/UI/EmptyState';
import Input from '@/Components/UI/Input';
import Pagination from '@/Components/UI/Pagination';
import Select from '@/Components/UI/Select';
import Table from '@/Components/UI/Table';
import { auditActionLabel } from '@/Utils/auditLabels';

export default function AuditLogsIndex({
    logs,
    filters,
    actions,
    auditableTypes,
}) {
    const [showFilters, setShowFilters] = useState(false);

    function applyFilter(key, value) {
        const params = { ...filters };

        if (value) {
            params[key] = value;
        } else {
            delete params[key];
        }

        router.get(route('admin.audit-logs.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['logs', 'filters'],
        });
    }

    const columns = [
        {
            key: 'created_at',
            header: 'Date',
            className: 'whitespace-nowrap',
            render: (log) => (
                <span className="font-mono text-sm text-slate-600">
                    {log.created_at}
                </span>
            ),
        },
        {
            key: 'action',
            header: 'Action',
            render: (log) => (
                <div>
                    <p className="font-medium text-slate-800">
                        {auditActionLabel(log.action)}
                    </p>
                    <p className="font-mono text-xs text-slate-400">
                        {log.action}
                    </p>
                </div>
            ),
        },
        {
            key: 'user',
            header: 'Utilisateur',
            className: 'hidden lg:table-cell',
            render: (log) => (
                <span className="text-slate-700">
                    {log.user ? log.user.name : 'Système'}
                </span>
            ),
        },
        {
            key: 'auditable',
            header: 'Entité',
            className: 'hidden lg:table-cell',
            render: (log) => (
                <span className="font-mono text-xs text-slate-500">
                    {log.auditable_type
                        ? `${log.auditable_type.split('\\').pop()} #${log.auditable_id}`
                        : '—'}
                </span>
            ),
        },
        {
            key: 'actions',
            header: '',
            className: 'w-16 text-right',
            render: (log) => (
                <Link href={route('admin.audit-logs.show', log.id)}>
                    <Button variant="ghost" size="sm">
                        <Eye className="h-4 w-4" />
                    </Button>
                </Link>
            ),
        },
    ];

    const hasFilters =
        filters.action ||
        filters.user_id ||
        filters.auditable_type ||
        filters.date_from ||
        filters.date_to;

    return (
        <AuthenticatedLayout title="Journal d'activité">
            <Head title="Journal d'activité" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div className="flex items-center gap-3">
                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-600 to-slate-800 text-white shadow-sm">
                        <ShieldCheck className="h-6 w-6" />
                    </div>

                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-slate-500">
                            Traçabilité
                        </p>

                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                            Journal d'activité
                        </h1>

                        <p className="mt-1 text-sm text-slate-500">
                            Suivi des opérations importantes de votre
                            établissement.
                        </p>
                    </div>
                </div>

                <Card className="overflow-hidden">
                    <Card.Header
                        title="Historique des actions"
                        description={`${logs.total} entrée(s) — accès en lecture seule`}
                        actions={
                            <div className="flex flex-wrap items-center gap-2">
                                <Filter className="hidden h-4 w-4 text-slate-400 sm:block" />

                                <Select
                                    className="w-full sm:w-56"
                                    value={filters.auditable_type || ''}
                                    onChange={(event) =>
                                        applyFilter(
                                            'auditable_type',
                                            event.target.value
                                        )
                                    }
                                >
                                    <option value="">Tous les types</option>
                                    {auditableTypes.map((type) => (
                                        <option key={type} value={type}>
                                            {type.split('\\').pop()}
                                        </option>
                                    ))}
                                </Select>

                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={() =>
                                        setShowFilters((value) => !value)
                                    }
                                >
                                    <Search className="h-4 w-4" />
                                    Filtres
                                </Button>
                            </div>
                        }
                    />

                    <Card.Body>
                        {showFilters && (
                            <div className="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-4">
                                <Input
                                    value={filters.action || ''}
                                    onChange={(event) =>
                                        applyFilter(
                                            'action',
                                            event.target.value
                                        )
                                    }
                                    placeholder="Action (ex: expense)"
                                />

                                <div>
                                    <label className="mb-1 block text-sm text-slate-500">
                                        Du
                                    </label>
                                    <Input
                                        type="date"
                                        value={filters.date_from || ''}
                                        onChange={(event) =>
                                            applyFilter(
                                                'date_from',
                                                event.target.value
                                            )
                                        }
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-sm text-slate-500">
                                        Au
                                    </label>
                                    <Input
                                        type="date"
                                        value={filters.date_to || ''}
                                        onChange={(event) =>
                                            applyFilter(
                                                'date_to',
                                                event.target.value
                                            )
                                        }
                                    />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            router.get(
                                                route(
                                                    'admin.audit-logs.index'
                                                ),
                                                {},
                                                {
                                                    preserveScroll: true,
                                                    replace: true,
                                                }
                                            )
                                        }
                                    >
                                        <RotateCcw className="h-4 w-4" />
                                        Réinitialiser
                                    </Button>
                                </div>
                            </div>
                        )}

                        <Table
                            columns={columns}
                            rows={logs.data}
                            keyField="id"
                            emptyState={
                                <EmptyState
                                    icon={FileText}
                                    title="Aucune activité"
                                    description={
                                        hasFilters
                                            ? 'Aucune entrée ne correspond aux critères sélectionnés.'
                                            : 'Les opérations importantes apparaîtront ici.'
                                    }
                                />
                            }
                        />

                        <Pagination meta={logs} />
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
