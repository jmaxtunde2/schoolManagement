import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft, School } from 'lucide-react';

import ResourceHeader from '@/Components/Public/ResourceHeader';

const LEGAL_LINKS = [
    { label: 'Contact', routeName: 'public.contact' },
    { label: 'Conditions d’utilisation', routeName: 'public.terms' },
    { label: 'Politique de confidentialité', routeName: 'public.privacy' },
];

/**
 * Habillage commun des pages d'information publiques (contact, conditions
 * d'utilisation, confidentialité). Il reprend l'en-tête des pages ressources et
 * ajoute un hero, une carte de contenu et un pied de page légal.
 */
export default function InfoPageLayout({
    metaTitle,
    metaDescription,
    title,
    subtitle,
    updatedAt = null,
    children,
}) {
    const currentRoute = route().current();

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <Head title={metaTitle}>
                <meta name="description" content={metaDescription} />
                <meta name="robots" content="index, follow" />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content="CoriSchool" />
                <meta property="og:locale" content="fr_FR" />
                <meta property="og:title" content={metaTitle} />
                <meta property="og:description" content={metaDescription} />
                <meta property="og:image" content="/icons/icon-512.png" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content={metaTitle} />
                <meta name="twitter:description" content={metaDescription} />
                <meta name="twitter:image" content="/icons/icon-512.png" />
            </Head>

            <ResourceHeader />

            <section className="relative overflow-hidden bg-slate-950 pt-32 pb-16">
                <div className="absolute inset-0 opacity-20">
                    <div className="absolute right-0 top-0 h-72 w-72 rounded-full bg-emerald-500/40 blur-3xl" />
                    <div className="absolute left-10 bottom-0 h-72 w-72 rounded-full bg-teal-500/30 blur-3xl" />
                </div>

                <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <motion.div
                        initial={{ opacity: 0, y: 24 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.55 }}
                        className="max-w-3xl"
                    >
                        <Link
                            href="/"
                            className="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 transition hover:text-white"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Retour à l’accueil
                        </Link>

                        <h1 className="mt-6 text-3xl font-black leading-tight text-white sm:text-4xl lg:text-5xl">
                            {title}
                        </h1>

                        <p className="mt-5 text-base leading-7 text-slate-300">
                            {subtitle}
                        </p>

                        {updatedAt && (
                            <p className="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-emerald-300">
                                Dernière mise à jour : {updatedAt}
                            </p>
                        )}
                    </motion.div>
                </div>
            </section>

            <main className="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
                <motion.article
                    initial={{ opacity: 0, y: 24 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.55, delay: 0.1 }}
                    className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10"
                >
                    {children}
                </motion.article>
            </main>

            <footer className="border-t border-slate-200 bg-white py-10">
                <div className="mx-auto flex max-w-7xl flex-col gap-5 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div className="flex items-center gap-2.5">
                        <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
                            <School className="h-4 w-4" />
                        </div>
                        <div>
                            <p className="text-sm font-black text-slate-950">CoriSchool</p>
                            <p className="text-xs text-slate-400">
                                © {new Date().getFullYear()} CoriSchool — Plateforme scolaire numérique
                            </p>
                        </div>
                    </div>

                    <nav className="flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
                        {LEGAL_LINKS.map((link) => (
                            <Link
                                key={link.routeName}
                                href={route(link.routeName)}
                                className={`transition hover:text-emerald-600 ${
                                    currentRoute === link.routeName
                                        ? 'text-emerald-600'
                                        : 'text-slate-500'
                                }`}
                            >
                                {link.label}
                            </Link>
                        ))}
                    </nav>
                </div>
            </footer>
        </div>
    );
}

export function InfoSection({ title, children }) {
    return (
        <section className="scroll-mt-24">
            <h2 className="text-lg font-black text-slate-950 sm:text-xl">
                {title}
            </h2>
            <div className="mt-3 space-y-3 text-sm leading-7 text-slate-600">
                {children}
            </div>
        </section>
    );
}
