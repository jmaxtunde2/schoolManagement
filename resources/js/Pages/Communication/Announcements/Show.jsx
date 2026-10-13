import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Megaphone,
    ArrowLeft,
    MessageCircle,
    Mail,
    Users,
    CheckCircle2,
    XCircle,
    Send,
    Paperclip,
    CalendarClock,
    Ban,
    RotateCcw,
    Trash2,
    AlertCircle,
    UserRound,
    GraduationCap,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import EmptyState from '@/Components/UI/EmptyState';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';

const announcementTones = {
    draft: 'slate',
    scheduled: 'amber',
    sending: 'blue',
    sent: 'green',
    partial: 'amber',
    failed: 'red',
    cancelled: 'slate',
};

const recipientTones = {
    pending: 'slate',
    queued: 'blue',
    sent: 'blue',
    delivered: 'green',
    failed: 'red',
};

const recipientLabels = {
    pending: 'En attente',
    queued: 'En file',
    sent: 'Envoyé',
    delivered: 'Livré',
    failed: 'Échec',
};

const channelLabels = {
    whatsapp: 'WhatsApp',
    sms: 'SMS',
    email: 'Email',
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

export default function AnnouncementShow({ announcement, recipients }) {
    const [toDelete, setToDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    function resendFailed() {
        router.post(
            route('communication.announcements.resend', announcement.id),
            {},
            { preserveScroll: true }
        );
    }

    function cancelAnnouncement() {
        router.post(
            route('communication.announcements.cancel', announcement.id),
            {},
            { preserveScroll: true }
        );
    }

    function confirmDelete() {
        setDeleting(true);

        router.delete(route('communication.announcements.destroy', announcement.id), {
            onFinish: () => {
                setDeleting(false);
                setToDelete(false);
            },
        });
    }

    const stats = [
        {
            label: 'Destinataires',
            value: announcement.recipients_count,
            icon: Users,
            className: 'from-slate-500 to-slate-700',
        },
        {
            label: 'Envoyés',
            value: announcement.sent_count,
            icon: CheckCircle2,
            className: 'from-emerald-500 to-teal-600',
        },
        {
            label: 'Échecs',
            value: announcement.failed_count,
            icon: XCircle,
            className: 'from-red-500 to-rose-600',
        },
    ];

    return (
        <AuthenticatedLayout title={announcement.title}>
            <Head title={announcement.title} />

            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-start gap-3">
                    <Link
                        href={route('communication.announcements.index')}
                        className="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50"
                        aria-label="Retour"
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Link>

                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-bold text-slate-900">
                                {announcement.title}
                            </h1>
                            <Badge tone={announcementTones[announcement.status] ?? 'slate'}>
                                {announcement.status_label}
                            </Badge>
                        </div>

                        <p className="mt-1 text-sm text-slate-500">
                            {announcement.audience_label}
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    {announcement.failed_count > 0 && (
                        <Button variant="secondary" onClick={resendFailed}>
                            <RotateCcw className="h-4 w-4" />
                            Relancer les échecs
                        </Button>
                    )}

                    {['draft', 'scheduled'].includes(announcement.status) && (
                        <Button variant="secondary" onClick={cancelAnnouncement}>
                            <Ban className="h-4 w-4" />
                            Annuler
                        </Button>
                    )}

                    {announcement.status !== 'sending' && (
                        <Button variant="danger" onClick={() => setToDelete(true)}>
                            <Trash2 className="h-4 w-4" />
                            Supprimer
                        </Button>
                    )}
                </div>
            </div>

            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                {stats.map((item) => {
                    const Icon = item.icon;

                    return (
                        <div
                            key={item.label}
                            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${item.className} p-5 text-white shadow-md`}
                        >
                            <div className="absolute -right-5 -top-5 h-20 w-20 rounded-full bg-white/10" />
                            <div className="relative flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-white/75">
                                        {item.label}
                                    </p>
                                    <p className="mt-1 text-2xl font-bold">{item.value}</p>
                                </div>
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20">
                                    <Icon className="h-5 w-5" />
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            <Card className="mb-6">
                <Card.Header title="Contenu de l'annonce" />

                <Card.Body className="space-y-4">
                    <div className="flex flex-wrap items-center gap-2">
                        {(announcement.channels ?? []).map((channel) => (
                            <Badge key={channel} tone={channel === 'email' ? 'blue' : 'green'}>
                                {channel === 'email' ? (
                                    <Mail className="h-3.5 w-3.5" />
                                ) : (
                                    <MessageCircle className="h-3.5 w-3.5" />
                                )}
                                {channel === 'email' ? 'Email' : 'WhatsApp'}
                            </Badge>
                        ))}

                        {announcement.scheduled_at && (
                            <Badge tone="amber">
                                <CalendarClock className="h-3.5 w-3.5" />
                                Prévue : {formatDate(announcement.scheduled_at)}
                            </Badge>
                        )}

                        {announcement.sent_at && (
                            <Badge tone="slate">
                                <Send className="h-3.5 w-3.5" />
                                Envoyée : {formatDate(announcement.sent_at)}
                            </Badge>
                        )}
                    </div>

                    <div className="whitespace-pre-wrap rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                        {announcement.body}
                    </div>

                    {announcement.attachment && (
                        <a
                            href={announcement.attachment.url}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            <Paperclip className="h-4 w-4 text-slate-400" />
                            {announcement.attachment.name}
                        </a>
                    )}

                    {announcement.creator && (
                        <p className="text-xs text-slate-400">
                            Créée par {announcement.creator} le {formatDate(announcement.created_at)}
                        </p>
                    )}
                </Card.Body>
            </Card>

            <Card className="overflow-hidden">
                <Card.Header
                    title="Destinataires"
                    description={`${recipients.total} destinataire(s)`}
                />

                <Card.Body className="p-0">
                    <Table
                        rows={recipients.data}
                        emptyState={
                            <EmptyState
                                icon={Users}
                                title="Aucun destinataire"
                                description="Les envois apparaîtront ici dès que l'annonce sera traitée."
                            />
                        }
                        columns={[
                            {
                                key: 'parent',
                                header: 'Parent',
                                render: (r) => (
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                                            <UserRound className="h-4 w-4" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-slate-700">
                                                {r.guardian?.name ?? '—'}
                                            </p>
                                            <p className="truncate text-xs text-slate-400">
                                                {r.email || r.phone}
                                            </p>
                                        </div>
                                    </div>
                                ),
                            },
                            {
                                key: 'student',
                                header: 'Élève',
                                render: (r) =>
                                    r.student ? (
                                        <div className="flex items-center gap-2 text-sm text-slate-600">
                                            <GraduationCap className="h-4 w-4 text-slate-400" />
                                            {r.student.first_name} {r.student.last_name}
                                        </div>
                                    ) : (
                                        <span className="text-slate-300">—</span>
                                    ),
                            },
                            {
                                key: 'channel',
                                header: 'Canal',
                                render: (r) => (
                                    <Badge tone={r.channel === 'email' ? 'blue' : 'green'}>
                                        {channelLabels[r.channel] ?? r.channel}
                                    </Badge>
                                ),
                            },
                            {
                                key: 'status',
                                header: 'Statut',
                                render: (r) => (
                                    <Badge tone={recipientTones[r.status] ?? 'slate'}>
                                        {recipientLabels[r.status] ?? r.status}
                                    </Badge>
                                ),
                            },
                            {
                                key: 'error',
                                header: 'Erreur',
                                render: (r) =>
                                    r.error ? (
                                        <div className="flex max-w-xs items-start gap-2">
                                            <AlertCircle className="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
                                            <span className="text-sm leading-5 text-red-600">
                                                {r.error}
                                            </span>
                                        </div>
                                    ) : (
                                        <span className="text-slate-300">—</span>
                                    ),
                            },
                            {
                                key: 'sent_at',
                                header: 'Envoyé le',
                                render: (r) => (
                                    <span className="text-sm text-slate-500">
                                        {formatDate(r.sent_at)}
                                    </span>
                                ),
                            },
                        ]}
                    />
                </Card.Body>

                <div className="border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
                    <Pagination meta={recipients} />
                </div>
            </Card>

            <ConfirmDialog
                show={toDelete}
                onClose={() => setToDelete(false)}
                onConfirm={confirmDelete}
                loading={deleting}
                title="Supprimer l'annonce"
                description="Cette action supprimera l'annonce et tout son historique d'envoi."
                variant="danger"
                confirmLabel="Supprimer"
            />
        </AuthenticatedLayout>
    );
}
