import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { CheckCircle2, Mail, ShieldCheck, Send, Eye, EyeOff } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function EmailNotifications({ settings }) {
    const { data, setData, processing, errors, reset } = useForm({
        mailer: settings.mailer ?? 'smtp',
        host: settings.host ?? '',
        port: settings.port ?? 587,
        username: settings.username ?? '',
        password: '',
        encryption: settings.encryption ?? 'tls',
        from_address: settings.from_address ?? '',
        from_name: settings.from_name ?? '',
        reply_to: settings.reply_to ?? '',
        is_active: settings.is_active ?? true,
    });

    const [showPassword, setShowPassword] = useState(false);

    function handleSave(e) {
        e.preventDefault();
        router.put(route('admin.settings.email.update'), data, {
            preserveScroll: true,
        });
    }

    function handleTest(e) {
        e.preventDefault();
        router.post(route('admin.settings.email.test'), {}, {
            preserveScroll: true,
        });
    }

    return (
        <AuthenticatedLayout title="Email & Notifications">
            <div className="mx-auto max-w-5xl space-y-6">
                <div className="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-6 shadow-sm">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-700">Paramètres</p>
                            <h2 className="mt-2 text-2xl font-bold text-slate-900">Email & Notifications</h2>
                        </div>
                        <div className="rounded-full bg-white p-3 text-emerald-600 shadow-sm">
                            <Mail className="h-6 w-6" />
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSave} className="space-y-6">
                    <div className="grid gap-6 lg:grid-cols-2">
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <ShieldCheck className="h-5 w-5 text-emerald-600" />
                                <h3 className="text-lg font-semibold text-slate-900">Serveur SMTP</h3>
                            </div>
                            <div className="space-y-4">
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">Serveur SMTP</label>
                                    <input value={data.host} onChange={(e) => setData('host', e.target.value)} className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2" placeholder="smtp.example.com" />
                                    {errors.host && <p className="mt-1 text-xs text-rose-600">{errors.host}</p>}
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">Port</label>
                                        <input type="number" value={data.port} onChange={(e) => setData('port', Number(e.target.value))} className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2" />
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-slate-700">Encryption</label>
                                        <select value={data.encryption} onChange={(e) => setData('encryption', e.target.value)} className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2">
                                            <option value="">Aucune</option>
                                            <option value="tls">TLS</option>
                                            <option value="ssl">SSL</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">Nom d'utilisateur</label>
                                    <input value={data.username} onChange={(e) => setData('username', e.target.value)} className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2" />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">Mot de passe</label>
                                    <div className="relative">
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="Laisser vide pour conserver le mot de passe actuel"
                                            className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 pr-10"
                                        />
                                        <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute inset-y-0 right-3 flex items-center text-slate-500">
                                            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <CheckCircle2 className="h-5 w-5 text-teal-600" />
                                <h3 className="text-lg font-semibold text-slate-900">Identité d'envoi</h3>
                            </div>
                            <div className="space-y-4">
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">Adresse d'expédition</label>
                                    <input type="email" value={data.from_address} onChange={(e) => setData('from_address', e.target.value)} className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2" />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">Nom de l'expéditeur</label>
                                    <input value={data.from_name} onChange={(e) => setData('from_name', e.target.value)} className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2" />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-700">Reply-To</label>
                                    <input type="email" value={data.reply_to} onChange={(e) => setData('reply_to', e.target.value)} className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2" />
                                </div>
                                <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <div>
                                        <p className="text-sm font-medium text-slate-700">Activer les emails</p>
                                        <p className="text-xs text-slate-500">Utilise le SMTP de cette école pour tous les emails internes.</p>
                                    </div>
                                    <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="h-4 w-4 rounded border-slate-300 text-emerald-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <button type="submit" disabled={processing} className="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                            Enregistrer
                        </button>
                        <button type="button" onClick={handleTest} className="inline-flex items-center justify-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-semibold text-teal-700 hover:bg-teal-100">
                            <Send className="h-4 w-4" /> Tester la configuration
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
