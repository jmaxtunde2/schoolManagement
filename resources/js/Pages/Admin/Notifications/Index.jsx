import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    BellRing,
    RotateCcw,
    CheckCircle2,
    Clock3,
    XCircle,
    Send,
    Smartphone,
    UserRound,
    GraduationCap,
    ClipboardList,
    AlertCircle,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
import Badge from '@/Components/UI/Badge';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';
import EmptyState from '@/Components/UI/EmptyState';

const statusTones = {
    pending: 'slate',
    queued: 'blue',
    sent: 'blue',
    delivered: 'green',
    failed: 'red',
};

const statusLabels = {
    pending: 'En attente',
    queued: 'En file',
    sent: 'Envoyé',
    delivered: 'Livré',
    failed: 'Échec',
};

export default function NotificationsIndex({
    notifications,
    filters,
    counts,
}) {
    const [status, setStatus] = useState(filters.status ?? '');
    const [resendingId, setResendingId] = useState(null);

    function applyFilter(value) {
        setStatus(value);

        router.get(
            route('admin.notifications.index'),
            { status: value },
            {
                preserveState: true,
                replace: true,
            }
        );
    }

    function resend(notification) {
        setResendingId(notification.id);

        router.post(
            route(
                'admin.notifications.resend',
                notification.id
            ),
            {},
            {
                preserveScroll: true,
                onFinish: () => setResendingId(null),
            }
        );
    }

    const summary = [
        {
            key: 'sent',
            label: 'Envoyés',
            value: counts.sent ?? 0,
            icon: Send,
            className:
                'from-blue-500 to-indigo-600',
        },
        {
            key: 'delivered',
            label: 'Livrés',
            value: counts.delivered ?? 0,
            icon: CheckCircle2,
            className:
                'from-emerald-500 to-teal-600',
        },
        {
            key: 'failed',
            label: 'Échecs',
            value: counts.failed ?? 0,
            icon: XCircle,
            className:
                'from-red-500 to-rose-600',
        },
        {
            key: 'pending',
            label: 'En attente',
            value: counts.pending ?? 0,
            icon: Clock3,
            className:
                'from-slate-500 to-slate-700',
        },
    ];

    return (
        <AuthenticatedLayout title="Notifications">
            <Head title="Notifications" />

            {/* Header */}
            <div className="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 text-white shadow-lg">
                <div className="relative p-5 sm:p-6">
                    <div className="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10" />
                    <div className="absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-white/5" />

                    <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                                    <BellRing className="h-5 w-5" />
                                </div>

                                <span className="text-sm font-medium text-white/80">
                                    Communication
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Notifications
                            </h1>

                            <p className="mt-1 max-w-xl text-sm text-white/75">
                                Suivez l'envoi des SMS et les notifications
                                destinées aux parents.
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
                            <Smartphone className="h-6 w-6" />

                            <div>
                                <p className="text-xs text-white/70">
                                    Notifications
                                </p>

                                <p className="text-xl font-bold">
                                    {notifications.total}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Statistiques */}
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

            {/* Historique */}
            <Card className="overflow-hidden">
                <Card.Header
                    title="Historique des notifications"
                    description={`${notifications.total} notification(s)`}
                    actions={
                        <Select
                            className="w-full sm:w-56"
                            value={status}
                            onChange={(e) =>
                                applyFilter(e.target.value)
                            }
                        >
                            <option value="">
                                Tous les statuts
                            </option>

                            {Object.entries(statusLabels).map(
                                ([key, label]) => (
                                    <option
                                        key={key}
                                        value={key}
                                    >
                                        {label}
                                    </option>
                                )
                            )}
                        </Select>
                    }
                />

                <Card.Body className="p-0">
                    <Table
                        rows={notifications.data}
                        emptyState={
                            <EmptyState
                                icon={BellRing}
                                title="Aucune notification"
                                description="Les SMS envoyés après validation d'une évaluation apparaîtront ici."
                            />
                        }
                        columns={[
                            {
                                key: 'student',
                                header: 'Élève',
                                render: (r) => (
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                            <GraduationCap className="h-4 w-4" />
                                        </div>

                                        <div className="min-w-0">
                                            <p className="truncate font-semibold text-slate-800">
                                                {
                                                    r.student
                                                        .first_name
                                                }{' '}
                                                {
                                                    r.student
                                                        .last_name
                                                }
                                            </p>

                                            <p className="text-xs text-slate-400">
                                                Élève
                                            </p>
                                        </div>
                                    </div>
                                ),
                            },

                            {
                                key: 'guardian',
                                header: 'Parent',
                                render: (r) => (
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-600">
                                            <UserRound className="h-4 w-4" />
                                        </div>

                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-slate-700">
                                                {r.guardian.name}
                                            </p>

                                            <p className="text-xs text-slate-400">
                                                {r.guardian.phone}
                                            </p>
                                        </div>
                                    </div>
                                ),
                            },

                            {
                                key: 'evaluation',
                                header: 'Évaluation',
                                render: (r) => (
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                            <ClipboardList className="h-4 w-4" />
                                        </div>

                                        <span className="max-w-[220px] truncate text-sm font-medium text-slate-700">
                                            {r.evaluation.title}
                                        </span>
                                    </div>
                                ),
                            },

                            {
                                key: 'status',
                                header: 'Statut',
                                render: (r) => (
                                    <Badge
                                        tone={
                                            statusTones[
                                                r.status
                                            ] ?? 'slate'
                                        }
                                    >
                                        {statusLabels[
                                            r.status
                                        ] ?? r.status}
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
                                        <span className="text-slate-300">
                                            —
                                        </span>
                                    ),
                            },

                            {
                                key: 'actions',
                                header: '',
                                render: (r) =>
                                    r.status === 'failed' ? (
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            loading={
                                                resendingId ===
                                                r.id
                                            }
                                            onClick={() =>
                                                resend(r)
                                            }
                                        >
                                            <RotateCcw className="h-4 w-4" />
                                            Relancer
                                        </Button>
                                    ) : (
                                        <span className="text-xs text-slate-300">
                                            —
                                        </span>
                                    ),
                            },
                        ]}
                    />
                </Card.Body>

                <div className="border-t border-slate-100 bg-slate-50/70 px-4 py-3 sm:px-5">
                    <Pagination meta={notifications} />
                </div>
            </Card>
        </AuthenticatedLayout>
    );
}