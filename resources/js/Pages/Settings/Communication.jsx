import { router, useForm } from '@inertiajs/react';
import { AlertTriangle, Mail, MessageSquare, Smartphone, Wallet } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

function formatNumber(value) {
    return new Intl.NumberFormat('fr-FR').format(value ?? 0);
}

export default function Communication({ settings }) {
    const { data, setData, processing, errors } = useForm({
        sms_enabled: settings.sms_enabled ?? true,
        whatsapp_enabled: settings.whatsapp_enabled ?? true,
        email_enabled: settings.email_enabled ?? true,
        sms_low_balance_threshold: settings.sms_low_balance_threshold ?? 500,
        sms_reactivation_threshold: settings.sms_reactivation_threshold ?? 1000,
        whatsapp_low_budget_threshold: settings.whatsapp_low_budget_threshold ?? 1,
    });

    function handleSave(e) {
        e.preventDefault();
        router.put(route('admin.settings.communication.update'), data, {
            preserveScroll: true,
        });
    }

    return (
        <AuthenticatedLayout title="Communication">
            <div className="mx-auto max-w-5xl space-y-6">
                <div className="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-6 shadow-sm">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-700">Paramètres</p>
                            <h2 className="mt-2 text-2xl font-bold text-slate-900">Communication aux parents</h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Les notes validées sont regroupées et envoyées une fois par jour aux parents.
                            </p>
                        </div>
                        <div className="rounded-full bg-white p-3 text-emerald-600 shadow-sm">
                            <MessageSquare className="h-6 w-6" />
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="mb-2 flex items-center gap-2 text-slate-500">
                            <Smartphone className="h-4 w-4" />
                            <span className="text-xs font-semibold uppercase tracking-wide">Budget SMS</span>
                        </div>
                        <p className="text-2xl font-bold text-slate-900">{formatNumber(settings.sms_budget)} F</p>
                        <p className="mt-1 text-xs text-slate-500">
                            Consommé : {formatNumber(settings.sms_consumed)} F · Solde : {formatNumber(settings.sms_balance)} F
                        </p>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="mb-2 flex items-center gap-2 text-slate-500">
                            <Wallet className="h-4 w-4" />
                            <span className="text-xs font-semibold uppercase tracking-wide">Budget WhatsApp</span>
                        </div>
                        <p className="text-2xl font-bold text-slate-900">{settings.whatsapp_budget} $</p>
                        <p className="mt-1 text-xs text-slate-500">
                            Consommé : {settings.whatsapp_consumed} $ · Restant : {settings.whatsapp_remaining} $
                        </p>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="mb-2 flex items-center gap-2 text-slate-500">
                            <Mail className="h-4 w-4" />
                            <span className="text-xs font-semibold uppercase tracking-wide">Dernier contrôle SMS</span>
                        </div>
                        <p className="text-lg font-semibold text-slate-900">
                            {settings.last_sms_balance_check_at || 'Jamais'}
                        </p>
                        <p className="mt-1 text-xs text-slate-500">Vérifié automatiquement chaque nuit.</p>
                    </div>
                </div>

                <form onSubmit={handleSave} className="space-y-6">
                    <div className="grid gap-6 lg:grid-cols-2">
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <Smartphone className="h-5 w-5 text-emerald-600" />
                                <h3 className="text-lg font-semibold text-slate-900">SMS</h3>
                            </div>

                            <label className="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <span className="text-sm font-medium text-slate-700">Activer le canal SMS</span>
                                <input
                                    type="checkbox"
                                    checked={data.sms_enabled}
                                    onChange={(e) => setData('sms_enabled', e.target.checked)}
                                    className="h-4 w-4"
                                />
                            </label>

                            <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">Seuil de désactivation (F)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.sms_low_balance_threshold}
                                        onChange={(e) => setData('sms_low_balance_threshold', Number(e.target.value))}
                                        className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2"
                                    />
                                    {errors.sms_low_balance_threshold && (
                                        <p className="mt-1 text-xs text-rose-600">{errors.sms_low_balance_threshold}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">Seuil de réactivation (F)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={data.sms_reactivation_threshold}
                                        onChange={(e) => setData('sms_reactivation_threshold', Number(e.target.value))}
                                        className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2"
                                    />
                                    {errors.sms_reactivation_threshold && (
                                        <p className="mt-1 text-xs text-rose-600">{errors.sms_reactivation_threshold}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <Wallet className="h-5 w-5 text-teal-600" />
                                <h3 className="text-lg font-semibold text-slate-900">WhatsApp & Email</h3>
                            </div>

                            <label className="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <span className="text-sm font-medium text-slate-700">Activer WhatsApp (repli SMS)</span>
                                <input
                                    type="checkbox"
                                    checked={data.whatsapp_enabled}
                                    onChange={(e) => setData('whatsapp_enabled', e.target.checked)}
                                    className="h-4 w-4"
                                />
                            </label>

                            <label className="mt-3 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <span className="text-sm font-medium text-slate-700">Activer Email (dernier repli)</span>
                                <input
                                    type="checkbox"
                                    checked={data.email_enabled}
                                    onChange={(e) => setData('email_enabled', e.target.checked)}
                                    className="h-4 w-4"
                                />
                            </label>

                            <div className="mt-4">
                                <label className="mb-1 block text-sm font-medium text-slate-700">Seuil budget WhatsApp bas ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.whatsapp_low_budget_threshold}
                                    onChange={(e) => setData('whatsapp_low_budget_threshold', Number(e.target.value))}
                                    className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2"
                                />
                                {errors.whatsapp_low_budget_threshold && (
                                    <p className="mt-1 text-xs text-rose-600">{errors.whatsapp_low_budget_threshold}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                        <p>
                            Ces budgets sont alloués par CoriSchool. Le canal SMS est automatiquement désactivé
                            sous le seuil bas, puis basculé vers WhatsApp ou Email.
                        </p>
                    </div>

                    <div className="flex justify-end">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60"
                        >
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
