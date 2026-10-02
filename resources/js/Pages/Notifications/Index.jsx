import { Link, router } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

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
                            <h2 className="text-xl font-semibold text-slate-900">Notifications internes</h2>
                            <p className="text-sm text-slate-500">Historique et lecture des messages de votre école.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() => router.post(route('notifications.read-all'))}
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
                        {items.map((notification) => (
                            <div key={notification.id} className={`rounded-2xl border p-4 shadow-sm ${notification.is_read ? 'border-slate-200 bg-white' : 'border-emerald-200 bg-emerald-50'}`}>
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold uppercase tracking-wide text-slate-500">{notification.type}</p>
                                        <h3 className="mt-1 text-lg font-semibold text-slate-900">{notification.title}</h3>
                                    </div>
                                    {!notification.is_read && <span className="rounded-full bg-emerald-600 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">Non lu</span>}
                                </div>
                                <p className="mt-3 text-sm text-slate-600">{notification.message}</p>
                                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                                    <span>{notification.created_at}</span>
                                    {!notification.is_read && (
                                        <button
                                            type="button"
                                            onClick={() => router.post(route('notifications.read', { notification: notification.id }))}
                                            className="rounded-lg border border-slate-200 bg-white px-2 py-1 font-medium text-slate-700 hover:bg-slate-100"
                                        >
                                            Marquer comme lu
                                        </button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
