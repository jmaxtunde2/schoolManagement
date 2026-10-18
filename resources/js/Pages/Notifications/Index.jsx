import { Link, router } from '@inertiajs/react';
import { Bell, CheckCheck, ExternalLink } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

const priorityStyles = {
    critical: {
        chip: 'bg-rose-100 text-rose-700',
        border: 'border-rose-200 bg-rose-50/40',
        label: 'Critique',
    },
    high: {
        chip: 'bg-orange-100 text-orange-700',
        border: 'border-orange-200 bg-orange-50/40',
        label: 'Élevée',
    },
    medium: {
        chip: 'bg-amber-100 text-amber-700',
        border: 'border-amber-200 bg-amber-50/40',
        label: 'Moyenne',
    },
    low: {
        chip: 'bg-sky-100 text-sky-700',
        border: 'border-sky-200 bg-sky-50/40',
        label: 'Faible',
    },
};

const typeLabels = {
    'alert.academic': 'Scolarité',
    'alert.attendance': 'Assiduité',
    'alert.finance': 'Finance',
    'evaluation.grades': 'Notes',
};

function typeLabel(type) {
    if (typeLabels[type]) return typeLabels[type];
    if (type?.startsWith('alert.')) return 'Alerte';
    return type ?? 'Notification';
}

export default function NotificationsIndex({ notifications }) {
    const items = notifications?.data ?? notifications ?? [];

    return (
        <AuthenticatedLayout title="Notifications">
            <div className="mx-auto max-w-4xl space-y-5">
                <div className="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex items-center gap-3">
                        <div className="rounded-xl bg-emerald-100 p-2 text-emerald-700">
                            <Bell className="h-5 w-5" />
                        </div>
                        <div>
                            <h2 className="text-xl font-semibold text-slate-900">
                                Notifications internes
                            </h2>
                            <p className="text-sm text-slate-500">
                                Historique, alertes de pilotage et lecture des
                                messages de votre école.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() =>
                            router.post(route('notifications.read-all'))
                        }
                        className="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700"
                    >
                        <CheckCheck className="h-4 w-4" /> Tout marquer comme lu
                    </button>
                </div>

                {items.length === 0 ? (
                    <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">
                        Aucune notification pour le moment.
                    </div>
                ) : (
                    <div className="space-y-3">
                        {items.map((notification) => {
                            const priority =
                                priorityStyles[notification.data?.priority];
                            const isAlert = notification.type?.startsWith(
                                'alert.'
                            );

                            return (
                                <div
                                    key={notification.id}
                                    className={`rounded-2xl border p-4 shadow-sm ${
                                        priority
                                            ? priority.border
                                            : notification.is_read
                                              ? 'border-slate-200 bg-white'
                                              : 'border-emerald-200 bg-emerald-50'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                {typeLabel(notification.type)}
                                            </span>
                                            {priority && (
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${priority.chip}`}
                                                >
                                                    {priority.label}
                                                </span>
                                            )}
                                        </div>
                                        {!notification.is_read && (
                                            <span className="rounded-full bg-emerald-600 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">
                                                Non lu
                                            </span>
                                        )}
                                    </div>

                                    <h3 className="mt-1 text-lg font-semibold text-slate-900">
                                        {notification.title}
                                    </h3>
                                    <p className="mt-2 text-sm text-slate-600">
                                        {notification.message}
                                    </p>

                                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                                        <span>
                                            {new Date(
                                                notification.created_at
                                            ).toLocaleString('fr-FR')}
                                        </span>

                                        <div className="flex items-center gap-3">
                                            {isAlert &&
                                                notification.data?.link_route && (
                                                    <Link
                                                        href={route(
                                                            notification.data
                                                                .link_route
                                                        )}
                                                        className="inline-flex items-center gap-1 font-medium text-primary hover:opacity-80"
                                                    >
                                                        {notification.data
                                                            .link_label ??
                                                            'Voir le détail'}
                                                        <ExternalLink className="h-3.5 w-3.5" />
                                                    </Link>
                                                )}

                                            {!notification.is_read && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        router.post(
                                                            route(
                                                                'notifications.read',
                                                                {
                                                                    notification:
                                                                        notification.id,
                                                                }
                                                            )
                                                        )
                                                    }
                                                    className="rounded-lg border border-slate-200 bg-white px-2 py-1 font-medium text-slate-700 hover:bg-slate-100"
                                                >
                                                    Marquer comme lu
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
