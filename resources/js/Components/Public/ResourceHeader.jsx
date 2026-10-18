import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { Menu, School, X } from 'lucide-react';

const NAV = [
    { label: 'Accueil', href: '/', inertia: true },
    { label: 'Fonctionnalités', href: '/#fonctionnalites' },
    { label: 'Banque d\'épreuves', href: '/banque-epreuves', match: 'public.banque-epreuves', inertia: true },
    { label: 'Jeu éducatif', href: '/jeu-educatif', match: 'public.jeu-educatif', inertia: true },
    { label: 'Compétitions', href: '/competitions', match: 'public.competitions', inertia: true },
    { label: 'Tarifs', href: '/#tarifs' },
];

export default function ResourceHeader() {
    const [open, setOpen] = useState(false);

    const isActive = (item) => item.match && route().current(item.match);

    return (
        <header className="fixed inset-x-0 top-0 z-50">
            <div className="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                <div className="rounded-2xl border border-white/70 bg-white/90 px-4 shadow-lg shadow-slate-900/5 backdrop-blur-xl">
                    <div className="flex h-16 items-center justify-between">
                        <Link href="/" className="flex items-center gap-2.5">
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
                        </Link>

                        <nav className="hidden items-center gap-6 lg:flex">
                            {NAV.map((item) =>
                                item.inertia ? (
                                    <Link
                                        key={item.label}
                                        href={item.href}
                                        className={`text-sm font-semibold transition hover:text-emerald-600 ${
                                            isActive(item) ? 'text-emerald-600' : 'text-slate-600'
                                        }`}
                                    >
                                        {item.label}
                                    </Link>
                                ) : (
                                    <a key={item.label} href={item.href} className="text-sm font-semibold text-slate-600 transition hover:text-emerald-600">
                                        {item.label}
                                    </a>
                                ),
                            )}
                        </nav>

                        <div className="hidden items-center gap-3 sm:flex">
                            <Link href={route('login')} className="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                Se connecter
                            </Link>
                            <Link
                                href={route('demo.request')}
                                className="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-slate-950/10 transition hover:bg-emerald-600"
                            >
                                Demander une démonstration
                            </Link>
                        </div>

                        <button
                            type="button"
                            onClick={() => setOpen(!open)}
                            className="rounded-xl p-2.5 text-slate-700 hover:bg-slate-100 lg:hidden"
                            aria-label="Menu"
                        >
                            {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                        </button>
                    </div>

                    <AnimatePresence>
                        {open && (
                            <motion.div
                                initial={{ height: 0, opacity: 0 }}
                                animate={{ height: 'auto', opacity: 1 }}
                                exit={{ height: 0, opacity: 0 }}
                                className="overflow-hidden lg:hidden"
                            >
                                <div className="border-t border-slate-100 py-4">
                                    <nav className="flex flex-col gap-1">
                                        {NAV.map((item) =>
                                            item.inertia ? (
                                                <Link
                                                    key={item.label}
                                                    href={item.href}
                                                    onClick={() => setOpen(false)}
                                                    className="flex items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-700"
                                                >
                                                    {item.label}
                                                </Link>
                                            ) : (
                                                <a
                                                    key={item.label}
                                                    href={item.href}
                                                    onClick={() => setOpen(false)}
                                                    className="flex items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-700"
                                                >
                                                    {item.label}
                                                </a>
                                            ),
                                        )}
                                    </nav>

                                    <div className="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                                        <Link href={route('login')} className="rounded-xl border border-slate-200 px-3 py-3 text-center text-sm font-bold text-slate-700">
                                            Connexion
                                        </Link>
                                        <Link href={route('demo.request')} className="rounded-xl bg-emerald-600 px-3 py-3 text-center text-sm font-bold text-white">
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
