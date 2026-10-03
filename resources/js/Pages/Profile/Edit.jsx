import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import {
    BadgeCheck,
    Building2,
    CalendarDays,
    Eye,
    EyeOff,
    KeyRound,
    Mail,
    Save,
    ShieldCheck,
    User as UserIcon,
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

function initials(name) {
    return (name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
}

export default function Edit({ profile }) {
    const [showEmailCurrent, setShowEmailCurrent] = useState(false);
    const [showCurrent, setShowCurrent] = useState(false);
    const [showNew, setShowNew] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const info = useForm({ name: profile.name, email: profile.email, current_password: '' });
    const password = useForm({ current_password: '', password: '', password_confirmation: '' });

    const emailChanged = info.data.email !== profile.email;

    function submitInfo(e) {
        e.preventDefault();
        router.put(route('profile.update'), info.data, {
            preserveScroll: true,
            onSuccess: () => info.setData('current_password', ''),
        });
    }

    function submitPassword(e) {
        e.preventDefault();
        router.put(route('profile.password.update'), password.data, {
            preserveScroll: true,
            onSuccess: () => password.setData({ current_password: '', password: '', password_confirmation: '' }),
        });
    }

    const facts = [
        { icon: ShieldCheck, label: 'Rôle', value: profile.role_label },
        { icon: Building2, label: 'Établissement', value: profile.school_name ?? 'Administration Coriyase' },
        { icon: CalendarDays, label: 'Compte créé le', value: profile.created_at ?? '—' },
    ];

    return (
        <AuthenticatedLayout title="Mon profil">
            <div className="mx-auto max-w-5xl space-y-6">
                <div className="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 p-6 shadow-sm">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-700">Compte</p>
                            <h2 className="mt-2 text-2xl font-bold text-slate-900">Mon profil</h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Mettez à jour vos informations personnelles et votre mot de passe.
                            </p>
                        </div>
                        <div className="rounded-full bg-white p-3 text-emerald-600 shadow-sm">
                            <UserIcon className="h-6 w-6" />
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-1">
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-sm">
                            <div className="mx-auto flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-2xl font-bold text-white ring-4 ring-emerald-50"
                                style={{ backgroundColor: 'var(--color-primary)' }}
                            >
                                {profile.avatar_url ? (
                                    <img src={profile.avatar_url} alt="" className="h-full w-full object-cover" />
                                ) : (
                                    <span>{initials(profile.name)}</span>
                                )}
                            </div>
                            <p className="mt-4 font-semibold text-slate-900">{profile.name}</p>
                            <p className="text-sm text-slate-500">{profile.email}</p>

                            <dl className="mt-5 space-y-3 border-t border-slate-100 pt-5 text-left">
                                {facts.map(({ icon: Icon, label, value }) => (
                                    <div key={label} className="flex items-start gap-3">
                                        <Icon className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                        <div className="min-w-0">
                                            <dt className="text-xs uppercase tracking-wide text-slate-400">{label}</dt>
                                            <dd className="text-sm text-slate-700">{value}</dd>
                                        </div>
                                    </div>
                                ))}
                            </dl>

                            <div className="mt-5 rounded-xl border border-slate-100 bg-slate-50 p-4 text-left">
                                <div className="flex items-center gap-2">
                                    {profile.two_factor_enabled ? (
                                        <BadgeCheck className="h-5 w-5 text-emerald-600" />
                                    ) : (
                                        <KeyRound className="h-5 w-5 text-amber-500" />
                                    )}
                                    <p className="text-sm font-medium text-slate-800">Double authentification</p>
                                </div>
                                <p className="mt-1 text-xs text-slate-500">
                                    {profile.two_factor_enabled
                                        ? `Active${profile.two_factor_enabled_at ? ` depuis le ${profile.two_factor_enabled_at}` : ''}.`
                                        : 'Non configurée.'}
                                </p>
                                <Link
                                    href={route('two-factor.recovery')}
                                    className="mt-2 inline-block text-xs font-medium text-emerald-700 hover:text-emerald-800"
                                >
                                    Voir mes codes de récupération
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-6 lg:col-span-2">
                        <form onSubmit={submitInfo} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <UserIcon className="h-5 w-5 text-emerald-600" />
                                <h3 className="text-lg font-semibold text-slate-900">Informations personnelles</h3>
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <label htmlFor="name" className="mb-1 block text-sm font-medium text-slate-700">
                                        Nom complet
                                    </label>
                                    <input
                                        id="name"
                                        type="text"
                                        value={info.data.name}
                                        onChange={(e) => info.setData('name', e.target.value)}
                                        className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2"
                                        autoComplete="name"
                                    />
                                    {info.errors.name && (
                                        <p className="mt-1 text-xs text-rose-600">{info.errors.name}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="email" className="mb-1 block text-sm font-medium text-slate-700">
                                        Adresse email
                                    </label>
                                    <div className="relative">
                                        <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                        <input
                                            id="email"
                                            type="email"
                                            value={info.data.email}
                                            onChange={(e) => info.setData('email', e.target.value)}
                                            className="w-full rounded-lg border border-slate-300 bg-slate-50 py-2 pl-9 pr-3"
                                            autoComplete="email"
                                        />
                                    </div>
                                    {profile.is_email_verified && (
                                        <p className="mt-1 text-xs text-emerald-600">Adresse email vérifiée.</p>
                                    )}
                                    {info.errors.email && (
                                        <p className="mt-1 text-xs text-rose-600">{info.errors.email}</p>
                                    )}
                                </div>

                                {emailChanged && (
                                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                        <p className="text-sm font-medium text-amber-900">
                                            Vous modifiez votre adresse de connexion.
                                        </p>
                                        <p className="mt-1 text-xs text-amber-800">
                                            Confirmez votre mot de passe actuel pour valider ce changement.
                                        </p>
                                        <label htmlFor="current_password" className="mt-3 block text-sm font-medium text-slate-700">
                                            Mot de passe actuel
                                        </label>
                                        <div className="relative mt-1">
                                            <input
                                                id="current_password"
                                                type={showEmailCurrent ? 'text' : 'password'}
                                                value={info.data.current_password}
                                                onChange={(e) => info.setData('current_password', e.target.value)}
                                                className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10"
                                                autoComplete="current-password"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setShowEmailCurrent(!showEmailCurrent)}
                                                className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                                aria-label={showEmailCurrent ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                                            >
                                                {showEmailCurrent ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                            </button>
                                        </div>
                                        {info.errors.current_password && (
                                            <p className="mt-1 text-xs text-rose-600">{info.errors.current_password}</p>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="mt-5 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={info.processing}
                                    className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-contrast transition hover:opacity-90 disabled:opacity-50"
                                >
                                    <Save className="h-4 w-4" />
                                    {info.processing ? 'Enregistrement…' : 'Enregistrer'}
                                </button>
                            </div>
                        </form>

                        <form onSubmit={submitPassword} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-center gap-2">
                                <KeyRound className="h-5 w-5 text-emerald-600" />
                                <h3 className="text-lg font-semibold text-slate-900">Mot de passe</h3>
                            </div>

                            <p className="mb-4 text-sm text-slate-600">
                                Choisissez un mot de passe d'au moins 8 caractères, contenant une lettre et un chiffre. Vos
                                autres sessions seront déconnectées.
                            </p>

                            <div className="space-y-4">
                                <PasswordField
                                    id="current_password"
                                    label="Mot de passe actuel"
                                    value={password.data.current_password}
                                    visible={showCurrent}
                                    onToggle={() => setShowCurrent(!showCurrent)}
                                    onChange={(value) => password.setData('current_password', value)}
                                    error={password.errors.current_password}
                                    autoComplete="current-password"
                                />

                                <PasswordField
                                    id="password"
                                    label="Nouveau mot de passe"
                                    value={password.data.password}
                                    visible={showNew}
                                    onToggle={() => setShowNew(!showNew)}
                                    onChange={(value) => password.setData('password', value)}
                                    error={password.errors.password}
                                    autoComplete="new-password"
                                />

                                <PasswordField
                                    id="password_confirmation"
                                    label="Confirmer le nouveau mot de passe"
                                    value={password.data.password_confirmation}
                                    visible={showConfirm}
                                    onToggle={() => setShowConfirm(!showConfirm)}
                                    onChange={(value) => password.setData('password_confirmation', value)}
                                    error={password.errors.password_confirmation}
                                    autoComplete="new-password"
                                />
                            </div>

                            <div className="mt-5 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={password.processing}
                                    className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-contrast transition hover:opacity-90 disabled:opacity-50"
                                >
                                    <KeyRound className="h-4 w-4" />
                                    {password.processing ? 'Modification…' : 'Modifier le mot de passe'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function PasswordField({ id, label, value, visible, onToggle, onChange, error, autoComplete }) {
    return (
        <div>
            <label htmlFor={id} className="mb-1 block text-sm font-medium text-slate-700">
                {label}
            </label>
            <div className="relative">
                <input
                    id={id}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 pr-10"
                    autoComplete={autoComplete}
                />
                <button
                    type="button"
                    onClick={onToggle}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    aria-label={visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                >
                    {visible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
            </div>
            {error && <p className="mt-1 text-xs text-rose-600">{error}</p>}
        </div>
    );
}