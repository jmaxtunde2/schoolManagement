import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowRight, BarChart3, Medal, Sparkles, Users } from 'lucide-react';

import ResourceHeader from '@/Components/Public/ResourceHeader';
import WhatsAppButton from '@/Components/Support/WhatsAppButton';

const previews = [
    { icon: Medal, title: 'Défis entre élèves', text: 'Des compétitions saines pour stimuler la progression.' },
    { icon: BarChart3, title: 'Résultats suivis', text: 'Des classements et statistiques exploitables en classe.' },
    { icon: Users, title: 'Ouvert aux établissements', text: 'Une dynamique collective à l\'échelle de l\'école.' },
];

export default function CompetitionsLanding() {
    return (
        <>
            <Head title="Compétitions scolaires | CoriSchool — Motiver et valoriser les élèves">
                <meta
                    name="description"
                    content="Compétitions éducatives CoriSchool : défis entre élèves, classements, résultats et valorisation des progrès pour motiver toute l'école."
                />
                <meta name="keywords" content="compétitions scolaires, défis élèves, classement scolaire, concours école, gamification, CoriSchool" />
                <meta name="robots" content="index, follow" />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content="CoriSchool" />
                <meta property="og:locale" content="fr_FR" />
                <meta property="og:title" content="Compétitions scolaires | CoriSchool — Motiver et valoriser les élèves" />
                <meta property="og:description" content="Des défis scolaires sains entre élèves avec classements et valorisation des progrès." />
                <meta property="og:image" content="/icons/icon-512.png" />
                <meta property="og:image:alt" content="Compétitions scolaires CoriSchool" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content="Compétitions scolaires | CoriSchool" />
                <meta name="twitter:description" content="Motiver les élèves avec des compétitions éducatives et des classements sur CoriSchool." />
                <meta name="twitter:image" content="/icons/icon-512.png" />
            </Head>

            <div className="min-h-screen bg-slate-50">
                <ResourceHeader />

                <section className="relative overflow-hidden bg-slate-950 pt-32 pb-20">
                    <div className="absolute inset-0 opacity-20">
                        <div className="absolute right-0 top-0 h-72 w-72 rounded-full bg-amber-500/40 blur-3xl" />
                        <div className="absolute left-10 bottom-0 h-72 w-72 rounded-full bg-emerald-500/30 blur-3xl" />
                    </div>

                    <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <motion.div
                            initial={{ opacity: 0, y: 30 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.6 }}
                            className="max-w-2xl"
                        >
                            <span className="inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-500/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-300">
                                <Sparkles className="h-3.5 w-3.5" /> Bientôt disponible
                            </span>
                            <h1 className="mt-5 text-3xl font-black leading-tight text-white sm:text-4xl lg:text-5xl">
                                Compétitions scolaires CoriSchool
                            </h1>
                            <p className="mt-5 text-base leading-7 text-slate-300">
                                Des défis éducatifs pour valoriser les efforts des élèves et faire vivre
                                une émulation positive au sein de votre établissement.
                            </p>

                            <div className="mt-8 flex flex-wrap gap-3">
                                <Link
                                    href={route('demo.request')}
                                    className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500"
                                >
                                    Être informé du lancement
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                                <Link
                                    href={route('public.jeu-educatif')}
                                    className="inline-flex items-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10"
                                >
                                    Découvrir le jeu éducatif
                                </Link>
                            </div>
                        </motion.div>
                    </div>
                </section>

                <section className="py-20">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="grid gap-5 sm:grid-cols-3">
                            {previews.map((item, index) => (
                                <motion.div
                                    key={item.title}
                                    initial={{ opacity: 0, y: 24 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    viewport={{ once: true }}
                                    transition={{ duration: 0.45, delay: index * 0.06 }}
                                    className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                                >
                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                                        <item.icon className="h-5 w-5" />
                                    </div>
                                    <h3 className="mt-4 font-bold text-slate-900">{item.title}</h3>
                                    <p className="mt-2 text-sm leading-6 text-slate-500">{item.text}</p>
                                </motion.div>
                            ))}
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
