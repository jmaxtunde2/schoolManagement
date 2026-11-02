import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    BookOpenCheck,
    CalendarDays,
    Clock3,
    GraduationCap,
    ShieldCheck,
    UserRound,
} from 'lucide-react';

function formatScore(evaluation) {
    if (evaluation.is_absent) {
        return 'Absent';
    }

    if (evaluation.score === null || evaluation.score === undefined) {
        return '—';
    }

    return `${evaluation.score} / ${evaluation.max_score}`;
}

export default function Show({
    school = {},
    parent = {},
    student = {},
    evaluations = [],
    expires_at,
    login_url,
    parent_url,
    is_authenticated = false,
}) {
    const primary = school.primary_color || '#059669';
    const secondary = school.secondary_color || '#2563EB';
    const brandName = school.short_name || school.name || 'CoriSchool';
    const expiresLabel = expires_at
        ? new Date(expires_at).toLocaleDateString('fr-FR', {
              day: '2-digit',
              month: 'long',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : null;

    return (
        <>
            <Head title={`Résultats — ${brandName}`}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>

            <div className="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900">
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div
                        className="absolute -left-40 -top-40 h-[34rem] w-[34rem] rounded-full opacity-[0.10] blur-3xl"
                        style={{ backgroundColor: primary }}
                    />
                    <div
                        className="absolute -bottom-48 -right-40 h-[38rem] w-[38rem] rounded-full opacity-[0.08] blur-3xl"
                        style={{ backgroundColor: secondary }}
                    />
                </div>

                <header className="relative z-10 mx-auto flex w-full max-w-4xl items-center justify-between px-4 py-6 sm:px-6">
                    <div className="flex items-center gap-3">
                        <div
                            className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl text-sm font-black text-white shadow-lg"
                            style={{
                                background: `linear-gradient(145deg, ${primary}, ${secondary})`,
                            }}
                        >
                            {school.logo_url ? (
                                <img
                                    src={school.logo_url}
                                    alt=""
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <GraduationCap
                                    className="h-6 w-6"
                                    strokeWidth={1.8}
                                />
                            )}
                        </div>

                        <div className="min-w-0">
                            <p className="truncate text-base font-black tracking-tight text-slate-950">
                                {brandName}
                            </p>
                            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">
                                Résultats scolaires
                            </p>
                        </div>
                    </div>

                    <span className="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 text-[11px] font-bold text-slate-600 shadow-sm ring-1 ring-slate-200">
                        <ShieldCheck className="h-3.5 w-3.5" />
                        Accès sécurisé
                    </span>
                </header>

                <main className="relative z-10 mx-auto w-full max-w-4xl px-4 pb-14 sm:px-6">
                    <div className="overflow-hidden rounded-[2rem] border border-white/80 bg-white/95 shadow-2xl shadow-slate-900/10 backdrop-blur">
                        <div
                            className="px-6 py-8 text-white sm:px-8"
                            style={{
                                background: `linear-gradient(145deg, ${primary}, ${secondary})`,
                            }}
                        >
                            <p className="text-xs font-bold uppercase tracking-[0.22em] text-white/70">
                                Bonjour {parent.name || 'cher parent'}
                            </p>

                            <h1 className="mt-2 text-2xl font-black tracking-tight sm:text-3xl">
                                {student.name}
                            </h1>

                            <div className="mt-2 flex flex-wrap items-center gap-2 text-sm text-white/85">
                                <span className="inline-flex items-center gap-1.5">
                                    <UserRound className="h-4 w-4" />
                                    {student.class_name || 'Classe non définie'}
                                </span>

                                {student.matricule && (
                                    <>
                                        <span className="text-white/40">•</span>
                                        <span className="rounded-md bg-white/15 px-2 py-0.5 text-xs font-semibold">
                                            {student.matricule}
                                        </span>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="space-y-5 px-5 py-7 sm:px-8">
                            <div className="flex items-center gap-2">
                                <BookOpenCheck className="h-5 w-5 text-slate-400" />
                                <h2 className="text-sm font-bold uppercase tracking-wider text-slate-500">
                                    {evaluations.length > 1
                                        ? 'Évaluations concernées'
                                        : 'Évaluation concernée'}
                                </h2>
                            </div>

                            <div className="space-y-4">
                                {evaluations.map((evaluation, index) => (
                                    <article
                                        key={`${evaluation.subject}-${index}`}
                                        className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div className="min-w-0">
                                                <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">
                                                    {evaluation.subject}
                                                </p>
                                                <h3 className="mt-1 text-lg font-bold text-slate-950">
                                                    {evaluation.title}
                                                </h3>

                                                <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-medium text-slate-500">
                                                    {evaluation.date && (
                                                        <span className="inline-flex items-center gap-1.5">
                                                            <CalendarDays className="h-3.5 w-3.5" />
                                                            {evaluation.date}
                                                        </span>
                                                    )}

                                                    {evaluation.type && (
                                                        <span>{evaluation.type}</span>
                                                    )}

                                                    {evaluation.period && (
                                                        <span>
                                                            {evaluation.period}
                                                        </span>
                                                    )}

                                                    <span>
                                                        Coef.{' '}
                                                        {evaluation.coefficient}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="shrink-0 rounded-2xl bg-slate-50 px-5 py-4 text-center ring-1 ring-slate-100">
                                                <p className="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                                    Note
                                                </p>
                                                <p
                                                    className={`mt-1 text-2xl font-black ${
                                                        evaluation.is_absent
                                                            ? 'text-amber-600'
                                                            : 'text-slate-950'
                                                    }`}
                                                >
                                                    {formatScore(evaluation)}
                                                </p>
                                            </div>
                                        </div>

                                        {evaluation.appreciation && (
                                            <p className="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                                {evaluation.appreciation}
                                            </p>
                                        )}
                                    </article>
                                ))}
                            </div>

                            <Link
                                href={parent_url || login_url}
                                className="group flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3.5 text-sm font-bold text-white shadow-lg transition hover:opacity-95"
                                style={{
                                    background: `linear-gradient(135deg, ${primary}, ${secondary})`,
                                }}
                            >
                                {is_authenticated
                                    ? 'Accéder à mon espace parent'
                                    : 'Se connecter à mon espace parent'}
                                <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                            </Link>

                            <div className="flex flex-col items-center gap-2 text-center">
                                {expiresLabel && (
                                    <p className="inline-flex items-center gap-1.5 text-xs text-slate-400">
                                        <Clock3 className="h-3.5 w-3.5" />
                                        Ce lien reste valable jusqu’au{' '}
                                        {expiresLabel}.
                                    </p>
                                )}

                                <p className="inline-flex items-center gap-1.5 text-xs text-slate-400">
                                    <Award className="h-3.5 w-3.5" />
                                    Seules les notes de cet enfant sont
                                    affichées sur cette page.
                                </p>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
