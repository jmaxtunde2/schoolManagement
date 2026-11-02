import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    Ban,
    BookOpen,
    Clock3,
    Compass,
    CreditCard,
    GraduationCap,
    Home,
    LifeBuoy,
    LockKeyhole,
    Mail,
    MessageCircle,
    RefreshCw,
    ServerCrash,
    ShieldAlert,
    TimerOff,
    Waves,
    Wrench,
} from 'lucide-react';

const ICONS = {
    400: AlertTriangle,
    401: LockKeyhole,
    402: CreditCard,
    403: ShieldAlert,
    404: Compass,
    405: Ban,
    408: Clock3,
    419: TimerOff,
    429: Clock3,
    500: ServerCrash,
    501: Wrench,
    502: Waves,
    503: Wrench,
    504: Waves,
    505: ServerCrash,
};

const QUICK_LINKS = [
    { label: 'Accueil', href: '/', icon: Home },
    { label: 'Nos écoles', href: '/ecoles', icon: GraduationCap },
    { label: 'Banque d’épreuves', href: '/banque-epreuves', icon: BookOpen },
];

function digitsOnly(value) {
    return String(value ?? '').replace(/\D+/g, '');
}

export default function Error({ status = 500, title, message, hint, support }) {
    const { props } = usePage();
    const school = props.school ?? {};
    const hasSchool = Boolean(school?.id);
    const brandName = hasSchool
        ? school.short_name || school.name || 'CoriSchool'
        : 'CoriSchool';

    const numericStatus = Number(status) || 500;
    const Icon = ICONS[numericStatus] ?? AlertTriangle;

    const canRetry = [408, 429, 500, 502, 503, 504].includes(numericStatus);
    const needsLogin = [401, 419].includes(numericStatus);
    const isNotFound = numericStatus === 404;

    const whatsapp = support?.whatsapp || school?.phone;
    const whatsappDigits = digitsOnly(whatsapp);
    const whatsappMessage =
        support?.whatsapp_message ||
        'Bonjour, je souhaite signaler une erreur sur CoriSchool.';
    const supportEmail = school?.email;

    return (
        <>
            <Head title={`${numericStatus} — ${title ?? 'Erreur'}`}>
                <meta name="robots" content="noindex" />
            </Head>

            <div className="relative flex min-h-screen flex-col overflow-hidden bg-slate-50 text-slate-900">
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div
                        className="absolute -left-48 -top-48 h-[36rem] w-[36rem] rounded-full opacity-[0.10] blur-3xl"
                        style={{ backgroundColor: 'var(--color-primary)' }}
                    />
                    <div
                        className="absolute -bottom-56 -right-48 h-[40rem] w-[40rem] rounded-full opacity-[0.08] blur-3xl"
                        style={{ backgroundColor: 'var(--color-secondary)' }}
                    />
                    <div
                        className="absolute inset-0 opacity-[0.03]"
                        style={{
                            backgroundImage: `
                                linear-gradient(to right, #64748b 1px, transparent 1px),
                                linear-gradient(to bottom, #64748b 1px, transparent 1px)
                            `,
                            backgroundSize: '48px 48px',
                        }}
                    />
                </div>

                <header className="relative z-10 mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                    <Link href="/" className="group flex items-center gap-3">
                        <div
                            className="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl text-sm font-black text-white shadow-lg"
                            style={{
                                background:
                                    'linear-gradient(145deg, var(--color-primary), var(--color-secondary))',
                            }}
                        >
                            {school?.logo_url ? (
                                <img
                                    src={school.logo_url}
                                    alt=""
                                    className="h-full w-full object-cover"
                                />
                            ) : (
                                <GraduationCap className="h-6 w-6" strokeWidth={1.8} />
                            )}
                        </div>

                        <div className="min-w-0">
                            <p className="truncate text-base font-black tracking-tight text-slate-950">
                                {brandName}
                            </p>
                            <p className="hidden text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400 sm:block">
                                Plateforme scolaire numérique
                            </p>
                        </div>
                    </Link>

                    <Link
                        href="/"
                        className="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-white sm:inline-flex"
                    >
                        <Home className="h-4 w-4" />
                        Accueil
                    </Link>
                </header>

                <main className="relative z-10 flex flex-1 items-center justify-center px-4 pb-12 pt-4 sm:px-6 lg:px-8">
                    <div className="w-full max-w-5xl">
                        <div className="overflow-hidden rounded-[2rem] border border-white/80 bg-white/90 shadow-2xl shadow-slate-900/10 backdrop-blur-xl">
                            <div className="grid lg:grid-cols-[0.92fr_1.08fr]">
                                <div
                                    className="relative flex min-h-[22rem] flex-col justify-between overflow-hidden p-8 text-white sm:p-10 lg:min-h-[34rem]"
                                    style={{
                                        background: `
                                            linear-gradient(
                                                150deg,
                                                color-mix(in srgb, var(--color-primary) 96%, #0f172a),
                                                color-mix(in srgb, var(--color-secondary) 72%, #0f172a)
                                            )
                                        `,
                                    }}
                                >
                                    <div className="absolute -right-28 -top-28 h-80 w-80 rounded-full border border-white/10" />
                                    <div className="absolute -right-8 -top-8 h-48 w-48 rounded-full border border-white/10" />
                                    <div className="absolute -bottom-40 -left-40 h-96 w-96 rounded-full border border-white/10" />
                                    <div className="absolute right-10 top-12 grid grid-cols-4 gap-3 opacity-20">
                                        {Array.from({ length: 16 }).map((_, index) => (
                                            <span
                                                key={index}
                                                className="h-1.5 w-1.5 rounded-full bg-white"
                                            />
                                        ))}
                                    </div>

                                    <div className="relative z-10">
                                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-white/80 backdrop-blur">
                                            <AlertTriangle className="h-3.5 w-3.5" />
                                            Code {numericStatus}
                                        </span>
                                    </div>

                                    <div className="relative z-10 py-10">
                                        <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl border border-white/15 bg-white/10 shadow-2xl backdrop-blur">
                                            <Icon className="h-10 w-10 text-white" strokeWidth={1.5} />
                                        </div>

                                        <p
                                            className="select-none text-[6.5rem] font-black leading-none tracking-tighter text-white/95 sm:text-[8rem]"
                                            aria-hidden="true"
                                        >
                                            {numericStatus}
                                        </p>
                                    </div>

                                    <div className="relative z-10 flex items-center gap-2 text-xs font-semibold text-white/55">
                                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-300" />
                                        {brandName} · Plateforme scolaire numérique
                                    </div>
                                </div>

                                <div className="flex flex-col justify-center p-8 sm:p-10 lg:p-12">
                                    <p
                                        className="mb-2 text-sm font-bold uppercase tracking-[0.16em]"
                                        style={{ color: 'var(--color-primary)' }}
                                    >
                                        Oups, quelque chose s’est mal passé
                                    </p>

                                    <h1 className="text-3xl font-black leading-tight tracking-tight text-slate-950 sm:text-4xl">
                                        {title ?? 'Une erreur est survenue'}
                                    </h1>

                                    <p className="mt-4 text-base leading-7 text-slate-600">
                                        {message ??
                                            "La page n'a pas pu être affichée correctement."}
                                    </p>

                                    {hint && (
                                        <p className="mt-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-500">
                                            {hint}
                                        </p>
                                    )}

                                    <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                        <Link
                                            href="/"
                                            className="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-bold text-white shadow-lg shadow-slate-900/10 transition hover:opacity-90"
                                            style={{ backgroundColor: 'var(--color-primary)' }}
                                        >
                                            <Home className="h-4 w-4" />
                                            Retour à l’accueil
                                        </Link>

                                        <button
                                            type="button"
                                            onClick={() => window.history.back()}
                                            className="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                                        >
                                            <ArrowLeft className="h-4 w-4" />
                                            Page précédente
                                        </button>

                                        {canRetry && (
                                            <button
                                                type="button"
                                                onClick={() => window.location.reload()}
                                                className="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                                            >
                                                <RefreshCw className="h-4 w-4" />
                                                Réessayer
                                            </button>
                                        )}

                                        {needsLogin && (
                                            <Link
                                                href="/login"
                                                className="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                                            >
                                                <LockKeyhole className="h-4 w-4" />
                                                Se connecter
                                            </Link>
                                        )}
                                    </div>

                                    {isNotFound && (
                                        <div className="mt-8 border-t border-slate-100 pt-6">
                                            <p className="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">
                                                Pages utiles
                                            </p>

                                            <div className="grid gap-2 sm:grid-cols-3">
                                                {QUICK_LINKS.map((link) => {
                                                    const QuickIcon = link.icon;

                                                    return (
                                                        <Link
                                                            key={link.href}
                                                            href={link.href}
                                                            className="group flex items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-200 hover:bg-white hover:text-slate-900"
                                                        >
                                                            <QuickIcon className="h-4 w-4 shrink-0 text-slate-400 group-hover:text-emerald-600" />
                                                            <span className="truncate">{link.label}</span>
                                                            <ArrowRight className="ml-auto h-3.5 w-3.5 shrink-0 text-slate-300 group-hover:text-slate-500" />
                                                        </Link>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}

                                    {(whatsappDigits || supportEmail) && (
                                        <div className="mt-8 flex flex-col gap-2 border-t border-slate-100 pt-6 text-sm sm:flex-row sm:items-center sm:justify-between">
                                            <span className="inline-flex items-center gap-2 font-semibold text-slate-500">
                                                <LifeBuoy className="h-4 w-4" />
                                                Besoin d’aide ?
                                            </span>

                                            <div className="flex flex-wrap gap-2">
                                                {whatsappDigits && (
                                                    <a
                                                        href={`https://wa.me/${whatsappDigits}?text=${encodeURIComponent(whatsappMessage)}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100"
                                                    >
                                                        <MessageCircle className="h-3.5 w-3.5" />
                                                        WhatsApp
                                                    </a>
                                                )}

                                                {supportEmail && (
                                                    <a
                                                        href={`mailto:${supportEmail}`}
                                                        className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50"
                                                    >
                                                        <Mail className="h-3.5 w-3.5" />
                                                        {supportEmail}
                                                    </a>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                <footer className="relative z-10 mx-auto flex w-full max-w-7xl flex-col items-center justify-between gap-2 px-4 pb-8 text-center text-xs text-slate-400 sm:flex-row sm:px-6 sm:text-left lg:px-8">
                    <p>
                        © {new Date().getFullYear()} CoriSchool — Plateforme scolaire numérique
                    </p>
                    <p>
                        Une solution{' '}
                        <a
                            href="https://coriyase.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="font-semibold text-slate-500 transition hover:text-slate-700"
                        >
                            Coriyase Technologies
                        </a>
                    </p>
                </footer>
            </div>
        </>
    );
}
