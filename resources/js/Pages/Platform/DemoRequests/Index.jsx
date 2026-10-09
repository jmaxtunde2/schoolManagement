import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    ClipboardList,
    Search,
    SlidersHorizontal,
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

const toneByStatus = {
    pending: 'amber',
    contacted: 'blue',
    scheduled: 'blue',
    demo_done: 'slate',
    approved: 'green',
    rejected: 'red',
    cancelled: 'slate',
};

export default function DemoRequestsIndex({
    demoRequests,
    filters,
    counts,
    statusOptions,
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    function apply(next = {}) {
        router.get(
            route('platform.demo-requests.index'),
            {
                search,
                status,
                ...next,
            },
            { preserveState: true, replace: true }
        );
    }

    const columns = [
        {
            key: 'school_name',
            header: 'Établissement',
            render: (row) => (
                <div>
                    <p className="font-medium text-slate-900">
                        {row.school_name}
                    </p>
                    <p className="mt-0.5 text-xs text-slate-500">
                        {row.email} · {row.phone}
                    </p>
                </div>
            ),
        },
        {
            key: 'preferred_demo_date',
            header: 'Créneau souhaité',
            render: (row) => (
                <span className="whitespace-nowrap text-slate-700">
                    {row.preferred_slot}
                </span>
            ),
        },
        {
            key: 'status',
            header: 'Statut',
            render: (row) => (
                <Badge tone={toneByStatus[row.status] ?? 'slate'}>
                    {row.status_label}
                </Badge>
            ),
        },
        {
            key: 'handler',
            header: 'Traité par',
            render: (row) => (
                <span className="text-slate-600">
                    {row.handler?.name ?? '—'}
                </span>
            ),
        },
        {
            key: 'created_at',
            header: 'Reçue le',
            render: (row) => (
                <span className="whitespace-nowrap text-slate-500">
                    {row.created_at}
                </span>
            ),
        },
        {
            key: 'actions',
            header: '',
            className: 'w-12',
            render: (row) => (
                <Link
                    href={route('platform.demo-requests.show', row.id)}
                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-emerald-700"
                    aria-label={`Ouvrir la demande ${row.school_name}`}
                >
                    <ArrowRight className="h-4 w-4" />
                </Link>
            ),
        },
    ];

    const total = Object.values(counts).reduce((sum, n) => sum + n, 0);

    return (
        <AuthenticatedLayout title="Demandes de démonstration">
            <Head title="Demandes de démonstration" />

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                {Object.entries(statusOptions).map(([value, label]) => (
                    <button
                        key={value}
                        type="button"
                        onClick={() => setStatus(value === status ? '' : value)}
                        className={`rounded-xl border p-3 text-left transition ${
                            status === value
                                ? 'border-emerald-500 bg-emerald-50'
                                : 'border-slate-200 bg-white hover:border-slate-300'
                        }`}
                    >
                        <p className="truncate text-xs font-medium text-slate-500">
                            {label}
                        </p>
                        <p className="mt-1 text-xl font-semibold text-slate-900">
                            {counts[value] ?? 0}
                        </p>
                    </button>
                ))}

                <div className="rounded-xl border border-slate-200 bg-white p-3 text-left">
                    <p className="text-xs font-medium text-slate-500">
                        Total
                    </p>
                    <p className="mt-1 text-xl font-semibold text-slate-900">
                        {total}
                    </p>
                </div>
            </div>

            <Card className="mt-6">
                <Card.Header
                    title="Demandes reçues"
                    description="Contact, planification, démonstration, approbation."
                    actions={
                        <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    apply();
                                }}
                                className="relative"
                            >
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    value={search}
                                    onChange={(e) =>
                                        setSearch(e.target.value)
                                    }
                                    placeholder="École, e-mail, téléphone…"
                                    className="pl-9"
                                    aria-label="Rechercher"
                                />
                            </form>

                            <div className="flex gap-2">
                                <Select
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                    aria-label="Filtrer par statut"
                                    className="min-w-[11rem]"
                                >
                                    <option value="">Tous les statuts</option>
                                    {Object.entries(statusOptions).map(
                                        ([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        )
                                    )}
                                </Select>

                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => apply()}
                                >
                                    <SlidersHorizontal className="h-4 w-4" />
                                    Filtrer
                                </Button>
                            </div>
                        </div>
                    }
                />

                <Card.Body>
                    <Table
                        columns={columns}
                        rows={demoRequests.data}
                        emptyState={
                            <EmptyState
                                icon={ClipboardList}
                                title="Aucune demande correspondante"
                                description="Ajustez la recherche ou le filtre de statut."
                                action={
                                    (search || status) && (
                                        <Button
                                            variant="secondary"
                                            onClick={() => {
                                                setSearch('');
                                                setStatus('');
                                                router.get(
                                                    route(
                                                        'platform.demo-requests.index'
                                                    ),
                                                    {},
                                                    { preserveState: true }
                                                );
                                            }}
                                        >
                                            Réinitialiser les filtres
                                        </Button>
                                    )
                                }
                            />
                        }
                    />

                    <Pagination meta={demoRequests} />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}
