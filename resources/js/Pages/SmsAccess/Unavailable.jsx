import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    GraduationCap,
    ShieldAlert,
    ShieldCheck,
} from 'lucide-react';

export default function Unavailable({
    status = 410,
    title = 'Lien expiré',
    message = 'Ce lien n’est plus disponible.',
    hint,
    school = {},
    login_url,
}) {
    const primary = school.primary_color || '#059669';
    const secondary = school.secondary_color || '#2563EB';
    const brandName = school.short_name || school.name || 'CoriSchool';

    return (
        <>
            <Head title={`${title} — ${brandName}`}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 py-10 text-slate-900">
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div
                        className="absolute -left-40 -top-40 h-[32rem] w-[32rem] rounded-full opacity-[0.10] blur-3xl"
                        style={{ backgroundColor: primary }}
                    />
                    <div
                        className="absolute -bottom-48 -right-40 h-[36rem] w-[36rem] rounded-full opacity-[0.08] blur-3xl"
                        style={{ backgroundColor: secondary }}
                    />
                </div>

                <div className="relative z-10 w-full max-w-lg overflow-hidden rounded-[2rem] border border-white/80 bg-white/95 shadow-2xl shadow-slate-900/10 backdrop-blur">
                    <div
                        className="px-6 py-7 text-white"
                        style={{
                            background: `linear-gradient(145deg, ${primary}, ${secondary})`,
                        }}
                    >
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                <GraduationCap className="h-6 w-6" />
                            </div>
                            <div>
                                <p className="text-base font-black tracking-tight">
                                    {brandName}
                                </p>
                                <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-white/70">
                                    Accès SMS sécurisé
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="px-6 py-8 text-center sm:px-8">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                            <ShieldAlert className="h-8 w-8" />
                        </div>

                        <p className="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-slate-400">
                            Erreur {status}
                        </p>

                        <h1 className="mt-2 text-2xl font-black tracking-tight text-slate-950">
                            {title}
                        </h1>

                        <p className="mt-3 text-sm leading-relaxed text-slate-600">
                            {message}
                        </p>

                        {hint && (
                            <p className="mt-2 text-sm leading-relaxed text-slate-500">
                                {hint}
                            </p>
                        )}

                        <Link
                            href={login_url || '/login'}
                            className="group mt-7 flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3.5 text-sm font-bold text-white shadow-lg transition hover:opacity-95"
                            style={{
                                background: `linear-gradient(135deg, ${primary}, ${secondary})`,
                            }}
                        >
                            Accéder à mon espace parent
                            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                        </Link>

                        <p className="mt-5 inline-flex items-center justify-center gap-1.5 text-xs text-slate-400">
                            <ShieldCheck className="h-3.5 w-3.5" />
                            Vos identifiants et votre authentification à deux
                            facteurs restent inchangés.
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
