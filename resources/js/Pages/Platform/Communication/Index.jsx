import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Building2,
    Megaphone,
    Wallet,
    MessageSquare,
    Send,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    History,
    Search,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';
import StatCard from '@/Components/UI/StatCard';

const channelLabels = {
    sms: { label: 'SMS', tone: 'green' },
    whatsapp: { label: 'WhatsApp', tone: 'green' },
    email: { label: 'Email', tone: 'blue' },
    disabled: { label: 'Désactivé', tone: 'red' },
};

function formatNumber(value) {
    return new Intl.NumberFormat('fr-FR').format(value ?? 0);
}

export default function CommunicationIndex({ kpis, schools, filters }) {
    const [search, setSearch] = useState(filters.search || '');

    const navigate = (params) => {
        const query = new URLSearchParams(window.location.search);

        Object.entries(params).forEach(([key, value]) => {
            if (value) {
                query.set(key, value);
            } else {
                query.delete(key);
            }
        });

        window.location.href = `${route('platform.communication.index')}?${query.toString()}`;
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
                    <Link
                        href={route('platform.schools.show', school.id)}
                        className="font-medium text-slate-900 hover:text-blue-600"
                    >
                        {school.name}
                    </Link>
                </div>
            ),
        },
        {
            key: 'sms_balance',
            header: 'Solde SMS',
            render: (school) => (
                <div className="flex items-center gap-2">
                    <span className="text-slate-700">{formatNumber(school.sms_balance)} F</span>
                    {school.alert === 'sms' && (
                        <AlertTriangle className="h-4 w-4 text-amber-500" strokeWidth={2} />
                    )}
                </div>
            ),
        },
        { key: 'sms_budget', header: 'Budget SMS', render: (s) => `${formatNumber(s.sms_budget)} F` },
        { key: 'sms_consumed', header: 'SMS consommés', render: (s) => `${formatNumber(s.sms_consumed)} F` },
        {
            key: 'whatsapp',
            header: 'WhatsApp',
            render: (s) => (
                <span className="text-slate-700">
                    {s.whatsapp_consumed} / {s.whatsapp_budget} $
                </span>
            ),
        },
        { key: 'messages_sent', header: 'Envoyés', render: (s) => formatNumber(s.messages_sent) },
        {
            key: 'messages_failed',
            header: 'Échoués',
            render: (s) => (
                <span className={s.messages_failed > 0 ? 'font-medium text-red-600' : 'text-slate-700'}>
                    {formatNumber(s.messages_failed)}
                </span>
            ),
        },
        {
            key: 'channel_status',
            header: 'Canal actif',
            render: (s) => {
                const config = channelLabels[s.channel_status] ?? channelLabels.disabled;

                return <Badge tone={config.tone}>{config.label}</Badge>;
            },
        },
        {
            key: 'last_balance_check',
            header: 'Dernier contrôle',
            className: 'hidden lg:table-cell',
            render: (s) => <span className="text-xs text-slate-500">{s.last_balance_check || '—'}</span>,
        },
    ];

    return (
        <AuthenticatedLayout title="Communication">
            <Head title="Communication — Plateforme" />

            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 text-white shadow-lg">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="relative p-6">
                    <div className="mb-3 flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20">
                            <Megaphone className="h-5 w-5" strokeWidth={2} />
                        </div>
                        <span className="text-sm font-medium text-white/80">Administration Coriyase</span>
                    </div>
                    <h1 className="text-2xl font-bold sm:text-3xl">Suivi de la communication</h1>
                    <p className="mt-2 max-w-2xl text-sm text-white/75">
                        Consommation SMS, WhatsApp et état des canaux pour l'ensemble des établissements.
                    </p>
                </div>
            </div>

            <div className="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
                <StatCard icon={Building2} label="Écoles" value={formatNumber(kpis.total_schools)} description="Établissements suivis" className="from-blue-500 to-indigo-600" />
                <StatCard icon={Wallet} label="Budget SMS" value={`${formatNumber(kpis.sms_budget)} F`} description="Alloué" className="from-emerald-500 to-teal-600" />
                <StatCard icon={MessageSquare} label="SMS consommés" value={`${formatNumber(kpis.sms_consumed)} F`} description="Dépensé" className="from-amber-500 to-orange-600" />
                <StatCard icon={Wallet} label="SMS restants" value={`${formatNumber(kpis.sms_remaining)} F`} description="Disponible" className="from-sky-500 to-cyan-600" />
                <StatCard icon={Wallet} label="Budget WhatsApp" value={`${kpis.whatsapp_budget} $`} description="Alloué" className="from-green-500 to-emerald-600" />
                <StatCard icon={MessageSquare} label="WhatsApp consommé" value={`${kpis.whatsapp_consumed} $`} description="Dépensé" className="from-lime-500 to-green-600" />
                <StatCard icon={Wallet} label="WhatsApp restant" value={`${kpis.whatsapp_remaining} $`} description="Disponible" className="from-teal-500 to-emerald-600" />
                <StatCard icon={Send} label="Messages envoyés" value={formatNumber(kpis.messages_sent)} description="Tous canaux" className="from-indigo-500 to-purple-600" />
                <StatCard icon={CheckCircle2} label="Délivrés" value={formatNumber(kpis.messages_delivered)} description="Confirmés" className="from-emerald-500 to-green-600" />
                <StatCard icon={XCircle} label="Échoués" value={formatNumber(kpis.messages_failed)} description="À investiguer" className="from-red-500 to-rose-600" />
            </div>

            <Card className="mt-6 overflow-hidden">
                <Card.Header
                    title="Établissements"
                    description={`${schools.total} établissement(s)`}
                    actions={
                        <Link
                            href={route('platform.communication.history')}
                            className="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-800"
                        >
                            <History className="h-4 w-4" strokeWidth={2} />
                            Historique
                        </Link>
                    }
                />

                <Card.Body>
                    <div className="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:flex-row">
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && navigate({ search })}
                                placeholder="Rechercher une école..."
                                className="pl-10"
                            />
                        </div>

                        <Select
                            className="sm:w-64"
                            value={filters.alert || ''}
                            onChange={(e) => navigate({ alert: e.target.value })}
                        >
                            <option value="">Toutes</option>
                            <option value="1">Alertes de budget uniquement</option>
                        </Select>
                    </div>

                    <Table
                        columns={columns}
                        rows={schools.data}
                        keyField="id"
                        emptyState={
                            <EmptyState
                                icon={Megaphone}
                                title="Aucune donnée de communication"
                                description="Aucun établissement ne correspond aux critères."
                            />
                        }
                    />

                    <Pagination meta={schools} />
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}
