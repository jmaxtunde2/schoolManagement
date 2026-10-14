import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, History } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Select from '@/Components/UI/Select';
import Input from '@/Components/UI/Input';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';

const statusTones = {
    pending: 'slate',
    queued: 'blue',
    sent: 'green',
    delivered: 'green',
    failed: 'red',
};

function formatCost(cost, currency) {
    if (!cost) {
        return '—';
    }

    return `${cost} ${currency ?? ''}`.trim();
}

export default function CommunicationHistory({ notifications, schools, channels, statuses, filters }) {
    const navigate = (params) => {
        const query = new URLSearchParams(window.location.search);

        Object.entries(params).forEach(([key, value]) => {
            if (value) {
                query.set(key, value);
            } else {
                query.delete(key);
            }
        });

        window.location.href = `${route('platform.communication.history')}?${query.toString()}`;
    };

    const columns = [
        {
            key: 'created_at',
            header: 'Date',
            render: (n) => <span className="whitespace-nowrap text-slate-700">{n.created_at}</span>,
        },
        { key: 'school', header: 'École', render: (n) => n.school || '—' },
        { key: 'student', header: 'Élève', render: (n) => n.student || '—' },
        { key: 'parent', header: 'Parent', render: (n) => n.parent || '—' },
        { key: 'channel_label', header: 'Canal', render: (n) => n.channel_label },
        {
            key: 'status',
            header: 'Statut',
            render: (n) => <Badge tone={statusTones[n.status] ?? 'slate'}>{n.status_label ?? n.status}</Badge>,
        },
        { key: 'provider', header: 'Fournisseur', render: (n) => n.provider || '—' },
        { key: 'cost', header: 'Coût', render: (n) => formatCost(n.cost, n.currency) },
        {
            key: 'error',
            header: 'Erreur',
            className: 'hidden lg:table-cell max-w-xs',
            render: (n) => (
                <span className="block truncate text-xs text-red-600" title={n.error || ''}>
                    {n.error || '—'}
                </span>
            ),
        },
    ];

    return (
        <AuthenticatedLayout title="Communication">
            <Head title="Historique de communication" />

            <div className="flex items-center justify-between">
                <div>
                    <Link
                        href={route('platform.communication.index')}
                        className="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-800"
                    >
                        <ArrowLeft className="h-4 w-4" strokeWidth={2} />
                        Retour au suivi
                    </Link>
                    <h1 className="text-xl font-bold text-slate-900">Historique de communication</h1>
                </div>
            </div>

            <Card className="mt-6 overflow-hidden">
                <Card.Header
                    title="Messages"
                    description={`${notifications.total} message(s)`}
                    actions={<History className="hidden h-5 w-5 text-slate-400 sm:block" />}
                />

                <Card.Body>
                    <div className="mb-6 grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Select value={filters.school || ''} onChange={(e) => navigate({ school: e.target.value })}>
                            <option value="">Toutes les écoles</option>
                            {schools.map((school) => (
                                <option key={school.id} value={school.id}>
                                    {school.name}
                                </option>
                            ))}
                        </Select>

                        <Select value={filters.channel || ''} onChange={(e) => navigate({ channel: e.target.value })}>
                            <option value="">Tous les canaux</option>
                            {channels.map((channel) => (
                                <option key={channel.value} value={channel.value}>
                                    {channel.label}
                                </option>
                            ))}
                        </Select>

                        <Select value={filters.status || ''} onChange={(e) => navigate({ status: e.target.value })}>
                            <option value="">Tous les statuts</option>
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </Select>

                        <Input
                            type="date"
                            value={filters.from || ''}
                            onChange={(e) => navigate({ from: e.target.value })}
                            placeholder="Du"
                        />

                        <Input
                            type="date"
                            value={filters.to || ''}
                            onChange={(e) => navigate({ to: e.target.value })}
                            placeholder="Au"
                        />
                    </div>

                    <Table
                        columns={columns}
                        rows={notifications.data}
                        keyField="id"
                        emptyState={
                            <EmptyState
                                icon={History}
                                title="Aucun message"
                                description="Aucune communication ne correspond aux filtres."
                            />
                        }
                    />

                    <Pagination meta={notifications} />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}
