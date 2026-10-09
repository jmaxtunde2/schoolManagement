import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    AlertCircle,
    ArrowRight,
    BarChart3,
    Bell,
    BookOpen,
    CalendarDays,
    Check,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    ClipboardCheck,
    Clock3,
    GraduationCap,
    LayoutDashboard,
    Menu,
    MessageSquare,
    MoreHorizontal,
    Play,
    School,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    UserCheck,
    Users,
    Wallet,
    X,
} from 'lucide-react';

function cn(...classes) {
    return classes
        .flat(Infinity)
        .filter(Boolean)
        .join(' ');
}

/*
|--------------------------------------------------------------------------
| CoriSchool Landing Page
|--------------------------------------------------------------------------
|
| Props expected from Laravel:
|
| plans   => pricing plans
| smsNote => optional SMS information
| faq     => FAQ items
|
| Routes:
|   route('login')
|   route('demo.request')
|
|--------------------------------------------------------------------------
*/

/* -------------------------------------------------------------------------- */
/* Animation presets                                                          */
/* -------------------------------------------------------------------------- */

const fadeUp = {
    hidden: {
        opacity: 0,
        y: 35,
    },
    visible: {
        opacity: 1,
        y: 0,
        transition: {
            duration: 0.7,
            ease: [0.22, 1, 0.36, 1],
        },
    },
};

const fadeIn = {
    hidden: {
        opacity: 0,
    },
    visible: {
        opacity: 1,
        transition: {
            duration: 0.7,
        },
    },
};

const scaleIn = {
    hidden: {
        opacity: 0,
        scale: 0.94,
    },
    visible: {
        opacity: 1,
        scale: 1,
        transition: {
            duration: 0.7,
            ease: [0.22, 1, 0.36, 1],
        },
    },
};

const stagger = {
    hidden: {},
    visible: {
        transition: {
            staggerChildren: 0.09,
        },
    },
};

const viewport = {
    once: true,
    amount: 0.15,
};

/* -------------------------------------------------------------------------- */
/* Static data                                                                */
/* -------------------------------------------------------------------------- */

const problems = [
    {
        icon: ClipboardCheck,
        title: 'Gestion administrative dispersée',
        description:
            'Notes, présences, bulletins, paiements et informations scolaires sont souvent répartis entre plusieurs outils.',
    },
    {
        icon: Clock3,
        title: 'Trop de tâches manuelles',
        description:
            'La saisie répétitive et les traitements manuels font perdre du temps aux équipes pédagogiques et administratives.',
    },
    {
        icon: MessageSquare,
        title: 'Communication insuffisante',
        description:
            'Les parents ont besoin d’un accès simple et rapide aux informations importantes concernant leurs enfants.',
    },
];

const features = [
    {
        id: 'pilotage',
        icon: LayoutDashboard,
        title: 'Pilotage de l’établissement',
        description:
            'Une vue centralisée pour suivre les activités essentielles de votre école.',
        points: [
            'Tableau de bord administratif',
            'Gestion des utilisateurs et rôles',
            'Suivi des classes et enseignants',
            'Indicateurs pédagogiques',
        ],
    },
    {
        id: 'notes',
        icon: BarChart3,
        title: 'Notes & évaluations',
        description:
            'Structurez le cycle complet de l’évaluation, de la saisie à la validation.',
        points: [
            'Évaluations et devoirs',
            'Saisie des notes',
            'Validation sécurisée',
            'Moyennes et classements',
        ],
    },
    {
        id: 'parents',
        icon: Users,
        title: 'Espace parents',
        description:
            'Offrez aux familles un accès moderne aux informations scolaires.',
        points: [
            'Suivi des résultats',
            'Notifications',
            'Présences et retards',
            'Informations scolaires',
        ],
    },
    {
        id: 'finances',
        icon: Wallet,
        title: 'Frais scolaires',
        description:
            'Centralisez le suivi des frais, paiements et reçus de votre établissement.',
        points: [
            'Suivi des frais',
            'Enregistrement des paiements',
            'Reçus',
            'Historique financier',
        ],
    },
];

const audiences = [
    {
        icon: School,
        title: 'Direction',
        description:
            'Pilotez votre établissement avec une vision claire des opérations et des performances.',
    },
    {
        icon: GraduationCap,
        title: 'Enseignants',
        description:
            'Gérez vos classes, évaluations, notes et activités pédagogiques plus simplement.',
    },
    {
        icon: Users,
        title: 'Parents',
        description:
            'Permettez aux parents de suivre facilement la scolarité de leurs enfants.',
    },
    {
        icon: UserCheck,
        title: 'Élèves',
        description:
            'Facilitez l’accès aux résultats, emplois du temps et informations importantes.',
    },
];

const workflow = [
    {
        number: '01',
        title: 'Créez votre établissement',
        description:
            'Configurez votre école et personnalisez son environnement numérique.',
    },
    {
        number: '02',
        title: 'Ajoutez votre équipe',
        description:
            'Invitez administrateurs, enseignants et autres membres du personnel.',
    },
    {
        number: '03',
        title: 'Gérez la vie scolaire',
        description:
            'Classes, matières, évaluations, notes, présences et documents.',
    },
    {
        number: '04',
        title: 'Connectez les familles',
        description:
            'Les parents disposent d’un espace pour suivre la scolarité de leurs enfants.',
    },
];

const defaultFaq = [
    {
        question: 'CoriSchool est-il adapté aux écoles africaines ?',
        answer:
            'Oui. CoriSchool est pensé pour les réalités des établissements scolaires africains : gestion académique, communication avec les parents, suivi des paiements, notifications et administration centralisée.',
    },
    {
        question: 'Puis-je gérer plusieurs écoles ?',
        answer:
            'Oui. L’architecture de CoriSchool est conçue pour supporter plusieurs établissements avec une séparation logique des données.',
    },
    {
        question: 'Les parents peuvent-ils accéder aux résultats ?',
        answer:
            'Oui. Un espace parent permet de consulter les informations scolaires disponibles selon les fonctionnalités activées par l’établissement.',
    },
    {
        question: 'Est-il possible d’utiliser les SMS ?',
        answer:
            'Oui. CoriSchool peut être connecté à un fournisseur SMS afin d’envoyer certaines notifications importantes. Les SMS peuvent être réservés aux événements qui nécessitent réellement une notification mobile.',
    },
    {
        question: 'Les données de l’école sont-elles protégées ?',
        answer:
            'La plateforme applique une séparation logique des données entre établissements et prévoit des mécanismes de contrôle d’accès et d’authentification renforcée.',
    },
];

/* -------------------------------------------------------------------------- */
/* Helpers                                                                    */
/* -------------------------------------------------------------------------- */

function SectionHeading({
    eyebrow,
    title,
    description,
    centered = true,
}) {
    return (
        <motion.div
            variants={fadeUp}
            initial="hidden"
            whileInView="visible"
            viewport={viewport}
            className={cn(
                'max-w-3xl',
                centered && 'mx-auto text-center',
            )}
        >
            <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                <Sparkles className="h-3.5 w-3.5" />
                {eyebrow}
            </div>

            <h2 className="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                {title}
            </h2>

            {description && (
                <p className="mt-5 text-base leading-7 text-slate-600 sm:text-lg">
                    {description}
                </p>
            )}
        </motion.div>
    );
}

/* -------------------------------------------------------------------------- */
/* Dashboard Mockup                                                           */
/* -------------------------------------------------------------------------- */

function DashboardMockup() {
    return (
        <div className="relative mx-auto w-full max-w-5xl">
            {/* Glow */}
            <div className="absolute -inset-8 rounded-[3rem] bg-emerald-400/10 blur-3xl" />

            {/* Floating notification */}
            <motion.div
                initial={{ opacity: 0, x: 25, y: 10 }}
                animate={{ opacity: 1, x: 0, y: 0 }}
                transition={{ delay: 1, duration: 0.7 }}
                className="absolute -right-3 top-20 z-30 hidden w-56 rounded-2xl border border-white/80 bg-white/95 p-4 shadow-2xl backdrop-blur sm:block lg:-right-12"
            >
                <div className="flex items-start gap-3">
                    <div className="rounded-xl bg-emerald-100 p-2 text-emerald-700">
                        <Bell className="h-4 w-4" />
                    </div>

                    <div>
                        <p className="text-xs font-bold text-slate-900">
                            Nouvelle notification
                        </p>
                        <p className="mt-1 text-[11px] leading-4 text-slate-500">
                            Les résultats du trimestre ont été validés.
                        </p>
                    </div>
                </div>
            </motion.div>

            {/* Floating performance card */}
            <motion.div
                initial={{ opacity: 0, x: -20, y: 20 }}
                animate={{ opacity: 1, x: 0, y: 0 }}
                transition={{ delay: 1.2, duration: 0.7 }}
                className="absolute -bottom-8 -left-4 z-30 hidden w-56 rounded-2xl border border-white/80 bg-white/95 p-4 shadow-2xl backdrop-blur sm:block lg:-left-12"
            >
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-[11px] font-semibold text-slate-500">
                            Performance
                        </p>
                        <p className="mt-1 text-xl font-black text-slate-950">
                            78%
                        </p>
                    </div>

                    <div className="rounded-xl bg-teal-50 p-2 text-teal-600">
                        <TrendingUp className="h-5 w-5" />
                    </div>
                </div>

                <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                    <motion.div
                        initial={{ width: 0 }}
                        animate={{ width: '78%' }}
                        transition={{
                            delay: 1.5,
                            duration: 1.2,
                            ease: 'easeOut',
                        }}
                        className="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500"
                    />
                </div>
            </motion.div>

            {/* Browser */}
            <motion.div
                variants={scaleIn}
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, amount: 0.15 }}
                className="relative overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_30px_100px_-25px_rgba(15,23,42,0.3)]"
            >
                {/* Browser top */}
                <div className="flex h-11 items-center gap-2 border-b border-slate-100 bg-slate-50 px-4">
                    <span className="h-2.5 w-2.5 rounded-full bg-slate-300" />
                    <span className="h-2.5 w-2.5 rounded-full bg-slate-300" />
                    <span className="h-2.5 w-2.5 rounded-full bg-slate-300" />

                    <div className="ml-4 hidden h-6 flex-1 rounded-lg bg-white px-3 text-[9px] leading-6 text-slate-400 sm:block">
                        app.corischool.com/dashboard
                    </div>
                </div>

                <div className="flex min-h-[430px] bg-slate-50">
                    {/* Sidebar */}
                    <aside className="hidden w-52 border-r border-slate-100 bg-white p-4 md:block">
                        <div className="mb-8 flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
                                <School className="h-4 w-4" />
                            </div>
                            <span className="text-sm font-black text-slate-900">
                                CoriSchool
                            </span>
                        </div>

                        <p className="mb-2 px-2 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                            Principal
                        </p>

                        <div className="space-y-1">
                            {[
                                ['Tableau de bord', LayoutDashboard],
                                ['Élèves', Users],
                                ['Classes', School],
                                ['Notes', BarChart3],
                                ['Présences', CheckCircle2],
                            ].map(([label, Icon], index) => (
                                <div
                                    key={label}
                                    className={cn(
                                        'flex items-center gap-2 rounded-xl px-3 py-2.5 text-[11px] font-semibold',
                                        index === 0
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'text-slate-500',
                                    )}
                                >
                                    <Icon className="h-3.5 w-3.5" />
                                    {label}
                                </div>
                            ))}
                        </div>

                        <p className="mb-2 mt-7 px-2 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                            Gestion
                        </p>

                        <div className="space-y-1">
                            {[
                                ['Paiements', Wallet],
                                ['Communication', MessageSquare],
                                ['Rapports', BarChart3],
                            ].map(([label, Icon]) => (
                                <div
                                    key={label}
                                    className="flex items-center gap-2 rounded-xl px-3 py-2.5 text-[11px] font-semibold text-slate-500"
                                >
                                    <Icon className="h-3.5 w-3.5" />
                                    {label}
                                </div>
                            ))}
                        </div>
                    </aside>

                    {/* Main dashboard */}
                    <main className="min-w-0 flex-1 p-4 sm:p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[10px] font-medium text-slate-400">
                                    Bonjour, Administration
                                </p>
                                <h3 className="mt-1 text-lg font-black text-slate-950 sm:text-2xl">
                                    Tableau de bord
                                </h3>
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="hidden rounded-xl border border-slate-200 bg-white px-3 py-2 text-[10px] font-semibold text-slate-500 sm:block">
                                    Année scolaire 2026–2027
                                </div>

                                <div className="relative rounded-xl border border-slate-200 bg-white p-2.5">
                                    <Bell className="h-4 w-4 text-slate-500" />
                                    <span className="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                </div>
                            </div>
                        </div>

                        {/* Stat cards */}
                        <div className="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                            {[
                                {
                                    label: 'Élèves',
                                    value: '1 248',
                                    icon: Users,
                                },
                                {
                                    label: 'Enseignants',
                                    value: '64',
                                    icon: GraduationCap,
                                },
                                {
                                    label: 'Classes',
                                    value: '32',
                                    icon: School,
                                },
                                {
                                    label: 'Présence',
                                    value: '94%',
                                    icon: CheckCircle2,
                                },
                            ].map(({ label, value, icon: Icon }, index) => (
                                <motion.div
                                    key={label}
                                    initial={{ opacity: 0, y: 12 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{
                                        delay: 0.3 + index * 0.08,
                                    }}
                                    className="rounded-2xl border border-slate-100 bg-white p-3 shadow-sm"
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="rounded-lg bg-emerald-50 p-2 text-emerald-600">
                                            <Icon className="h-3.5 w-3.5" />
                                        </div>
                                        <MoreHorizontal className="h-4 w-4 text-slate-300" />
                                    </div>

                                    <p className="mt-3 text-[9px] font-medium text-slate-400">
                                        {label}
                                    </p>

                                    <p className="mt-1 text-base font-black text-slate-950 sm:text-lg">
                                        {value}
                                    </p>
                                </motion.div>
                            ))}
                        </div>

                        {/* Lower content */}
                        <div className="mt-4 grid gap-4 lg:grid-cols-3">
                            {/* Chart */}
                            <div className="rounded-2xl border border-slate-100 bg-white p-4 lg:col-span-2">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-xs font-bold text-slate-900">
                                            Évolution des résultats
                                        </p>
                                        <p className="mt-1 text-[9px] text-slate-400">
                                            Performance moyenne
                                        </p>
                                    </div>

                                    <TrendingUp className="h-4 w-4 text-emerald-500" />
                                </div>

                                <div className="mt-6 flex h-36 items-end gap-2">
                                    {[42, 55, 49, 63, 58, 72, 68, 79, 76, 84, 81, 88].map(
                                        (height, index) => (
                                            <div
                                                key={index}
                                                className="flex h-full flex-1 items-end"
                                            >
                                                <motion.div
                                                    initial={{ height: 0 }}
                                                    animate={{
                                                        height: `${height}%`,
                                                    }}
                                                    transition={{
                                                        delay: 0.5 + index * 0.04,
                                                        duration: 0.7,
                                                    }}
                                                    className="w-full rounded-t-md bg-gradient-to-t from-emerald-600 to-teal-400"
                                                />
                                            </div>
                                        ),
                                    )}
                                </div>

                                <div className="mt-2 flex justify-between text-[8px] text-slate-400">
                                    <span>Sep</span>
                                    <span>Oct</span>
                                    <span>Nov</span>
                                    <span>Déc</span>
                                    <span>Jan</span>
                                    <span>Fév</span>
                                </div>
                            </div>

                            {/* Recent activity */}
                            <div className="rounded-2xl border border-slate-100 bg-white p-4">
                                <div className="flex items-center justify-between">
                                    <p className="text-xs font-bold text-slate-900">
                                        Activité récente
                                    </p>
                                    <ChevronRight className="h-4 w-4 text-slate-300" />
                                </div>

                                <div className="mt-4 space-y-4">
                                    {[
                                        {
                                            icon: CheckCircle2,
                                            title: 'Notes validées',
                                            text: '3e A · Mathématiques',
                                        },
                                        {
                                            icon: Users,
                                            title: 'Nouvel élève',
                                            text: 'Inscription enregistrée',
                                        },
                                        {
                                            icon: Wallet,
                                            title: 'Paiement reçu',
                                            text: 'Frais de scolarité',
                                        },
                                    ].map(({ icon: Icon, title, text: activity }) => (
                                        <div
                                            key={title}
                                            className="flex items-start gap-3"
                                        >
                                            <div className="rounded-lg bg-slate-50 p-2">
                                                <Icon className="h-3.5 w-3.5 text-emerald-600" />
                                            </div>

                                            <div className="min-w-0">
                                                <p className="truncate text-[10px] font-bold text-slate-800">
                                                    {title}
                                                </p>
                                                <p className="mt-0.5 truncate text-[9px] text-slate-400">
                                                    {activity}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
            </motion.div>
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* Site Header                                                                */
/* -------------------------------------------------------------------------- */

function SiteHeader() {
    const [mobileOpen, setMobileOpen] = useState(false);

    const links = [
        ['Fonctionnalités', '#fonctionnalites'],
        ['Solution', '#solution'],
        ['Tarifs', '#tarifs'],
        ['FAQ', '#faq'],
    ];

    return (
        <header className="fixed inset-x-0 top-0 z-50">
            <div className="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                <div className="rounded-2xl border border-white/70 bg-white/90 px-4 shadow-lg shadow-slate-900/5 backdrop-blur-xl">
                    <div className="flex h-16 items-center justify-between">
                        <a
                            href="#accueil"
                            className="flex items-center gap-2.5"
                        >
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/20">
                                <School className="h-5 w-5" />
                            </div>

                            <div>
                                <div className="text-lg font-black tracking-tight text-slate-950">
                                    Cori<span className="text-emerald-600">School</span>
                                </div>
                                <div className="hidden text-[8px] font-bold uppercase tracking-[0.2em] text-slate-400 sm:block">
                                    Plateforme scolaire numérique
                                </div>
                            </div>
                        </a>

                        <nav className="hidden items-center gap-7 lg:flex">
                            {links.map(([label, href]) => (
                                <a
                                    key={label}
                                    href={href}
                                    className="text-sm font-semibold text-slate-600 transition hover:text-emerald-600"
                                >
                                    {label}
                                </a>
                            ))}
                        </nav>

                        <div className="hidden items-center gap-3 sm:flex">
                            <Link
                                href={route('login')}
                                className="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                            >
                                Se connecter
                            </Link>

                            <Link
                                href={route('demo.request')}
                                className="group inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-slate-950/10 transition hover:bg-emerald-600"
                            >
                                Demander une démonstration
                                <ArrowRight className="h-4 w-4 transition group-hover:translate-x-0.5" />
                            </Link>
                        </div>

                        <button
                            type="button"
                            onClick={() => setMobileOpen(!mobileOpen)}
                            className="rounded-xl p-2.5 text-slate-700 hover:bg-slate-100 lg:hidden"
                            aria-label="Menu"
                        >
                            {mobileOpen ? (
                                <X className="h-5 w-5" />
                            ) : (
                                <Menu className="h-5 w-5" />
                            )}
                        </button>
                    </div>

                    <AnimatePresence>
                        {mobileOpen && (
                            <motion.div
                                initial={{ height: 0, opacity: 0 }}
                                animate={{ height: 'auto', opacity: 1 }}
                                exit={{ height: 0, opacity: 0 }}
                                className="overflow-hidden lg:hidden"
                            >
                                <div className="border-t border-slate-100 py-4">
                                    <nav className="flex flex-col gap-1">
                                        {links.map(([label, href]) => (
                                            <a
                                                key={label}
                                                href={href}
                                                onClick={() =>
                                                    setMobileOpen(false)
                                                }
                                                className="rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-700"
                                            >
                                                {label}
                                            </a>
                                        ))}
                                    </nav>

                                    <div className="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                                        <Link
                                            href={route('login')}
                                            className="rounded-xl border border-slate-200 px-3 py-3 text-center text-sm font-bold text-slate-700"
                                        >
                                            Connexion
                                        </Link>

                                        <Link
                                            href={route('demo.request')}
                                            className="rounded-xl bg-emerald-600 px-3 py-3 text-center text-sm font-bold text-white"
                                        >
                                            Demander une démo
                                        </Link>
                                    </div>
                                </div>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>
            </div>
        </header>
    );
}

/* -------------------------------------------------------------------------- */
/* Hero                                                                       */
/* -------------------------------------------------------------------------- */

function Hero() {
    return (
        <section
            id="accueil"
            className="relative overflow-hidden bg-slate-950 pt-32"
        >
            {/* Background */}
            <div className="absolute inset-0">
                <div className="absolute left-1/2 top-0 h-[600px] w-[900px] -translate-x-1/2 rounded-full bg-emerald-500/10 blur-[120px]" />
                <div className="absolute right-0 top-1/3 h-[400px] w-[400px] rounded-full bg-teal-500/10 blur-[100px]" />
            </div>

            <div className="relative mx-auto max-w-7xl px-4 pb-24 pt-12 sm:px-6 lg:px-8 lg:pb-32 lg:pt-20">
                <div className="grid items-center gap-14 lg:grid-cols-[0.9fr_1.1fr]">
                    <motion.div
                        variants={stagger}
                        initial="hidden"
                        animate="visible"
                    >
                        <motion.div
                            variants={fadeUp}
                            className="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2 text-xs font-bold text-emerald-300"
                        >
                            <span className="relative flex h-2 w-2">
                                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                                <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-400" />
                            </span>
                            La nouvelle génération de gestion scolaire
                        </motion.div>

                        <motion.h1
                            variants={fadeUp}
                            className="mt-7 max-w-3xl text-4xl font-black leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl xl:text-7xl"
                        >
                            L’excellence commence par une{' '}
                            <span className="bg-gradient-to-r from-emerald-400 via-teal-300 to-emerald-400 bg-clip-text text-transparent">
                                école connectée.
                            </span>
                        </motion.h1>

                        <motion.p
                            variants={fadeUp}
                            className="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg"
                        >
                            CoriSchool centralise la gestion académique,
                            administrative et financière de votre établissement
                            dans une plateforme moderne, simple et sécurisée.
                        </motion.p>

                        <motion.div
                            variants={fadeUp}
                            className="mt-8 flex flex-col gap-3 sm:flex-row"
                        >
                            <Link
                                href={route('demo.request')}
                                className="group inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-6 py-4 text-sm font-black text-white shadow-xl shadow-emerald-500/20 transition hover:bg-emerald-400"
                            >
                                Demander une démonstration
                                <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />
                            </Link>

                            <a
                                href="#fonctionnalites"
                                className="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-6 py-4 text-sm font-bold text-white backdrop-blur transition hover:bg-white/10"
                            >
                                <Play className="h-4 w-4 fill-current" />
                                Découvrir CoriSchool
                            </a>
                        </motion.div>

                        <motion.div
                            variants={fadeUp}
                            className="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 text-xs font-medium text-slate-400"
                        >
                            <span className="flex items-center gap-2">
                                <CheckCircle2 className="h-4 w-4 text-emerald-400" />
                                Gestion centralisée
                            </span>

                            <span className="flex items-center gap-2">
                                <CheckCircle2 className="h-4 w-4 text-emerald-400" />
                                Espace parents
                            </span>

                            <span className="flex items-center gap-2">
                                <CheckCircle2 className="h-4 w-4 text-emerald-400" />
                                Sécurité renforcée
                            </span>
                        </motion.div>
                    </motion.div>

                    {/* Hero visual */}
                    <motion.div
                        initial={{ opacity: 0, y: 45 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{
                            delay: 0.35,
                            duration: 0.9,
                            ease: [0.22, 1, 0.36, 1],
                        }}
                        className="relative"
                    >
                        <div className="absolute -inset-10 rounded-full bg-emerald-500/10 blur-3xl" />

                        <div className="relative">
                            <DashboardMockup />
                        </div>
                    </motion.div>
                </div>
            </div>

            {/* Bottom transition */}
            <div className="relative h-16 bg-gradient-to-b from-slate-950 to-white" />
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Trust strip                                                                */
/* -------------------------------------------------------------------------- */

function TrustStrip() {
    return (
        <section className="border-b border-slate-100 bg-white">
            <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 px-4 py-7 sm:px-6 md:flex-row lg:px-8">
                <div className="text-center md:text-left">
                    <p className="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">
                        Une plateforme pensée pour
                    </p>
                    <p className="mt-1 text-sm font-bold text-slate-700">
                        toute la communauté scolaire
                    </p>
                </div>

                <div className="flex flex-wrap justify-center gap-3">
                    {[
                        ['Direction', School],
                        ['Enseignants', GraduationCap],
                        ['Parents', Users],
                        ['Élèves', BookOpen],
                    ].map(([label, Icon]) => (
                        <div
                            key={label}
                            className="flex items-center gap-2 rounded-full border border-slate-100 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-600"
                        >
                            <Icon className="h-3.5 w-3.5 text-emerald-600" />
                            {label}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Problems                                                                   */
/* -------------------------------------------------------------------------- */

function Problems() {
    return (
        <section className="bg-white py-24 sm:py-28">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Le constat"
                    title="Votre école mérite mieux que des processus dispersés."
                    description="La gestion d’un établissement devient rapidement complexe lorsque les informations, les personnes et les opérations ne sont pas réunies dans un même environnement."
                />

                <motion.div
                    variants={stagger}
                    initial="hidden"
                    whileInView="visible"
                    viewport={viewport}
                    className="mt-14 grid gap-5 md:grid-cols-3"
                >
                    {problems.map(
                        ({ icon: Icon, title, description }, index) => (
                            <motion.div
                                key={title}
                                variants={fadeUp}
                                className="group rounded-3xl border border-slate-100 bg-slate-50/70 p-7 transition duration-300 hover:-translate-y-1 hover:border-emerald-100 hover:bg-white hover:shadow-xl hover:shadow-slate-900/5"
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-700 shadow-sm ring-1 ring-slate-100 transition group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                        <Icon className="h-5 w-5" />
                                    </div>

                                    <span className="text-xs font-black text-slate-200">
                                        0{index + 1}
                                    </span>
                                </div>

                                <h3 className="mt-7 text-lg font-black text-slate-950">
                                    {title}
                                </h3>

                                <p className="mt-3 text-sm leading-6 text-slate-500">
                                    {description}
                                </p>
                            </motion.div>
                        ),
                    )}
                </motion.div>
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Solution                                                                   */
/* -------------------------------------------------------------------------- */

function Solution() {
    return (
        <section
            id="solution"
            className="overflow-hidden bg-slate-50 py-24 sm:py-28"
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid items-center gap-14 lg:grid-cols-2">
                    <motion.div
                        variants={fadeUp}
                        initial="hidden"
                        whileInView="visible"
                        viewport={viewport}
                    >
                        <div className="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-xs font-bold uppercase tracking-wider text-emerald-700">
                            <Sparkles className="h-3.5 w-3.5" />
                            La solution
                        </div>

                        <h2 className="mt-5 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
                            Une seule plateforme pour toute votre école.
                        </h2>

                        <p className="mt-5 text-base leading-7 text-slate-600">
                            CoriSchool réunit les principales opérations de
                            votre établissement afin de créer une expérience
                            plus fluide pour l’administration, les enseignants,
                            les parents et les élèves.
                        </p>

                        <div className="mt-8 space-y-4">
                            {[
                                'Une source unique pour vos données scolaires',
                                'Des rôles et permissions adaptés à chaque utilisateur',
                                'Des processus de validation plus sûrs',
                                'Une communication plus directe avec les familles',
                            ].map((item) => (
                                <div
                                    key={item}
                                    className="flex items-start gap-3"
                                >
                                    <div className="mt-0.5 rounded-full bg-emerald-100 p-1 text-emerald-600">
                                        <Check className="h-3.5 w-3.5" />
                                    </div>
                                    <span className="text-sm font-semibold text-slate-700">
                                        {item}
                                    </span>
                                </div>
                            ))}
                        </div>

                        <Link
                            href={route('demo.request')}
                            className="group mt-9 inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-3.5 text-sm font-bold text-white transition hover:bg-emerald-600"
                        >
                            Commencer maintenant
                            <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />
                        </Link>
                    </motion.div>

                    <motion.div
                        variants={scaleIn}
                        initial="hidden"
                        whileInView="visible"
                        viewport={viewport}
                        className="relative"
                    >
                        <div className="absolute -inset-8 rounded-full bg-emerald-200/40 blur-3xl" />

                        <div className="relative rounded-[2rem] border border-white bg-white p-4 shadow-2xl shadow-slate-900/10 sm:p-6">
                            <div className="rounded-3xl bg-gradient-to-br from-emerald-600 to-teal-700 p-6 text-white sm:p-8">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                                            <School className="h-5 w-5" />
                                        </div>

                                        <p className="mt-6 text-sm font-semibold text-emerald-100">
                                            CoriSchool
                                        </p>

                                        <h3 className="mt-2 text-2xl font-black">
                                            Une école plus simple à piloter.
                                        </h3>
                                    </div>

                                    <div className="rounded-xl bg-white/10 px-3 py-2 text-[10px] font-bold">
                                        CONNECTÉE
                                    </div>
                                </div>

                                <div className="mt-8 grid grid-cols-2 gap-3">
                                    {[
                                        ['Notes', 'Centralisées'],
                                        ['Présences', 'Suivies'],
                                        ['Parents', 'Connectés'],
                                        ['Paiements', 'Organisés'],
                                    ].map(([title, value]) => (
                                        <div
                                            key={title}
                                            className="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur"
                                        >
                                            <p className="text-xs font-bold text-white">
                                                {title}
                                            </p>
                                            <p className="mt-1 text-[11px] text-emerald-100">
                                                {value}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </motion.div>
                </div>
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Features                                                                   */
/* -------------------------------------------------------------------------- */

function Features() {
    const [active, setActive] = useState('pilotage');

    const current =
        features.find((feature) => feature.id === active) ?? features[0];

    return (
        <section
            id="fonctionnalites"
            className="bg-white py-24 sm:py-28"
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Fonctionnalités"
                    title="Tout ce dont votre établissement a besoin."
                    description="Une architecture fonctionnelle conçue pour accompagner les opérations quotidiennes de votre école."
                />

                <div className="mt-14 grid gap-8 lg:grid-cols-[280px_1fr]">
                    {/* Tabs */}
                    <motion.div
                        variants={fadeUp}
                        initial="hidden"
                        whileInView="visible"
                        viewport={viewport}
                        className="space-y-2"
                    >
                        {features.map((feature) => {
                            const Icon = feature.icon;
                            const isActive = feature.id === active;

                            return (
                                <button
                                    key={feature.id}
                                    type="button"
                                    onClick={() => setActive(feature.id)}
                                    className={cn(
                                        'w-full rounded-2xl p-4 text-left transition',
                                        isActive
                                            ? 'bg-slate-950 text-white shadow-xl shadow-slate-900/10'
                                            : 'bg-slate-50 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700',
                                    )}
                                >
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={cn(
                                                'rounded-xl p-2.5',
                                                isActive
                                                    ? 'bg-emerald-500 text-white'
                                                    : 'bg-white text-emerald-600',
                                            )}
                                        >
                                            <Icon className="h-4 w-4" />
                                        </div>

                                        <span className="text-sm font-bold">
                                            {feature.title}
                                        </span>
                                    </div>
                                </button>
                            );
                        })}
                    </motion.div>

                    {/* Feature showcase */}
                    <AnimatePresence mode="wait">
                        <motion.div
                            key={active}
                            initial={{
                                opacity: 0,
                                x: 20,
                            }}
                            animate={{
                                opacity: 1,
                                x: 0,
                            }}
                            exit={{
                                opacity: 0,
                                x: -20,
                            }}
                            transition={{
                                duration: 0.35,
                            }}
                            className="overflow-hidden rounded-[2rem] border border-slate-100 bg-slate-50 p-5 sm:p-8"
                        >
                            <div className="grid items-center gap-8 lg:grid-cols-2">
                                <div>
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                                        <current.icon className="h-5 w-5" />
                                    </div>

                                    <h3 className="mt-6 text-2xl font-black text-slate-950">
                                        {current.title}
                                    </h3>

                                    <p className="mt-3 text-sm leading-6 text-slate-500">
                                        {current.description}
                                    </p>

                                    <div className="mt-6 space-y-3">
                                        {current.points.map((point) => (
                                            <div
                                                key={point}
                                                className="flex items-center gap-3"
                                            >
                                                <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-500" />
                                                <span className="text-sm font-semibold text-slate-700">
                                                    {point}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <FeatureMiniMockup type={active} />
                            </div>
                        </motion.div>
                    </AnimatePresence>
                </div>
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Feature Mini Mockups                                                        */
/* -------------------------------------------------------------------------- */

function FeatureMiniMockup({ type }) {
    if (type === 'notes') {
        return (
            <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-900/5">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-[10px] text-slate-400">
                            Classe
                        </p>
                        <p className="mt-1 text-sm font-black text-slate-900">
                            3e A
                        </p>
                    </div>

                    <div className="rounded-xl bg-emerald-50 px-3 py-2 text-[10px] font-bold text-emerald-700">
                        En cours
                    </div>
                </div>

                <div className="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                    <div className="grid grid-cols-3 bg-slate-50 px-3 py-2 text-[9px] font-bold text-slate-400">
                        <span>Élève</span>
                        <span>Note</span>
                        <span>Statut</span>
                    </div>

                    {['A. Koffi', 'M. Soglo', 'D. Ahouansou', 'J. Hounkpe'].map(
                        (student, index) => (
                            <div
                                key={student}
                                className="grid grid-cols-3 items-center border-t border-slate-100 px-3 py-3 text-[10px]"
                            >
                                <span className="font-semibold text-slate-700">
                                    {student}
                                </span>

                                <span className="font-black text-slate-900">
                                    {14 + index}.5
                                </span>

                                <span className="text-emerald-600">
                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                </span>
                            </div>
                        ),
                    )}
                </div>
            </div>
        );
    }

    if (type === 'parents') {
        return (
            <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-900/5">
                <div className="flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-sm font-black text-emerald-700">
                        AM
                    </div>

                    <div>
                        <p className="text-sm font-black text-slate-900">
                            Espace Parent
                        </p>
                        <p className="text-[10px] text-slate-400">
                            Suivi de la scolarité
                        </p>
                    </div>
                </div>

                <div className="mt-5 space-y-3">
                    {[
                        ['Résultats', 'Nouvelles notes disponibles'],
                        ['Présence', 'Présence enregistrée aujourd’hui'],
                        ['Message', 'Une information de l’école'],
                    ].map(([title, text]) => (
                        <div
                            key={title}
                            className="rounded-2xl bg-slate-50 p-4"
                        >
                            <div className="flex items-center gap-3">
                                <div className="rounded-xl bg-white p-2 text-emerald-600 shadow-sm">
                                    <Bell className="h-3.5 w-3.5" />
                                </div>

                                <div>
                                    <p className="text-[10px] font-black text-slate-800">
                                        {title}
                                    </p>
                                    <p className="mt-1 text-[9px] text-slate-400">
                                        {text}
                                    </p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    if (type === 'finances') {
        return (
            <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-900/5">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-[10px] text-slate-400">
                            Suivi financier
                        </p>
                        <p className="mt-1 text-2xl font-black text-slate-950">
                            2 450 000 F
                        </p>
                    </div>

                    <div className="rounded-xl bg-emerald-50 p-3 text-emerald-600">
                        <Wallet className="h-5 w-5" />
                    </div>
                </div>

                <div className="mt-6 h-3 overflow-hidden rounded-full bg-slate-100">
                    <motion.div
                        initial={{ width: 0 }}
                        animate={{ width: '72%' }}
                        transition={{ duration: 1 }}
                        className="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500"
                    />
                </div>

                <div className="mt-6 space-y-3">
                    {[
                        ['Scolarité', '1 700 000 F'],
                        ['Cantine', '420 000 F'],
                        ['Autres frais', '330 000 F'],
                    ].map(([label, amount]) => (
                        <div
                            key={label}
                            className="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-3"
                        >
                            <span className="text-[10px] font-semibold text-slate-500">
                                {label}
                            </span>
                            <span className="text-[10px] font-black text-slate-900">
                                {amount}
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-900/5">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-[10px] text-slate-400">
                        Vue globale
                    </p>
                    <p className="mt-1 text-sm font-black text-slate-900">
                        Aujourd’hui
                    </p>
                </div>

                <div className="rounded-xl bg-emerald-50 p-2.5 text-emerald-600">
                    <LayoutDashboard className="h-4 w-4" />
                </div>
            </div>

            <div className="mt-5 grid grid-cols-2 gap-3">
                {[
                    ['Présence', '94%'],
                    ['Moyenne', '14.8'],
                    ['Retards', '12'],
                    ['Alertes', '04'],
                ].map(([label, value]) => (
                    <div
                        key={label}
                        className="rounded-2xl border border-slate-100 p-4"
                    >
                        <p className="text-[9px] text-slate-400">
                            {label}
                        </p>
                        <p className="mt-1 text-lg font-black text-slate-900">
                            {value}
                        </p>
                    </div>
                ))}
            </div>

            <div className="mt-4 rounded-2xl bg-slate-950 p-4 text-white">
                <div className="flex items-center justify-between">
                    <p className="text-[10px] font-bold">
                        Actions à traiter
                    </p>
                    <AlertCircle className="h-4 w-4 text-emerald-400" />
                </div>

                <p className="mt-2 text-[9px] text-slate-400">
                    Les éléments nécessitant votre attention apparaissent ici.
                </p>
            </div>
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/* Audience                                                                    */
/* -------------------------------------------------------------------------- */

function Audience() {
    return (
        <section className="bg-slate-950 py-24 sm:py-28">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Pour toute votre communauté"
                    title="Une expérience adaptée à chaque acteur."
                    description="CoriSchool ne se limite pas à l’administration. La plateforme crée un environnement numérique cohérent pour toute la communauté scolaire."
                />

                <motion.div
                    variants={stagger}
                    initial="hidden"
                    whileInView="visible"
                    viewport={viewport}
                    className="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    {audiences.map(
                        ({ icon: Icon, title, description }) => (
                            <motion.div
                                key={title}
                                variants={fadeUp}
                                className="group rounded-3xl border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:bg-white/[0.07]"
                            >
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-400 transition group-hover:bg-emerald-500 group-hover:text-white">
                                    <Icon className="h-5 w-5" />
                                </div>

                                <h3 className="mt-6 text-lg font-black text-white">
                                    {title}
                                </h3>

                                <p className="mt-3 text-sm leading-6 text-slate-400">
                                    {description}
                                </p>
                            </motion.div>
                        ),
                    )}
                </motion.div>
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Workflow                                                                   */
/* -------------------------------------------------------------------------- */

function Workflow() {
    return (
        <section className="bg-white py-24 sm:py-28">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Comment ça fonctionne"
                    title="Passez au numérique sans complexité."
                    description="Une mise en place progressive pour vous permettre de commencer simplement et d’étendre la plateforme selon vos besoins."
                />

                <motion.div
                    variants={stagger}
                    initial="hidden"
                    whileInView="visible"
                    viewport={viewport}
                    className="relative mt-16 grid gap-8 md:grid-cols-2 lg:grid-cols-4"
                >
                    <div className="absolute left-[12%] right-[12%] top-7 hidden h-px bg-gradient-to-r from-emerald-200 via-teal-300 to-emerald-200 lg:block" />

                    {workflow.map((item) => (
                        <motion.div
                            key={item.number}
                            variants={fadeUp}
                            className="relative text-center"
                        >
                            <div className="relative mx-auto flex h-14 w-14 items-center justify-center rounded-full border-4 border-white bg-slate-950 text-sm font-black text-white shadow-xl shadow-slate-900/10">
                                <span className="text-emerald-400">
                                    {item.number}
                                </span>
                            </div>

                            <h3 className="mt-6 text-base font-black text-slate-950">
                                {item.title}
                            </h3>

                            <p className="mx-auto mt-3 max-w-xs text-sm leading-6 text-slate-500">
                                {item.description}
                            </p>
                        </motion.div>
                    ))}
                </motion.div>
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Pricing                                                                    */
/* -------------------------------------------------------------------------- */

function Pricing({ plans = [], smsNote }) {
    return (
        <section
            id="tarifs"
            className="bg-slate-50 py-24 sm:py-28"
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="Tarification"
                    title="Choisissez l’offre adaptée à votre établissement."
                    description="Commencez avec les fonctionnalités dont vous avez besoin et évoluez lorsque votre école grandit."
                />

                {plans?.length > 0 ? (
                    <motion.div
                        variants={stagger}
                        initial="hidden"
                        whileInView="visible"
                        viewport={viewport}
                        className="mt-14 grid gap-5 lg:grid-cols-3"
                    >
                        {plans.map((plan, index) => (
                            <motion.div
                                key={plan.id ?? plan.name ?? index}
                                variants={fadeUp}
                                className={cn(
                                    'relative flex flex-col rounded-[2rem] border bg-white p-7 shadow-sm',
                                    index === 1
                                        ? 'border-emerald-300 shadow-xl shadow-emerald-900/10'
                                        : 'border-slate-200',
                                )}
                            >
                                {index === 1 && (
                                    <div className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-emerald-600 px-4 py-1.5 text-[10px] font-black uppercase tracking-wider text-white">
                                        Recommandé
                                    </div>
                                )}

                                <h3 className="text-lg font-black text-slate-950">
                                    {plan.name}
                                </h3>

                                {plan.description && (
                                    <p className="mt-2 min-h-[48px] text-sm leading-6 text-slate-500">
                                        {plan.description}
                                    </p>
                                )}

                                <div className="mt-6">
                                    <span className="text-4xl font-black tracking-tight text-slate-950">
                                        {plan.price ?? plan.amount ?? '—'}
                                    </span>

                                    {plan.period && (
                                        <span className="ml-2 text-sm font-semibold text-slate-400">
                                            {plan.period}
                                        </span>
                                    )}
                                </div>

                                <div className="my-7 h-px bg-slate-100" />

                                <div className="flex-1 space-y-3">
                                    {(plan.features ?? []).map((feature) => (
                                        <div
                                            key={
                                                typeof feature === 'string'
                                                    ? feature
                                                    : feature.name
                                            }
                                            className="flex items-start gap-3"
                                        >
                                            <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />

                                            <span className="text-sm font-medium text-slate-600">
                                                {typeof feature === 'string'
                                                    ? feature
                                                    : feature.name}
                                            </span>
                                        </div>
                                    ))}
                                </div>

                                <Link
                                    href={route('demo.request')}
                                    className={cn(
                                        'mt-8 flex items-center justify-center gap-2 rounded-xl px-5 py-3.5 text-sm font-black transition',
                                        index === 1
                                            ? 'bg-emerald-600 text-white hover:bg-emerald-500'
                                            : 'bg-slate-950 text-white hover:bg-emerald-600',
                                    )}
                                >
                                    Choisir cette offre
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            </motion.div>
                        ))}
                    </motion.div>
                ) : (
                    <motion.div
                        variants={fadeUp}
                        initial="hidden"
                        whileInView="visible"
                        viewport={viewport}
                        className="mx-auto mt-14 max-w-2xl rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm"
                    >
                        <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <Sparkles className="h-6 w-6" />
                        </div>

                        <h3 className="mt-5 text-xl font-black text-slate-950">
                            Des offres adaptées à votre établissement
                        </h3>

                        <p className="mt-3 text-sm leading-6 text-slate-500">
                            Contactez-nous pour découvrir les options
                            disponibles pour votre école.
                        </p>

                        <Link
                            href={route('demo.request')}
                            className="mt-6 inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-3.5 text-sm font-bold text-white transition hover:bg-emerald-600"
                        >
                            Commencer
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </motion.div>
                )}

                {smsNote && (
                    <p className="mx-auto mt-8 max-w-2xl text-center text-xs leading-5 text-slate-400">
                        {smsNote}
                    </p>
                )}
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Security                                                                   */
/* -------------------------------------------------------------------------- */

function Security() {
    return (
        <section className="bg-white py-20">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <motion.div
                    variants={scaleIn}
                    initial="hidden"
                    whileInView="visible"
                    viewport={viewport}
                    className="overflow-hidden rounded-[2rem] bg-slate-950 p-7 sm:p-10"
                >
                    <div className="grid items-center gap-8 md:grid-cols-[auto_1fr_auto]">
                        <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-xl shadow-emerald-500/20">
                            <ShieldCheck className="h-7 w-7" />
                        </div>

                        <div>
                            <p className="text-xs font-bold uppercase tracking-[0.16em] text-emerald-400">
                                Sécurité
                            </p>

                            <h3 className="mt-2 text-2xl font-black text-white">
                                Vos données scolaires méritent une protection
                                renforcée.
                            </h3>

                            <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                                Contrôle des accès, rôles utilisateurs,
                                authentification renforcée et séparation des
                                données entre établissements font partie de
                                l’architecture de CoriSchool.
                            </p>
                        </div>

                        <Link
                            href={route('demo.request')}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-emerald-50"
                        >
                            En savoir plus
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    </div>
                </motion.div>
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* FAQ                                                                        */
/* -------------------------------------------------------------------------- */

function Faq({ faq = [] }) {
    const items = faq?.length > 0 ? faq : defaultFaq;
    const [open, setOpen] = useState(0);

    return (
        <section
            id="faq"
            className="bg-slate-50 py-24 sm:py-28"
        >
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="FAQ"
                    title="Questions fréquentes."
                    description="Quelques réponses aux questions que se posent les établissements avant de passer au numérique."
                />

                <motion.div
                    variants={stagger}
                    initial="hidden"
                    whileInView="visible"
                    viewport={viewport}
                    className="mt-12 space-y-3"
                >
                    {items.map((item, index) => {
                        const question =
                            item.question ?? item.title ?? '';
                        const answer =
                            item.answer ?? item.description ?? '';

                        const isOpen = open === index;

                        return (
                            <motion.div
                                key={question}
                                variants={fadeUp}
                                className="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                            >
                                <button
                                    type="button"
                                    onClick={() =>
                                        setOpen(isOpen ? null : index)
                                    }
                                    className="flex w-full items-center justify-between gap-5 px-5 py-5 text-left sm:px-6"
                                >
                                    <span className="text-sm font-black text-slate-900 sm:text-base">
                                        {question}
                                    </span>

                                    <ChevronDown
                                        className={cn(
                                            'h-5 w-5 shrink-0 text-slate-400 transition-transform',
                                            isOpen && 'rotate-180 text-emerald-600',
                                        )}
                                    />
                                </button>

                                <AnimatePresence initial={false}>
                                    {isOpen && (
                                        <motion.div
                                            initial={{
                                                height: 0,
                                                opacity: 0,
                                            }}
                                            animate={{
                                                height: 'auto',
                                                opacity: 1,
                                            }}
                                            exit={{
                                                height: 0,
                                                opacity: 0,
                                            }}
                                            transition={{
                                                duration: 0.25,
                                            }}
                                        >
                                            <div className="border-t border-slate-100 px-5 pb-5 pt-4 text-sm leading-6 text-slate-500 sm:px-6">
                                                {answer}
                                            </div>
                                        </motion.div>
                                    )}
                                </AnimatePresence>
                            </motion.div>
                        );
                    })}
                </motion.div>
            </div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Final CTA                                                                  */
/* -------------------------------------------------------------------------- */

function FinalCta() {
    return (
        <section className="bg-white px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
            <motion.div
                variants={scaleIn}
                initial="hidden"
                whileInView="visible"
                viewport={viewport}
                className="relative mx-auto max-w-6xl overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-700 px-6 py-14 text-center shadow-2xl shadow-emerald-900/15 sm:px-10 sm:py-20"
            >
                <div className="absolute -left-24 -top-24 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
                <div className="absolute -bottom-32 -right-24 h-80 w-80 rounded-full bg-slate-950/10 blur-3xl" />

                <div className="relative">
                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 text-white backdrop-blur">
                        <School className="h-6 w-6" />
                    </div>

                    <h2 className="mx-auto mt-7 max-w-3xl text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl">
                        Prêt à connecter votre école ?
                    </h2>

                    <p className="mx-auto mt-5 max-w-2xl text-sm leading-6 text-emerald-50 sm:text-base">
                        Commencez à construire une expérience scolaire plus
                        moderne pour votre administration, vos enseignants,
                        vos élèves et vos parents.
                    </p>

                    <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <Link
                            href={route('demo.request')}
                            className="group inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-6 py-4 text-sm font-black text-emerald-700 shadow-xl transition hover:bg-slate-50"
                        >
                            Demander une démonstration
                            <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" />
                        </Link>

                        <Link
                            href={route('login')}
                            className="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-6 py-4 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20"
                        >
                            Se connecter
                        </Link>
                    </div>
                </div>
            </motion.div>
        </section>
    );
}

/* -------------------------------------------------------------------------- */
/* Footer                                                                     */
/* -------------------------------------------------------------------------- */

function SiteFooter() {
    return (
        <footer className="border-t border-slate-100 bg-slate-950 text-white">
            <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div className="grid gap-10 md:grid-cols-4">
                    <div className="md:col-span-2">
                        <div className="flex items-center gap-2.5">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600">
                                <School className="h-5 w-5" />
                            </div>

                            <div>
                                <p className="text-lg font-black">
                                    CoriSchool
                                </p>
                                <p className="text-[8px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                    Plateforme scolaire numérique
                                </p>
                            </div>
                        </div>

                        <p className="mt-5 max-w-md text-sm leading-6 text-slate-400">
                            Une plateforme moderne pour simplifier la gestion
                            scolaire et rapprocher les établissements des
                            familles.
                        </p>
                    </div>

                    <div>
                        <h4 className="text-sm font-black">Plateforme</h4>

                        <div className="mt-4 space-y-3">
                            <a
                                href="#fonctionnalites"
                                className="block text-sm text-slate-400 transition hover:text-white"
                            >
                                Fonctionnalités
                            </a>

                            <a
                                href="#tarifs"
                                className="block text-sm text-slate-400 transition hover:text-white"
                            >
                                Tarifs
                            </a>

                            <a
                                href="#faq"
                                className="block text-sm text-slate-400 transition hover:text-white"
                            >
                                FAQ
                            </a>
                        </div>
                    </div>

                    <div>
                        <h4 className="text-sm font-black">Compte</h4>

                        <div className="mt-4 space-y-3">
                            <Link
                                href={route('login')}
                                className="block text-sm text-slate-400 transition hover:text-white"
                            >
                                Se connecter
                            </Link>

                            <Link
                                href={route('demo.request')}
                                className="block text-sm text-slate-400 transition hover:text-white"
                            >
                                Demander une démo
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mt-12 flex flex-col justify-between gap-4 border-t border-white/10 pt-7 text-xs text-slate-500 sm:flex-row">
                    <p>
                        © {new Date().getFullYear()} CoriSchool. Tous droits
                        réservés.
                    </p>

                    <p>
                        Une solution de{' '}
                        <span className="font-bold text-slate-400">
                            Coriyase Technologies
                        </span>
                    </p>
                </div>
            </div>
        </footer>
    );
}

/* -------------------------------------------------------------------------- */
/* Main Component                                                             */
/* -------------------------------------------------------------------------- */

export default function CoriSchoolLanding({
    plans = [],
    smsNote = null,
    faq = [],
}) {
    return (
        <>
            <Head title="CoriSchool — Plateforme scolaire numérique" />

            <div className="min-h-screen bg-white text-slate-950 selection:bg-emerald-100 selection:text-emerald-900">
                <SiteHeader />

                <main>
                    <Hero />
                    <TrustStrip />
                    <Problems />
                    <Solution />
                    <Features />
                    <Audience />
                    <Workflow />
                    <Pricing plans={plans} smsNote={smsNote} />
                    <Security />
                    <Faq faq={faq} />
                    <FinalCta />
                </main>

                <SiteFooter />
            </div>
        </>
    );
}