import { useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { GraduationCap, HandCoins, LayoutDashboard, LogOut, MessageSquarePlus } from 'lucide-react';

import InstallPwaButton from '@/Components/PWA/InstallPwaButton';
import Toast from '@/Components/UI/Toast';
import { useFlash } from '@/Hooks/useFlash';
import { cn } from '@/Utils/cn';
import { rememberParentSchool } from '@/Utils/pwa';

const navigation = [
    { name: 'dashboard', label: 'Tableau de bord', icon: LayoutDashboard },
    { name: 'finance.index', label: 'Ma scolarité', icon: HandCoins },
    { name: 'messages.index', label: 'Messages', icon: MessageSquarePlus },
];

export default function ParentAccessLayout({ children }) {
    const { parentAccess } = usePage().props;
    const [toast, setToast] = useFlash();

    const school = parentAccess?.school;
    const parent = parentAccess?.parent;

    useEffect(() => {
        rememberParentSchool(school);
    }, [school?.slug, school?.name]);

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="border-b border-slate-200 bg-white shadow-sm">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between gap-4">
                        <Link href={parentAccess?.dashboard_url ?? '/'} className="flex min-w-0 items-center gap-3">
                            {school?.logo_url ? (
                                <img src={school.logo_url} alt={school.name} className="h-10 w-10 rounded-xl object-contain" />
                            ) : (
                                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
                                    <GraduationCap className="h-5 w-5" />
                                </span>
                            )}
                            <div className="min-w-0">
                                <p className="truncate text-sm font-bold text-slate-900">Espace parent</p>
                                <p className="truncate text-xs text-slate-500">{school?.name}</p>
                            </div>
                        </Link>

                        <div className="flex items-center gap-3">
                            <span className="hidden text-sm font-medium text-slate-700 sm:inline">{parent?.name}</span>
                            <Link
                                href={parentAccess?.logout_url ?? '#'}
                                method="post"
                                as="button"
                                className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"
                            >
                                <LogOut className="h-4 w-4" /> Déconnexion
                            </Link>
                        </div>
                    </div>

                    <nav className="flex flex-wrap gap-2">
                        {navigation.map((item) => {
                            const href = route(`parent-access.${item.name}`);
                            const active = typeof window !== 'undefined'
                                && window.location.pathname === new URL(href, window.location.origin).pathname;

                            return (
                                <Link
                                    key={item.name}
                                    href={href}
                                    className={cn(
                                        'inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition',
                                        active
                                            ? 'bg-emerald-600 text-white'
                                            : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                                    )}
                                >
                                    <item.icon className="h-4 w-4" />
                                    {item.label}
                                </Link>
                            );
                        })}

                        <InstallPwaButton />
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <Toast toast={toast} onDismiss={() => setToast(null)} />
                {children}
            </main>
        </div>
    );
}
