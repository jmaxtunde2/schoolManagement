import { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, GraduationCap, Search, School } from 'lucide-react';

import InstallPwaButton from '@/Components/PWA/InstallPwaButton';
import { readParentSchool } from '@/Utils/pwa';

/**
 * Écran de reprise de la PWA installée lorsqu'aucune session parent valide
 * n'est disponible. Il ne lit que le dernier établissement mémorisé côté
 * navigateur (slug public) et ne donne accès à aucune donnée scolaire.
 */
export default function PwaWelcome({ schoolsUrl, homeUrl }) {
    const [lastSchool, setLastSchool] = useState(null);

    useEffect(() => {
        setLastSchool(readParentSchool());
    }, []);

    return (
        <>
            <Head title="CoriSchool — Espace parent">
                <meta name="description" content="Retrouvez l’Espace Parent de votre établissement sur CoriSchool." />
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <div className="relative min-h-screen overflow-hidden bg-slate-950 px-4 py-10 text-white">
                <div className="pointer-events-none absolute inset-0">
                    <div className="absolute left-1/2 top-0 h-[420px] w-[720px] -translate-x-1/2 rounded-full bg-emerald-500/20 blur-[120px]" />
                    <div className="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-teal-500/20 blur-[100px]" />
                </div>

                <div className="relative mx-auto flex min-h-[calc(100vh-5rem)] max-w-lg flex-col justify-center">
                    <div className="mb-8 flex items-center justify-center gap-3">
                        <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-xl shadow-emerald-500/20">
                            <School className="h-7 w-7" />
                        </span>
                        <div>
                            <p className="text-lg font-black tracking-tight">
                                Cori<span className="text-emerald-400">School</span>
                            </p>
                            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">
                                Plateforme scolaire numérique
                            </p>
                        </div>
                    </div>

                    <div className="rounded-3xl border border-white/10 bg-white p-6 text-slate-900 shadow-2xl sm:p-8">
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                            <GraduationCap className="h-6 w-6" />
                        </div>

                        <h1 className="mt-5 text-2xl font-black tracking-tight text-slate-950">
                            Bienvenue sur CoriSchool
                        </h1>
                        <p className="mt-2 text-sm leading-6 text-slate-500">
                            Pour accéder à votre Espace Parent, retrouvez votre
                            établissement. Votre code d’accès ne sera demandé que si
                            votre session n’est plus active.
                        </p>

                        {lastSchool && (
                            <div className="mt-6">
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Dernier établissement
                                </p>
                                <Link
                                    href={route('parent-access.login.slug', { slug: lastSchool.slug })}
                                    className="mt-2 flex min-h-14 w-full items-center justify-between gap-3 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500"
                                >
                                    <span className="min-w-0">
                                        <span className="block truncate">
                                            {lastSchool.name || 'Mon établissement'}
                                        </span>
                                        <span className="block truncate text-xs font-medium text-emerald-100">
                                            Ouvrir mon espace parent
                                        </span>
                                    </span>
                                    <ArrowRight className="h-5 w-5 shrink-0" />
                                </Link>
                            </div>
                        )}

                        <div className={lastSchool ? 'mt-6 border-t border-slate-100 pt-6' : 'mt-6'}>
                            <Link
                                href={schoolsUrl}
                                className="flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                            >
                                <Search className="h-4 w-4" />
                                Rechercher mon établissement
                            </Link>
                        </div>

                        <div className="mt-4 flex justify-center">
                            <InstallPwaButton />
                        </div>
                    </div>

                    <div className="mt-6 text-center">
                        <Link
                            href={homeUrl}
                            className="text-sm font-semibold text-slate-400 transition hover:text-white"
                        >
                            Retour à l’accueil CoriSchool
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
