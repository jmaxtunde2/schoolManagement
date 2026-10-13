import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Megaphone,
    Plus,
    Users,
    Send,
    CalendarClock,
    RotateCcw,
    Ban,
    Trash2,
    Eye,
    CheckCircle2,
    XCircle,
    Clock3,
    Mail,
    MessageCircle,
    GraduationCap,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';
import EmptyState from '@/Components/UI/EmptyState';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';

const statusTones = {
    draft: 'slate',
    scheduled: 'amber',
    sending: 'blue',
    sent: 'green',
    partial: 'amber',
    failed: 'red',
    cancelled: 'slate',
};

const statusLabels = {
    draft: 'Brouillon',
    scheduled: 'Programmée',
    sending: 'En cours',
    sent: 'Envoyée',
    partial: 'Partielle',
    failed: 'Échec',
    cancelled: 'Annulée',
};

function formatDate(value) {
    if (!value) return '—';

    return new Date(value).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function AnnouncementsIndex({
    announcements,
    filters,
    counts,
}) {
    const [status, setStatus] = useState(filters.status ?? '');
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    function applyFilter(value) {
        setStatus(value);

        router.get(
            route('communication.announcements.index'),
            { status: value },
            { preserveState: true, replace: true }
        );
    }

    function confirmDelete() {
        if (!toDelete) return;

        setDeleting(true);

        router.delete(
            route('communication.announcements.destroy', toDelete.id),
            {
                onFinish: () => {
                    setDeleting(false);
                    setToDelete(null);
                },
            }
        );
    }

    const summary = [
        {
            key: 'sent',
            label: 'Envoyées',
            value: Number(counts.sent ?? 0) + Number(counts.partial ?? 0),
            icon: CheckCircle2,
            className: 'from-emerald-500 to-teal-600',
        },
        {
            key: 'scheduled',
            label: 'Programmées',
            value: counts.scheduled ?? 0,
            icon: CalendarClock,
            className: 'from-amber-500 to-orange-600',
        },
        {
            key: 'sending',
            label: 'En cours',
            value: counts.sending ?? 0,
            icon: Send,
            className: 'from-blue-500 to-indigo-600',
        },
        {
            key: 'failed',
            label: 'Échecs',
            value: counts.failed ?? 0,
            icon: XCircle,
            className: 'from-red-500 to-rose-600',
        },
    ];

    return (
        <AuthenticatedLayout title="Annonces aux parents">
            <Head title="Annonces aux parents" />

            <div className="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-emerald-600 to-green-700 text-white shadow-lg">
                <div className="relative p-5 sm:p-6">
                    <div className="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10" />
                    <div className="absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-white/5" />

                    <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                                    <Megaphone className="h-5 w-5" />
                                </div>

                                <span className="text-sm font-medium text-white/80">
                                    Communication
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Annonces aux parents
                            </h1>

                            <p className="mt-1 max-w-xl text-sm text-white/75">
                                Informez les parents par WhatsApp et email :
                                réunions, convocations, rappels…
                            </p>
                        </div>

                        <Button
                            as={Link}
                            href={route('communication.announcements.create')}
                            className="shrink-0 bg-white text-white shadow-sm hover:bg-teal-50"
                        >
                            <Plus className="h-4 w-4" />
                            Nouvelle annonce
                        </Button>
                    </div>
                </div>
            </div>

            <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                {summary.map((item) => {
                    const Icon = item.icon;

                    return (
                        <div
                            key={item.key}
                            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${item.className} p-5 text-white shadow-md`}
                        >
                            <div className="absolute -right-5 -top-5 h-20 w-20 rounded-full bg-white/10" />

                            <div className="relative flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-white/75">
                                        {item.label}
                                    </p>

                                    <p className="mt-1 text-2xl font-bold">
                                        {item.value}
                                    </p>
                                </div>

                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20">
                                    <Icon className="h-5 w-5" />
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            <Card className="overflow-hidden">
                <Card.Header
                    title="Historique des annonces"
                    description={`${announcements.total} annonce(s)`}
                    actions={
                        <Select
                            className="w-full sm:w-56"
                            value={status}
                            onChange={(e) => applyFilter(e.target.value)}
                        >
                            <option value="">Tous les statuts</option>

                            {Object.entries(statusLabels).map(([key, label]) => (
                                <option key={key} value={key}>
                                    {label}
                                </option>
                            ))}
                        </Select>
                    }
                />

                <Card.Body className="p-0">
                    <Table
                        rows={announcements.data}
                        emptyState={
                            <EmptyState
                                icon={Megaphone}
                                title="Aucune annonce"
                                description="Créez votre première annonce pour informer les parents d'une réunion ou d'une information importante."
                                action={
                                    <Button
                                        as={Link}
                                        href={route('communication.announcements.create')}
                                    >
                                        <Plus className="h-4 w-4" />
                                        Nouvelle annonce
                                    </Button>
                                }
                            />
                        }
                        columns={[
                            {
                                key: 'title',
                                header: 'Annonce',
                                render: (r) => (
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold text-slate-800">
                                            {r.title}
                                        </p>

                                        <div className="mt-1 flex flex-wrap items-center gap-1.5">
                                            {(r.channels ?? []).map((channel) => (
                                                <span
                                                    key={channel}
                                                    className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"
                                                >
                                                    {channel === 'email' ? (
                                                        <Mail className="h-3 w-3" />
                                                    ) : (
                                                        <MessageCircle className="h-3 w-3" />
                                                    )}
                                                    {channel === 'email' ? 'Email' : 'WhatsApp'}
                                                </span>
                                            ))}

                                            <span className="text-xs text-slate-400">
                                                {r.audience_label}
                                            </span>
                                        </div>
                                    </div>
                                ),
                            },
                            {
                                key: 'status',
                                header: 'Statut',
                                render: (r) => (
                                    <Badge tone={statusTones[r.status] ?? 'slate'}>
                                        {statusLabels[r.status] ?? r.status}
                                    </Badge>
                                ),
                            },
                            {
                                key: 'recipients',
                                header: 'Destinataires',
                                render: (r) => (
                                    <div className="flex items-center gap-2 text-sm">
                                        <Users className="h-4 w-4 text-slate-400" />
                                        <span className="font-medium text-slate-700">
                                            {r.sent_count}/{r.recipients_count}
                                        </span>
                                        {r.failed_count > 0 && (
                                            <span className="text-xs font-medium text-red-600">
                                                {r.failed_count} échec(s)
                                            </span>
                                        )}
                                    </div>
                                ),
                            },
                            {
                                key: 'date',
                                header: 'Date',
                                render: (r) => (
                                    <div className="text-sm text-slate-600">
                                        {r.scheduled_at
                                            ? `Prévue : ${formatDate(r.scheduled_at)}`
                                            : formatDate(r.sent_at ?? r.created_at)}
                                    </div>
                                ),
                            },
                            {
                                key: 'actions',
                                header: '',
                                render: (r) => (
                                    <div className="flex justify-end gap-1">
                                        <Link
                                            href={route(
                                                'communication.announcements.show',
                                                r.id
                                            )}
                                            className="rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-600"
                                            aria-label="Détails"
                                            title="Détails"
                                        >
                                            <Eye className="h-4 w-4" />
                                        </Link>

                                        {r.failed_count > 0 && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    router.post(
                                                        route(
                                                            'communication.announcements.resend',
                                                            r.id
                                                        ),
                                                        {},
                                                        { preserveScroll: true }
                                                    )
                                                }
                                                className="rounded-lg p-2 text-amber-500 transition hover:bg-amber-50 hover:text-amber-600"
                                                aria-label="Relancer les échecs"
                                                title="Relancer les échecs"
                                            >
                                                <RotateCcw className="h-4 w-4" />
                                            </button>
                                        )}

                                        {['draft', 'scheduled'].includes(r.status) && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    router.post(
                                                        route(
                                                            'communication.announcements.cancel',
                                                            r.id
                                                        ),
                                                        {},
                                                        { preserveScroll: true }
                                                    )
                                                }
                                                className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                                aria-label="Annuler"
                                                title="Annuler"
                                            >
                                                <Ban className="h-4 w-4" />
                                            </button>
                                        )}

                                        {r.status !== 'sending' && (
                                            <button
                                                type="button"
                                                onClick={() => setToDelete(r)}
                                                className="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                aria-label="Supprimer"
                                                title="Supprimer"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                ),
                            },
                        ]}
                    />
                </Card.Body>

                <div className="border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
                    <Pagination meta={announcements} />
                </div>
            </Card>

            <ConfirmDialog
                show={!!toDelete}
                onClose={() => setToDelete(null)}
                onConfirm={confirmDelete}
                loading={deleting}
                title="Supprimer l'annonce"
                description={
                    toDelete
                        ? `Supprimer « ${toDelete.title} » ? L'historique des envois sera également supprimé.`
                        : ''
                }
                variant="danger"
                confirmLabel="Supprimer"
            />
        </AuthenticatedLayout>
    );
}
