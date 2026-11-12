import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import { ChevronDown, Menu, School, X } from 'lucide-react';

const NAV = [
    { label: 'Accueil', href: '/', inertia: true },
    { label: 'Fonctionnalités', href: '/#fonctionnalites' },
    { label: 'Solution', href: '/#solution' },
    { label: 'Tarifs', href: '/#tarifs' },
    { label: 'FAQ', href: '/#faq' },
];

const RESOURCES = [
    { label: 'Banque d’épreuves', href: '/banque-epreuves', match: 'public.banque-epreuves' },
    { label: 'Jeu éducatif', href: '/jeu-educatif', match: 'public.jeu-educatif' },
    { label: 'Compétitions', href: '/competitions', match: 'public.competitions' },
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

                            <div className="group relative">
                                <button
                                    type="button"
                                    className="flex items-center gap-1 text-sm font-semibold text-slate-600 transition hover:text-emerald-600"
                                >
                                    Ressources
                                    <ChevronDown className="h-3.5 w-3.5 transition-transform duration-200 group-hover:rotate-180" />
                                </button>

                                <div className="invisible absolute left-1/2 top-full z-50 -translate-x-1/2 pt-3 opacity-0 transition duration-150 group-hover:visible group-hover:opacity-100">
                                    <div className="w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-900/10">
                                        {RESOURCES.map((item) => (
                                            <Link
                                                key={item.label}
                                                href={route(item.match)}
                                                className={`block rounded-xl px-3 py-2.5 text-sm font-semibold transition hover:bg-emerald-50 hover:text-emerald-700 ${
                                                    isActive(item) ? 'text-emerald-700' : 'text-slate-600'
                                                }`}
                                            >
                                                {item.label}
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            </div>
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

                                    <div className="mt-3 border-t border-slate-100 pt-3">
                                        <p className="px-3 pb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">
                                            Ressources pédagogiques
                                        </p>
                                        {RESOURCES.map((item) => (
                                            <Link
                                                key={item.label}
                                                href={route(item.match)}
                                                onClick={() => setOpen(false)}
                                                className={`block rounded-xl px-3 py-3 text-sm font-semibold hover:bg-emerald-50 hover:text-emerald-700 ${
                                                    isActive(item) ? 'text-emerald-700' : 'text-slate-600'
                                                }`}
                                            >
                                                {item.label}
                                            </Link>
                                        ))}
                                    </div>

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
