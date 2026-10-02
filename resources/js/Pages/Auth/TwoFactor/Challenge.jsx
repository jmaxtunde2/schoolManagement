import { Head, router, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import {
    ArrowLeft,
    CheckCircle2,
    KeyRound,
    LockKeyhole,
    ShieldCheck,
    Smartphone,
} from 'lucide-react';

import GuestLayout from '@/Layouts/GuestLayout';
import Input from '@/Components/UI/Input';
import Button from '@/Components/UI/Button';

export default function Challenge({
    purpose = 'login',
    return: back = null,
    pendingAction = null,
}) {
    const pendingActionRef = useRef(pendingAction);

    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        code: '',
        purpose,
        return: back,
    });

    const isRecoveryCode =
        data.code.trim().length > 6;

    const handleSubmit = (event) => {
        event.preventDefault();

        if (!data.code.trim() || processing) {
            return;
        }

        post(route('two-factor.verify'), {
            preserveScroll: true,
            onSuccess: () => {
                const action = pendingActionRef.current;

                if (action) {
                    router.visit(action.url, {
                        method: action.method,
                        data: action.data,
                        preserveScroll: true,
                    });
                }
            },
        });
    };

    const purposeLabel = {
        login: 'connexion à votre compte',
        submit_evaluation: 'soumission de l’évaluation',
        validate_evaluation: 'validation de l’évaluation',
        manage_users: 'gestion des utilisateurs',
    }[purpose] ?? 'cette opération';

    return (
        <GuestLayout>
            <Head title="Vérification de sécurité" />

            <div className="min-h-[calc(100vh-4rem)] bg-gradient-to-br from-slate-50 via-white to-emerald-50/40 px-4 py-8 sm:py-12">
                <div className="mx-auto max-w-md">

                    {/* Security badge */}
                    <div className="mb-6 flex justify-center">
                        <div className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 shadow-sm">
                            <ShieldCheck className="h-4 w-4" />
                            Vérification de sécurité
                        </div>
                    </div>

                    {/* Main card */}
                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">

                        {/* Header */}
                        <div className="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 px-6 py-8 text-white sm:px-8">
                            <div className="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10" />
                            <div className="absolute -bottom-16 -left-8 h-40 w-40 rounded-full bg-white/5" />

                            <div className="relative">
                                <div className="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20 backdrop-blur-sm">
                                    <LockKeyhole className="h-7 w-7" />
                                </div>

                                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                                    Vérification Authenticator
                                </h1>

                                <p className="mt-2 max-w-sm text-sm leading-6 text-emerald-50">
                                    Confirmez votre identité pour continuer
                                    {purpose !== 'login' && (
                                        <>
                                            {' '}
                                            la {purposeLabel}
                                        </>
                                    )}.
                                </p>
                            </div>
                        </div>

                        <div className="p-6 sm:p-8">

                            {/* Explanation */}
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div className="flex gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-600 shadow-sm ring-1 ring-slate-200">
                                        <Smartphone className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <h2 className="text-sm font-semibold text-slate-900">
                                            Code à usage temporaire
                                        </h2>

                                        <p className="mt-1 text-sm leading-5 text-slate-600">
                                            Ouvrez votre application
                                            Authenticator et saisissez le
                                            code à 6 chiffres affiché.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Form */}
                            <form
                                className="mt-6 space-y-5"
                                onSubmit={handleSubmit}
                            >
                                <div>
                                    <label
                                        htmlFor="two-factor-code"
                                        className="mb-2 block text-sm font-semibold text-slate-800"
                                    >
                                        Code de vérification
                                    </label>

                                    <div className="relative">
                                        <KeyRound className="pointer-events-none absolute left-4 top-1/2 z-10 h-5 w-5 -translate-y-1/2 text-slate-400" />

                                        <Input
                                            id="two-factor-code"
                                            type="text"
                                            autoFocus
                                            autoComplete="one-time-code"
                                            inputMode={
                                                isRecoveryCode
                                                    ? 'text'
                                                    : 'numeric'
                                            }
                                            maxLength={30}
                                            placeholder="000000"
                                            value={data.code}
                                            onChange={(event) => {
                                                setData(
                                                    'code',
                                                    event.target.value
                                                );
                                            }}
                                            className="h-14 pl-12 text-center text-xl font-semibold tracking-[0.35em]"
                                        />
                                    </div>

                                    {errors.code && (
                                        <div className="mt-2 flex items-start gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                                            <span className="mt-0.5 font-bold">
                                                !
                                            </span>
                                            <span>
                                                {errors.code}
                                            </span>
                                        </div>
                                    )}

                                    {!errors.code && (
                                        <p className="mt-2 text-xs text-slate-500">
                                            Le code change régulièrement et
                                            ne doit être communiqué à personne.
                                        </p>
                                    )}
                                </div>

                                {/* Recovery information */}
                                {/* <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <div className="flex gap-3">
                                        <KeyRound className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />

                                        <div>
                                            <p className="text-sm font-semibold text-amber-900">
                                                Vous n’avez pas accès à Authenticator ?
                                            </p>

                                            <p className="mt-1 text-sm leading-5 text-amber-800">
                                                Vous pouvez saisir l’un de vos
                                                codes de récupération à la place.
                                            </p>
                                        </div>
                                    </div>
                                </div> */}

                                <Button
                                    type="submit"
                                    className="h-12 w-full justify-center bg-gradient-to-r from-emerald-600 to-teal-600 text-base font-semibold shadow-lg shadow-emerald-500/20 hover:from-emerald-700 hover:to-teal-700"
                                    loading={processing}
                                    disabled={
                                        processing ||
                                        !data.code.trim()
                                    }
                                >
                                    <CheckCircle2 className="mr-2 h-5 w-5" />
                                    Vérifier et continuer
                                </Button>
                            </form>

                            {/* Security footer */}
                            <div className="mt-6 flex items-center justify-center gap-2 border-t border-slate-100 pt-5 text-xs text-slate-500">
                                <ShieldCheck className="h-4 w-4 text-emerald-600" />
                                <span>
                                    Connexion protégée par authentification
                                    à deux facteurs
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Back action */}
                    {back && (
                        <div className="mt-5 text-center">
                            <button
                                type="button"
                                onClick={() =>
                                    router.post(route('two-factor.cancel'), {
                                        purpose,
                                        return: back,
                                    })
                                }
                                className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-800"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Retour
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </GuestLayout>
    );
}