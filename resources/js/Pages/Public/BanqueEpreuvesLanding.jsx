import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    ArrowRight,
    BookOpen,
    Check,
    Clock,
    Download,
    FileText,
    Filter,
    Layers,
    Lock,
    Search,
    ShieldCheck,
    Sparkles,
    Target,
} from 'lucide-react';

import ResourceHeader from '@/Components/Public/ResourceHeader';
import WhatsAppButton from '@/Components/Support/WhatsAppButton';

const fadeUp = {
    hidden: { opacity: 0, y: 30 },
    visible: { opacity: 1, y: 0 },
};

const highlights = [
    {
        icon: Layers,
        title: 'Classée par niveau et matière',
        text: 'Retrouvez les ressources du collège au lycée, rangées par niveau, matière et type de document.',
    },
    {
        icon: Search,
        title: 'Recherche et filtres',
        text: 'Un moteur de recherche avec filtres par niveau, matière, type et difficulté pour gagner du temps.',
    },
    {
        icon: FileText,
        title: 'Tous les formats utiles',
        text: 'Épreuves, exercices, devoirs, examens blancs, QCM, sujets d\'examen et fiches d\'exercices.',
    },
    {
        icon: Download,
        title: 'Téléchargement filigrané',
        text: 'Chaque document téléchargé porte le filigrane CoriSchool pour préserver la propriété des contenus.',
    },
    {
        icon: Lock,
        title: 'Corrigés protégés',
        text: 'Les corrigés sont disponibles séparément et ne sont jamais exposés aux élèves.',
    },
    {
        icon: ShieldCheck,
        title: 'Banque centrale gouvernée',
        text: 'Les administrateurs publient, révisent et archivent les ressources de manière contrôlée.',
    },
];

const steps = [
    { title: 'Recherchez', text: 'Filtrez par niveau, matière, type ou difficulté.' },
    { title: 'Prévisualisez', text: 'Consultez le détail et les fichiers disponibles.' },
    { title: 'Téléchargez', text: 'Récupérez l\'épreuve avec son filigrane CoriSchool.' },
    { title: 'Utilisez en classe', text: 'Réutilisez les ressources pour vos propres séances.' },
];

export default function BanqueEpreuvesLanding() {
    return (
        <>
            <Head title="Banque d'épreuves | CoriSchool — Ressources pédagogiques classées par niveau et matière">
                <meta
                    name="description"
                    content="Retrouvez des épreuves, exercices, devoirs, examens blancs et corrigés classés par niveau et par matière. Bibliothèque pédagogique centralisée CoriSchool pour enseignants et établissements."
                />
                <meta
                    name="keywords"
                    content="banque d'épreuves, exercices scolaires, devoirs, examens, corrigés, ressources pédagogiques, sujets d'examen, CoriSchool"
                />
                <meta name="robots" content="index, follow" />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content="CoriSchool" />
                <meta property="og:locale" content="fr_FR" />
                <meta property="og:title" content="Banque d'épreuves | CoriSchool — Ressources pédagogiques classées par niveau et matière" />
                <meta
                    property="og:description"
                    content="Une bibliothèque pédagogique centralisée : épreuves, exercices, corrigés et ressources classés par niveau et matière."
                />
                <meta property="og:image" content="/icons/icon-512.png" />
                <meta property="og:image:alt" content="Banque d'épreuves CoriSchool" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content="Banque d'épreuves | CoriSchool" />
                <meta name="twitter:description" content="Épreuves, exercices et corrigés classés par niveau et matière sur CoriSchool." />
                <meta name="twitter:image" content="/icons/icon-512.png" />
            </Head>

            <div className="min-h-screen bg-slate-50">
                <ResourceHeader />

                <section className="relative overflow-hidden bg-slate-950 pt-32 pb-20">
                    <div className="absolute inset-0 opacity-20">
                        <div className="absolute -right-20 top-10 h-72 w-72 rounded-full bg-emerald-500/40 blur-3xl" />
                        <div className="absolute -left-10 bottom-0 h-72 w-72 rounded-full bg-teal-500/30 blur-3xl" />
                    </div>

                    <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="grid items-center gap-12 lg:grid-cols-2">
                            <motion.div variants={fadeUp} initial="hidden" animate="visible" transition={{ duration: 0.6 }}>
                                <span className="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-300">
                                    <Sparkles className="h-3.5 w-3.5" /> Ressources pédagogiques
                                </span>
                                <h1 className="mt-5 text-3xl font-black leading-tight text-white sm:text-4xl lg:text-5xl">
                                    Une banque d'épreuves pour mieux préparer vos élèves
                                </h1>
                                <p className="mt-5 max-w-xl text-base leading-7 text-slate-300">
                                    Retrouvez des épreuves et exercices classés par niveau et par matière,
                                    accessibles depuis une bibliothèque pédagogique centralisée.
                                </p>

                                <div className="mt-8 flex flex-wrap gap-3">
                                    <Link
                                        href={route('demo.request')}
                                        className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500"
                                    >
                                        Demander une démonstration
                                        <ArrowRight className="h-4 w-4" />
                                    </Link>
                                    <Link
                                        href={route('login')}
                                        className="inline-flex items-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10"
                                    >
                                        Accéder à mon espace
                                    </Link>
                                </div>
                            </motion.div>

                            <motion.div
                                initial={{ opacity: 0, scale: 0.95 }}
                                animate={{ opacity: 1, scale: 1 }}
                                transition={{ duration: 0.6, delay: 0.15 }}
                                className="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur"
                            >
                                <div className="rounded-xl bg-white p-5 shadow-2xl">
                                    <p className="text-sm font-bold text-slate-900">Banque d'épreuves</p>

                                    <div className="mt-4 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-400">
                                        <Search className="h-4 w-4" />
                                        Rechercher une épreuve...
                                    </div>

                                    <div className="mt-3 grid grid-cols-2 gap-2 text-xs">
                                        {['Niveau : 3e', 'Matière : Maths', 'Type : Épreuve', 'Difficulté : Moyenne'].map((filter) => (
                                            <span key={filter} className="rounded-lg border border-slate-200 px-3 py-2 font-medium text-slate-600">
                                                {filter}
                                            </span>
                                        ))}
                                    </div>

                                    <div className="mt-4 space-y-2">
                                        {[
                                            { subject: 'Mathématiques · 3e', title: 'Épreuve de mathématiques', level: 'Moyenne' },
                                            { subject: 'SVT · 4e', title: 'Exercices sur la digestion', level: 'Facile' },
                                        ].map((card) => (
                                            <div key={card.title} className="rounded-xl border border-slate-200 p-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">{card.subject}</span>
                                                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">{card.level}</span>
                                                </div>
                                                <p className="mt-2 text-sm font-semibold text-slate-800">{card.title}</p>
                                                <div className="mt-2 flex gap-2 text-[11px] font-bold text-emerald-600">
                                                    <span>Voir</span>
                                                    <span>·</span>
                                                    <span>Télécharger</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </motion.div>
                        </div>
                    </div>
                </section>

                <section className="py-20">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="max-w-2xl">
                            <h2 className="text-2xl font-black text-slate-900 sm:text-3xl">
                                Toute votre pédagogie documentaire, centralisée
                            </h2>
                            <p className="mt-3 text-slate-600">
                                Une bibliothèque commune à tous les établissements, enrichie par la plateforme CoriSchool.
                            </p>
                        </div>

                        <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {highlights.map((item, index) => (
                                <motion.div
                                    key={item.title}
                                    variants={fadeUp}
                                    initial="hidden"
                                    whileInView="visible"
                                    viewport={{ once: true }}
                                    transition={{ duration: 0.45, delay: index * 0.05 }}
                                    className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                                >
                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <item.icon className="h-5 w-5" />
                                    </div>
                                    <h3 className="mt-4 font-bold text-slate-900">{item.title}</h3>
                                    <p className="mt-2 text-sm leading-6 text-slate-500">{item.text}</p>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="border-y border-slate-200 bg-white py-20">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <h2 className="text-2xl font-black text-slate-900 sm:text-3xl">En quatre étapes</h2>
                        <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            {steps.map((step, index) => (
                                <div key={step.title} className="relative rounded-2xl border border-slate-200 p-6">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">
                                        {index + 1}
                                    </span>
                                    <h3 className="mt-4 font-bold text-slate-900">{step.title}</h3>
                                    <p className="mt-2 text-sm text-slate-500">{step.text}</p>
                                </div>
                            ))}
                        </div>

                        <div className="mt-10 grid gap-4 sm:grid-cols-3">
                            {[
                                { icon: Clock, label: 'Durée indicative' },
                                { icon: Filter, label: 'Filtres avancés' },
                                { icon: Target, label: 'Niveaux du programme' },
                            ].map((badge) => (
                                <div key={badge.label} className="flex items-center gap-3 rounded-xl bg-slate-50 p-4">
                                    <badge.icon className="h-5 w-5 text-emerald-600" />
                                    <span className="text-sm font-semibold text-slate-700">{badge.label}</span>
                                    <Check className="ml-auto h-4 w-4 text-emerald-500" />
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="py-20">
                    <div className="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                        <BookOpen className="mx-auto h-10 w-10 text-emerald-600" />
                        <h2 className="mt-4 text-2xl font-black text-slate-900 sm:text-3xl">
                            Préparez vos élèves avec des contenus de qualité
                        </h2>
                        <p className="mt-3 text-slate-600">
                            La banque d'épreuves est accessible depuis votre espace CoriSchool, sans créer de compte supplémentaire.
                        </p>
                        <div className="mt-8 flex flex-wrap justify-center gap-3">
                            <Link
                                href={route('demo.request')}
                                className="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-600"
                            >
                                Demander une démonstration
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                            <Link
                                href={route('public.jeu-educatif')}
                                className="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-6 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-100"
                            >
                                Découvrir le jeu éducatif
                            </Link>
                        </div>
                    </div>
                </section>

                <footer className="border-t border-slate-200 py-8 text-center text-sm text-slate-400">
                    © {new Date().getFullYear()} CoriSchool — Plateforme scolaire numérique
                </footer>
            </div>

            <WhatsAppButton />
        </>
    );
}
