import { usePage } from '@inertiajs/react';
import {
    GraduationCap,
    BookOpen,
    Laptop,
    ShieldCheck,
    Sparkles,
    BarChart3,
    Users,
} from 'lucide-react';

export default function GuestLayout({ children }) {
    const { school } = usePage().props;

    const primaryColor = 'var(--color-primary)';

    return (
        <div className="relative min-h-screen overflow-hidden bg-slate-50">

            {/* =========================================================
                BACKGROUND
            ========================================================== */}
            <div className="pointer-events-none absolute inset-0 overflow-hidden">

                {/* Primary glow */}
                <div
                    className="absolute -left-40 -top-40 h-[34rem] w-[34rem] rounded-full opacity-[0.08] blur-3xl"
                    style={{ backgroundColor: primaryColor }}
                />

                {/* Secondary glow */}
                <div
                    className="absolute -bottom-40 -right-40 h-[38rem] w-[38rem] rounded-full opacity-[0.07] blur-3xl"
                    style={{ backgroundColor: primaryColor }}
                />

                {/* Subtle grid */}
                <div
                    className="absolute inset-0 opacity-[0.025]"
                    style={{
                        backgroundImage: `
                            linear-gradient(to right, #64748b 1px, transparent 1px),
                            linear-gradient(to bottom, #64748b 1px, transparent 1px)
                        `,
                        backgroundSize: '48px 48px',
                    }}
                />
            </div>

            {/* =========================================================
                MAIN CONTAINER
            ========================================================== */}
            <div className="relative z-10 mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-6 sm:px-6 lg:px-8">

                <div className="grid w-full overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-2">

                    {/* =================================================
                        LEFT — CORISCHOOL PRESENTATION
                    ================================================== */}
                    <div
                        className="relative hidden min-h-[700px] overflow-hidden p-10 lg:flex lg:flex-col lg:justify-between"
                        style={{
                            background: `
                                linear-gradient(
                                    145deg,
                                    color-mix(in srgb, ${primaryColor} 96%, #0f172a),
                                    color-mix(in srgb, ${primaryColor} 70%, #0f172a)
                                )
                            `,
                        }}
                    >

                        {/* Decorative circles */}
                        <div className="absolute -right-28 -top-28 h-80 w-80 rounded-full border border-white/10" />
                        <div className="absolute -right-8 -top-8 h-48 w-48 rounded-full border border-white/10" />
                        <div className="absolute -bottom-40 -left-40 h-96 w-96 rounded-full border border-white/10" />

                        {/* Decorative dots */}
                        <div className="absolute right-12 top-14 grid grid-cols-4 gap-3 opacity-20">
                            {Array.from({ length: 16 }).map((_, index) => (
                                <span
                                    key={index}
                                    className="h-1.5 w-1.5 rounded-full rounded-full bg-white"
                                />
                            ))}
                        </div>

                        {/* =================================================
                            BRAND
                        ================================================== */}
                        <div className="relative z-10">
                            <div className="flex items-center gap-3">

                                {/* CoriSchool logo */}
                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-sm font-bold shadow-lg">
                                    <span style={{ color: primaryColor }}>
                                        CO
                                    </span>
                                </div>

                                <div>
                                    <p className="text-base font-bold text-white">
                                        CoriSchool
                                    </p>

                                    <p className="text-xs text-white/60">
                                        La plateforme numérique scolaire
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* =================================================
                            CENTRAL VISUAL
                        ================================================== */}
                        <div className="relative z-10">

                            <div className="relative mx-auto mb-10 flex h-64 max-w-md items-center justify-center">

                                {/* Glow */}
                                <div className="absolute h-60 w-60 rounded-full bg-white/10 blur-3xl" />

                                {/* Main circle */}
                                <div className="relative flex h-48 w-48 items-center justify-center rounded-full border border-white/20 bg-white/10 shadow-2xl backdrop-blur-md">

                                    <div className="flex h-32 w-32 items-center justify-center rounded-full bg-white shadow-xl">
                                        <GraduationCap
                                            size={68}
                                            strokeWidth={1.4}
                                            style={{ color: primaryColor }}
                                        />
                                    </div>

                                </div>

                                {/* =================================================
                                    FLOATING CARD — ACADEMIC
                                ================================================== */}
                                <div className="absolute left-0 top-5 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 shadow-xl backdrop-blur-md">

                                    <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                                        <BookOpen
                                            size={18}
                                            className="text-white"
                                        />
                                    </div>

                                    <div>
                                        <p className="text-xs font-semibold text-white">
                                            Suivi académique
                                        </p>

                                        <p className="text-[10px] text-white/60">
                                            Notes & résultats
                                        </p>
                                    </div>
                                </div>

                                {/* =================================================
                                    FLOATING CARD — CONNECTIVITY
                                ================================================== */}
                                <div className="absolute bottom-5 right-0 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 shadow-xl backdrop-blur-md">

                                    <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                                        <Users
                                            size={18}
                                            className="text-white"
                                        />
                                    </div>

                                    <div>
                                        <p className="text-xs font-semibold text-white">
                                            École connectée
                                        </p>

                                        <p className="text-[10px] text-white/60">
                                            École & familles
                                        </p>
                                    </div>
                                </div>

                            </div>

                            {/* =================================================
                                TEXT CONTENT
                            ================================================== */}
                            <div className="max-w-lg">

                                <div className="mb-4 flex items-center gap-2">
                                    <Sparkles
                                        size={15}
                                        className="text-white/80"
                                    />

                                    <span className="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">
                                        Éducation • Innovation • Excellence
                                    </span>
                                </div>

                                <h2 className="text-3xl font-bold leading-tight tracking-tight text-white xl:text-4xl">
                                    Une école.
                                    <br />
                                    Une plateforme.
                                    <span className="block text-white/65">
                                        Tout le suivi scolaire.
                                    </span>
                                </h2>

                                <p className="mt-5 max-w-md text-sm leading-6 text-white/65">
                                    CoriSchool centralise la gestion de
                                    l'établissement, le suivi académique et
                                    la communication avec les familles dans
                                    un environnement simple, moderne et
                                    sécurisé.
                                </p>

                            </div>
                        </div>

                        {/* =================================================
                            FEATURES
                        ================================================== */}
                        <div className="relative z-10 grid grid-cols-2 gap-3 border-t border-white/10 pt-6">

                            <div className="flex items-center gap-3">
                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10">
                                    <ShieldCheck
                                        size={17}
                                        className="text-white"
                                    />
                                </div>

                                <div>
                                    <p className="text-xs font-semibold text-white">
                                        Données protégées
                                    </p>

                                    <p className="text-[10px] text-white/50">
                                        Environnement sécurisé
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10">
                                    <Laptop
                                        size={17}
                                        className="text-white"
                                    />
                                </div>

                                <div>
                                    <p className="text-xs font-semibold text-white">
                                        Accessible en ligne
                                    </p>

                                    <p className="text-[10px] text-white/50">
                                        Partout et à tout moment
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    {/* =================================================
                        RIGHT — AUTHENTICATION
                    ================================================== */}
                    <div className="flex min-h-[700px] items-center justify-center px-6 py-10 sm:px-12 lg:px-14">

                        <div className="w-full max-w-md">

                            {/* =================================================
                                MOBILE BRANDING
                            ================================================== */}
                            <div className="mb-8 flex flex-col items-center text-center lg:hidden">

                                <div
                                    className="mb-4 flex h-20 w-20 items-center justify-center rounded-2xl text-2xl font-bold text-white shadow-lg"
                                    style={{
                                        backgroundColor: primaryColor,
                                    }}
                                >
                                    CO
                                </div>

                                <h1 className="text-xl font-bold text-slate-900">
                                    CoriSchool
                                </h1>

                                <p className="mt-1 text-sm text-slate-500">
                                    La plateforme numérique scolaire
                                </p>
                            </div>

                            {/* =================================================
                                AUTH HEADING
                            ================================================== */}
                            <div className="mb-8">

                                <p
                                    className="mb-2 text-sm font-semibold"
                                    style={{ color: primaryColor }}
                                >
                                    Bienvenue sur CoriSchool
                                </p>

                                <h1 className="text-3xl font-bold tracking-tight text-slate-900">
                                    Accédez à votre espace
                                </h1>

                                <p className="mt-2 text-sm leading-6 text-slate-500">
                                    Connectez-vous pour accéder à votre
                                    environnement scolaire.
                                </p>

                            </div>

                            {/* =================================================
                                AUTH FORM
                            ================================================== */}
                            <div>
                                {children}
                            </div>

                            {/* =================================================
                                FOOTER
                            ================================================== */}
                            <div className="mt-10 border-t border-slate-100 pt-6 text-center">

                                <p className="text-xs text-slate-400">
                                    CoriSchool, Plateforme numérique scolaire
                                </p>

                                <p className="mt-1 text-xs text-slate-400">
                                    Une solution{' '}
                                    <span
                                        className="font-semibold"
                                        style={{ color: primaryColor }}
                                    >
                                        <a href="https://coriyase.com" target="_blank" rel="noopener noreferrer">
                                            Coriyase Technologies
                                        </a>
                                    </span>
                                </p>

                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    );
}