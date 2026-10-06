import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    BookOpen,
    CalendarDays,
    Check,
    ChevronDown,
    ClipboardCheck,
    FileText,
    GraduationCap,
    LayoutDashboard,
    Menu,
    MessageSquare,
    Receipt,
    School,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    UserCog,
    Users,
    Wallet,
    X,
} from 'lucide-react';

import { cn } from '@/Utils/cn';

/**
 * Landing page publique de CoriSchool (la plateforme).
 *
 * Elle est servie sur l'hôte de la plateforme, quand aucun établissement n'est résolu
 * par le TenantResolver. Les offres, la note SMS et la FAQ proviennent de `PricingPlan` :
 * aucun tarif, aucun effectif et aucun faux compteur n'est codé en dur ici.
 *
 * L'identité visuelle est volontairement fixe (emerald/teal) : il s'agit de la marque
 * CoriSchool, pas de l'identité d'un établissement, qui est portée par `--color-primary`.
 */
export default function CoriSchoolLanding({ plans = [], smsNote, faq = [] }) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [openFaq, setOpenFaq] = useState(null);

    return (
        <div className="min-h-screen bg-white text-slate-900">
            <Head title="CoriSchool — La gestion scolaire simple, connectée et intelligente">
                <meta
                    name="description"
                    content="CoriSchool réunit la gestion administrative, le suivi pédagogique et la relation avec les familles dans une seule plateforme par établissement."
                />
            </Head>

            <SiteHeader menuOpen={menuOpen} setMenuOpen={setMenuOpen} />

            <main>
                <Hero />
                <Problems />
                <Solution />
                <Features />
                <HowItWorks />
                <Pricing plans={plans} smsNote={smsNote} />
                <Faq items={faq} openFaq={openFaq} setOpenFaq={setOpenFaq} />
                <FinalCta />
            </main>

            <SiteFooter />
        </div>
    );
}

/* ==========================================================================
 | HEADER
 | ==========================================================================*/

const navigation = [
    ['Fonctionnalités', '#fonctionnalites'],
    ['Comment ça marche', '#fonctionnement'],
    ['Tarifs', '#tarifs'],
    ['Questions', '#faq'],
];

function SiteHeader({ menuOpen, setMenuOpen }) {
    return (
        <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <Link href="/" className="flex min-w-0 items-center gap-2.5">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-700 text-white">
                        <GraduationCap className="h-5 w-5" />
                    </span>
                    <span className="truncate text-base font-bold text-slate-950">
                        CoriSchool
                    </span>
                </Link>

                <nav className="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                    {navigation.map(([label, href]) => (
                        <a key={href} href={href} className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-950">
                            {label}
                        </a>
                    ))}
                </nav>

                <div className="hidden items-center gap-2 lg:flex">
                    <Link href={route('login')} className="inline-flex min-h-11 items-center justify-center rounded-md px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Se connecter
                    </Link>
                    <BrandButton href={route('school.register')}>
                        Créer mon école
                    </BrandButton>
                </div>

                <button
                    type="button"
                    onClick={() => setMenuOpen(!menuOpen)}
                    className="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-700 lg:hidden"
                    aria-label={menuOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
                    aria-expanded={menuOpen}
                >
                    {menuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                </button>
            </div>

            {menuOpen && (
                <nav className="border-t border-slate-100 bg-white px-4 py-3 lg:hidden" aria-label="Navigation mobile">
                    <div className="mx-auto grid max-w-7xl gap-1 sm:grid-cols-2">
                        {navigation.map(([label, href]) => (
                            <a key={href} href={href} onClick={() => setMenuOpen(false)} className="rounded-md px-3 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                {label}
                            </a>
                        ))}
                        <div className="flex flex-col gap-2 px-3 py-2 sm:col-span-2">
                            <Link href={route('login')} className="inline-flex min-h-11 items-center justify-center rounded-md border border-slate-300 px-4 text-sm font-semibold text-slate-700">
                                Se connecter
                            </Link>
                            <BrandButton href={route('school.register')}>
                                Créer mon école
                            </BrandButton>
                        </div>
                    </div>
                </nav>
            )}
        </header>
    );
}

/* ==========================================================================
 | SECTION 1 — ACCUEIL
 | ==========================================================================*/

function Hero() {
    return (
        <section className="relative isolate overflow-hidden bg-gradient-to-b from-emerald-50 via-white to-white">
            <div className="pointer-events-none absolute inset-0 -z-10" aria-hidden>
                <div className="absolute -left-32 -top-32 h-[30rem] w-[30rem] rounded-full bg-emerald-200/40 blur-3xl" />
                <div className="absolute -right-24 top-24 h-[26rem] w-[26rem] rounded-full bg-teal-200/40 blur-3xl" />
            </div>

            <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-24">
                <div>
                    <p className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800">
                        <Sparkles className="h-3.5 w-3.5" />
                        Logiciel de gestion scolaire
                    </p>

                    <h1 className="mt-5 text-4xl font-bold leading-tight tracking-tight text-slate-950 sm:text-5xl">
                        CoriSchool
                    </h1>

                    <p className="mt-3 text-xl font-semibold leading-8 text-emerald-700 sm:text-2xl">
                        La gestion scolaire simple, connectée et intelligente.
                    </p>

                    <p className="mt-5 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
                        Gérez votre établissement, suivez les performances de
                        vos élèves et rapprochez l'école des familles depuis
                        une seule plateforme.
                    </p>

                    <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                        <BrandButton href={route('school.register')} large>
                            Créer mon école
                        </BrandButton>

                        <OutlineButton
                            href="mailto:contact@coriyase.com?subject=Demande%20de%20d%C3%A9monstration%20CoriSchool"
                            large
                        >
                            Demander une démonstration
                        </OutlineButton>
                    </div>

                    <ul className="mt-8 grid gap-2.5 text-sm text-slate-600 sm:grid-cols-2">
                        {[
                            'Mise en service immédiate',
                            'Données séparées par école',
                            'Aucune installation requise',
                            '2FA sur les comptes du personnel',
                        ].map((item) => (
                            <li key={item} className="flex items-center gap-2">
                                <Check className="h-4 w-4 shrink-0 text-emerald-600" />
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>

                <DashboardMockup />
            </div>
        </section>
    );
}

/**
 * Illustration du tableau de bord.
 * Elle est décorative et sans valeur indicative : aucun effectif réel n'y est affiché.
 */
function DashboardMockup() {
    const tiles = [
        { label: 'Élèves', icon: GraduationCap },
        { label: 'Enseignants', icon: Users },
        { label: 'Classes', icon: School },
        { label: 'Absences', icon: ClipboardCheck },
    ];

    const averages = [
        ['6ème A', 72],
        ['5ème B', 88],
        ['4ème A', 61],
        ['3ème B', 94],
        ['2ème A', 80],
    ];

    return (
        <div className="relative">
            <div className="absolute -inset-4 -z-10 rounded-3xl bg-gradient-to-tr from-emerald-200/50 to-teal-200/50 blur-2xl" aria-hidden />

            <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl sm:p-5">
                <div className="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <span className="h-2.5 w-2.5 rounded-full bg-red-400" />
                    <span className="h-2.5 w-2.5 rounded-full bg-amber-400" />
                    <span className="h-2.5 w-2.5 rounded-full bg-emerald-400" />
                    <span className="ml-2 text-xs font-medium text-slate-400">
                        Tableau de bord
                    </span>
                </div>

                <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {tiles.map(({ label, icon: Icon }) => (
                        <div key={label} className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <Icon className="h-4 w-4 text-emerald-600" />
                            <p className="mt-2 text-xs font-medium text-slate-500">{label}</p>
                        </div>
                    ))}
                </div>

                <div className="mt-3 rounded-xl border border-slate-100 p-3">
                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-xs font-semibold text-slate-700">
                            Moyennes par classe
                        </p>
                        <span className="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                            Trimestre 1
                        </span>
                    </div>

                    <div className="space-y-2.5">
                        {averages.map(([label, width]) => (
                            <div key={label} className="flex items-center gap-2.5">
                                <span className="w-14 shrink-0 text-[10px] font-medium text-slate-400">
                                    {label}
                                </span>
                                <span className="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <span
                                        className="block h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500"
                                        style={{ width: `${width}%` }}
                                    />
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="mt-3 grid gap-2 sm:grid-cols-2">
                    <div className="flex items-center gap-2.5 rounded-xl border border-slate-100 p-3">
                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <FileText className="h-4 w-4" />
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-xs font-semibold text-slate-700">
                                Bulletin publié
                            </p>
                            <p className="text-[10px] text-slate-400">
                                6ème A · parents notifiés
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2.5 rounded-xl border border-slate-100 p-3">
                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                            <MessageSquare className="h-4 w-4" />
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-xs font-semibold text-slate-700">
                                SMS envoyés
                            </p>
                            <p className="text-[10px] text-slate-400">
                                Absences du jour
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

/* ==========================================================================
 | SECTION 2 — PROBLÈMES
 | ==========================================================================*/

const PROBLEMS = [
    {
        icon: FileText,
        title: 'Gestion manuelle des notes',
        text: "Cahiers, registres et feuilles de calcul ne se parlent plus : les erreurs de saisie et les retards s'accumulent.",
    },
    {
        icon: MessageSquare,
        title: 'Communication difficile avec les parents',
        text: "Des parents prévenus tardivement, par des messages isolés et sans historique, ce qui complique le suivi de la scolarité.",
    },
    {
        icon: ClipboardCheck,
        title: 'Suivi compliqué des présences',
        text: "L'appel se fait sur un cahier, les retards et les justifications se perdent, et les familles ne sont pas informées.",
    },
    {
        icon: BookOpen,
        title: 'Bulletins difficiles à produire',
        text: "Moyennes, rangs et appréciations sont ressaisis à la main, puis photocopiés : autant de sources d'erreur.",
    },
    {
        icon: TrendingUp,
        title: 'Manque de visibilité sur les performances',
        text: "Impossible de voir d'un coup d'œil quelles classes ou quels élèves décrochent avant la fin du trimestre.",
    },
    {
        icon: Wallet,
        title: 'Gestion financière dispersée',
        text: "Contributions, frais de scolarité et reçus sont suivis dans des cahiers séparés, sans rapprochement possible.",
    },
];

function Problems() {
    return (
        <section className="border-y border-slate-200 bg-slate-50 py-16 sm:py-20">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Le constat"
                    title="La gestion scolaire au quotidien, c'est lourd"
                    text="Six points de friction reviennent dans presque tous les établissements."
                />

                <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {PROBLEMS.map(({ icon: Icon, title, text }) => (
                        <article key={title} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <span className="flex h-11 w-11 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                <Icon className="h-5 w-5" />
                            </span>
                            <h3 className="mt-4 font-semibold text-slate-950">{title}</h3>
                            <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

/* ==========================================================================
 | SECTION 3 — SOLUTION
 | ==========================================================================*/

const SOLUTION_POINTS = [
    {
        title: 'Un espace par école',
        text: "Chaque établissement a ses classes, ses élèves, son personnel et ses données, séparés de ceux des autres écoles.",
    },
    {
        title: 'Des rôles clairs',
        text: "Le directeur, le censeur, la secrétaire, le comptable, l'enseignant et le parent ne voient que ce qui les concerne.",
    },
    {
        title: 'Une information qui circule',
        text: "Publication d'un bulletin ou signalement d'une absence : les parents concernés sont informés immédiatement.",
    },
];

const SOLUTION_HIGHLIGHTS = [
    {
        icon: LayoutDashboard,
        label: 'Tableau de bord direction',
        text: 'Vue consolidée de la vie de l’établissement.',
    },
    {
        icon: BarChart3,
        label: 'Statistiques',
        text: 'Moyennes, dispersion et évolution par classe.',
    },
    {
        icon: ShieldCheck,
        label: '2FA obligatoire',
        text: 'Comptes du personnel protégés par code TOTP.',
    },
    {
        icon: MessageSquare,
        label: 'Notifications & SMS',
        text: 'Absences, bulletins et messages aux familles.',
    },
];

function Solution() {
    return (
        <section className="py-16 sm:py-20">
            <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div>
                    <SectionHeading
                        align="left"
                        eyebrow="La solution"
                        title="Une seule plateforme, des inscriptions au bulletin"
                        text="CoriSchool réunit la gestion administrative, le suivi pédagogique et la relation avec les familles dans un espace unique, cloisonné par établissement."
                    />

                    <div className="mt-8 space-y-4">
                        {SOLUTION_POINTS.map((point) => (
                            <div key={point.title} className="flex gap-3">
                                <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <Check className="h-3.5 w-3.5" />
                                </span>
                                <div>
                                    <p className="font-medium text-slate-950">{point.title}</p>
                                    <p className="mt-1 text-sm leading-6 text-slate-600">{point.text}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    {SOLUTION_HIGHLIGHTS.map(({ icon: Icon, label, text }) => (
                        <article key={label} className="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
                            <Icon className="h-5 w-5 text-emerald-700" />
                            <h3 className="mt-3 font-semibold text-slate-950">{label}</h3>
                            <p className="mt-1 text-sm leading-6 text-slate-600">{text}</p>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

/* ==========================================================================
 | SECTION 4 — FONCTIONNALITÉS
 | ==========================================================================*/

const FEATURES = [
    { icon: FileText, title: 'Notes & bulletins', text: 'Évaluations, moyennes, rangs et bulletins générés puis publiés.' },
    { icon: ClipboardCheck, title: 'Présences', text: 'Absences, retards et justifications saisis par l’administration.' },
    { icon: CalendarDays, title: 'Emploi du temps', text: 'Vue hebdomadaire par classe, par enseignant ou par élève.' },
    { icon: Users, title: 'Portail parents', text: 'Notes, bulletins, assiduité et emploi du temps de chaque enfant.' },
    { icon: MessageSquare, title: 'Notifications & SMS', text: "Alertes d'absence, publication de bulletin, messages de l'école." },
    { icon: UserCog, title: 'Gestion du personnel', text: "Comptes, rôles et accès des enseignants et de l'administration." },
    { icon: BarChart3, title: 'Statistiques', text: 'Performance par classe, par matière et par élève.' },
    { icon: Wallet, title: 'Gestion financière', text: 'Frais scolaires, contributions et services par élève.' },
    { icon: Receipt, title: 'Paiements', text: 'Enregistrement des règlements et édition de reçus.' },
];

function Features() {
    return (
        <section id="fonctionnalites" className="scroll-mt-20 border-y border-slate-200 bg-slate-50 py-16 sm:py-20">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Fonctionnalités"
                    title="Tout ce qu'un établissement gère, au même endroit"
                    text="Des notes au portail parent, sans changer d'outil."
                />

                <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {FEATURES.map(({ icon: Icon, title, text }) => (
                        <article key={title} className="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md">
                            <span className="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-700 group-hover:text-white">
                                <Icon className="h-5 w-5" />
                            </span>
                            <h3 className="mt-4 font-semibold text-slate-950">{title}</h3>
                            <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

/* ==========================================================================
 | SECTION 5 — COMMENT ÇA MARCHE
 | ==========================================================================*/

const STEPS = [
    { title: 'Créez votre école', text: "Nom, sigle, adresse et contacts : l'établissement et son espace de travail sont créés en quelques secondes." },
    { title: 'Configurez classes et personnel', text: "Années scolaires, classes, matières, puis les enseignants avec leurs accès." },
    { title: 'Ajoutez vos élèves', text: "Matricule, classe, photo et responsables : le dossier élève se constitue progressivement." },
    { title: 'Gérez les activités scolaires', text: 'Emploi du temps, évaluations, notes, moyennes et présences au quotidien.' },
    { title: 'Communiquez avec les parents', text: "Portail parent, notifications et SMS : les familles suivent leur enfant." },
    { title: 'Pilotez votre établissement', text: 'Bulletins, statistiques et suivi des performances pour décider.' },
];

function HowItWorks() {
    return (
        <section id="fonctionnement" className="scroll-mt-20 py-16 sm:py-20">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Comment ça marche"
                    title="Six étapes pour être opérationnel"
                    text="L'établissement avance à son rythme : chaque étape peut être complétée plus tard."
                />

                <ol className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {STEPS.map((step, index) => (
                        <li key={step.title} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-sm font-bold text-white">
                                {index + 1}
                            </span>
                            <h3 className="mt-4 font-semibold text-slate-950">{step.title}</h3>
                            <p className="mt-2 text-sm leading-6 text-slate-600">{step.text}</p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}

/* ==========================================================================
 | SECTION 6 — TARIFS
 | ==========================================================================*/

function Pricing({ plans, smsNote }) {
    return (
        <section id="tarifs" className="scroll-mt-20 border-y border-slate-200 bg-slate-50 py-16 sm:py-20">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Tarifs"
                    title="Un tarif par élève et par an"
                    text="Choisissez l'offre correspondant à la taille et aux besoins de votre établissement."
                />

                <div className="mt-12 grid items-start gap-6 lg:grid-cols-3">
                    {plans.map((plan) => (
                        <div
                            key={plan.key}
                            className={cn(
                                'relative flex flex-col rounded-2xl border bg-white p-6 shadow-sm',
                                plan.featured
                                    ? 'border-emerald-600 ring-2 ring-emerald-600/20'
                                    : 'border-slate-200',
                            )}
                        >
                            {plan.featured && (
                                <span className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-emerald-700 px-3 py-1 text-xs font-semibold text-white">
                                    Le plus choisi
                                </span>
                            )}

                            <h3 className="text-lg font-bold text-slate-950">{plan.name}</h3>
                            <p className="mt-2 text-sm leading-6 text-slate-600">{plan.tagline}</p>

                            <p className="mt-5 flex flex-wrap items-baseline gap-x-2">
                                <span className="text-3xl font-bold tracking-tight text-slate-950">
                                    {plan.price}
                                </span>
                                <span className="text-sm text-slate-500">{plan.period}</span>
                            </p>

                            {plan.featured ? (
                                <BrandButton href={route('school.register')} className="mt-6 w-full">
                                    Créer mon école
                                </BrandButton>
                            ) : (
                                <OutlineButton href={route('school.register')} className="mt-6 w-full">
                                    Créer mon école
                                </OutlineButton>
                            )}

                            <ul className="mt-6 space-y-2.5 border-t border-slate-100 pt-6">
                                {plan.features.map((feature) => (
                                    <li key={feature} className="flex items-start gap-2.5 text-sm leading-6 text-slate-600">
                                        <Check className="mt-1 h-4 w-4 shrink-0 text-emerald-600" />
                                        <span>{feature}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                {smsNote && (
                    <p className="mt-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                        <span>{smsNote}</span>
                    </p>
                )}
            </div>
        </section>
    );
}

/* ==========================================================================
 | SECTION 7 — QUESTIONS
 | ==========================================================================*/

function Faq({ items, openFaq, setOpenFaq }) {
    return (
        <section id="faq" className="scroll-mt-20 py-16 sm:py-20">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Questions fréquentes"
                    title="Ce que les écoles demandent le plus"
                />

                <div className="mt-10 divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    {items.map((item, index) => {
                        const isOpen = openFaq === index;

                        return (
                            <div key={item.question}>
                                <button
                                    type="button"
                                    onClick={() => setOpenFaq(isOpen ? null : index)}
                                    className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                                    aria-expanded={isOpen}
                                >
                                    <span className="font-medium text-slate-950">{item.question}</span>
                                    <ChevronDown
                                        className={cn(
                                            'h-5 w-5 shrink-0 text-slate-400 transition',
                                            isOpen && 'rotate-180',
                                        )}
                                    />
                                </button>

                                {isOpen && (
                                    <p className="px-5 pb-4 text-sm leading-6 text-slate-600">
                                        {item.answer}
                                    </p>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

/* ==========================================================================
 | SECTION 8 — APPEL FINAL
 | ==========================================================================*/

function FinalCta() {
    return (
        <section className="bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-700 py-16 text-white sm:py-20">
            <div className="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                    Prêt à connecter votre école ?
                </h2>

                <p className="mx-auto mt-4 max-w-xl text-base leading-7 text-emerald-50/90">
                    Créez votre établissement, ajoutez vos classes et
                    transmettez le premier bulletin à vos familles.
                </p>

                <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <Link
                        href={route('school.register')}
                        className="inline-flex min-h-12 items-center justify-center gap-2 rounded-md bg-white px-6 text-base font-semibold text-emerald-800 transition hover:bg-emerald-50"
                    >
                        Créer mon école
                    </Link>

                    <Link
                        href={route('login')}
                        className="inline-flex min-h-12 items-center justify-center gap-2 rounded-md border border-white/40 px-6 text-base font-semibold text-white transition hover:bg-white/10"
                    >
                        J'ai déjà un compte
                    </Link>
                </div>
            </div>
        </section>
    );
}

/* ==========================================================================
 | FOOTER
 | ==========================================================================*/

function SiteFooter() {
    return (
        <footer className="border-t border-slate-200 bg-white py-10">
            <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 text-center sm:px-6 lg:flex-row lg:px-8">
                <Link href="/" className="flex items-center gap-2.5">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-700 text-white">
                        <GraduationCap className="h-4 w-4" />
                    </span>
                    <span className="text-sm font-semibold text-slate-950">CoriSchool</span>
                </Link>

                <nav className="flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-slate-500" aria-label="Navigation de pied de page">
                    {navigation.map(([label, href]) => (
                        <a key={href} href={href} className="transition hover:text-slate-950">{label}</a>
                    ))}
                </nav>

                <p className="text-xs text-slate-400">
                    © {new Date().getFullYear()} Coriyase Technologies
                </p>
            </div>
        </footer>
    );
}

/* ==========================================================================
 | COMPOSANTS LOCAUX
 | ==========================================================================*/

/**
 * Bouton de marque CoriSchool.
 *
 * Le composant `Button` global applique `var(--color-primary)`, qui correspond à
 * l'identité de l'établissement et non à celle de la plateforme : sur cette page
 * la marque est donc portée par des classes emerald explicites.
 */
function BrandButton({ href, children, large = false, className }) {
    return (
        <Link
            href={href}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-md bg-emerald-700 font-semibold text-white transition hover:bg-emerald-800',
                large ? 'min-h-12 px-6 text-base' : 'min-h-11 px-4 text-sm',
                className,
            )}
        >
            {children}
        </Link>
    );
}

function OutlineButton({ href, children, large = false, className }) {
    return (
        <Link
            href={href}
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-md border border-slate-300 bg-white font-semibold text-slate-700 transition hover:bg-slate-50',
                large ? 'min-h-12 px-6 text-base' : 'min-h-11 px-4 text-sm',
                className,
            )}
        >
            {children}
        </Link>
    );
}

function SectionHeading({ eyebrow, title, text, align = 'center' }) {
    return (
        <div className={cn('max-w-2xl', align === 'center' && 'mx-auto text-center')}>
            {eyebrow && (
                <p className="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                    {eyebrow}
                </p>
            )}

            <h2 className="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                {title}
            </h2>

            {text && <p className="mt-3 text-base leading-7 text-slate-600">{text}</p>}
        </div>
    );
}
