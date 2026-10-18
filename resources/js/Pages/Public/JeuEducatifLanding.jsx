import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowRight, Gamepad2, Sparkles, Trophy, Zap } from 'lucide-react';

import ResourceHeader from '@/Components/Public/ResourceHeader';
import WhatsAppButton from '@/Components/Support/WhatsAppButton';

const previews = [
    { icon: Zap, title: 'Défis interactifs', text: 'Des activités courtes et ludiques pour réviser en s\'amusant.' },
    { icon: Trophy, title: 'Classements', text: 'Des progressions visibles pour motiver les élèves.' },
    { icon: Sparkles, title: 'Contenus alignés', text: 'Des exercices alignés sur le programme scolaire.' },
];

export default function JeuEducatifLanding() {
    return (
        <>
            <Head title="Jeu éducatif | CoriSchool">
                <meta
                    name="description"
                    content="Les explorations ludiques CoriSchool : des défis interactifs pour réviser le programme en s'amusant."
                />
                <meta property="og:title" content="Jeu éducatif | CoriSchool" />
                <meta property="og:type" content="website" />
            </Head>

            <div className="min-h-screen bg-slate-50">
                <ResourceHeader />

                <section className="relative overflow-hidden bg-slate-950 pt-32 pb-20">
                    <div className="absolute inset-0 opacity-20">
                        <div className="absolute right-0 top-0 h-72 w-72 rounded-full bg-indigo-500/40 blur-3xl" />
                        <div className="absolute left-10 bottom-0 h-72 w-72 rounded-full bg-emerald-500/30 blur-3xl" />
                    </div>

                    <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <motion.div
                            initial={{ opacity: 0, y: 30 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.6 }}
                            className="max-w-2xl"
                        >
                            <span className="inline-flex items-center gap-2 rounded-full border border-indigo-400/30 bg-indigo-500/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-300">
                                <Gamepad2 className="h-3.5 w-3.5" /> Bientôt disponible
                            </span>
                            <h1 className="mt-5 text-3xl font-black leading-tight text-white sm:text-4xl lg:text-5xl">
                                Le jeu éducatif CoriSchool
                            </h1>
                            <p className="mt-5 text-base leading-7 text-slate-300">
                                Les explorations ludiques transforment la révision en défis interactifs,
                                pensés pour capter l'attention des élèves et consolider les acquis.
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
                                    href={route('public.banque-epreuves')}
                                    className="inline-flex items-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10"
                                >
                                    Voir la banque d'épreuves
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
                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
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
