import { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard, Building2, CalendarRange, School as SchoolIcon, BookOpen, Users, UserRound,
    GraduationCap, ClipboardList, BellRing, Globe2, ClipboardCheck, BookOpenCheck, FileText, Menu, X, LogOut, ChevronDown, CheckCheck,
} from 'lucide-react';
import Dropdown from '@/Components/UI/Dropdown';
import Toast from '@/Components/UI/Toast';
import { useFlash } from '@/Hooks/useFlash';
import { cn } from '@/Utils/cn';

const adminNav = [
    { href: 'admin.dashboard', label: 'Tableau de bord', icon: LayoutDashboard },
    { href: 'admin.evaluations.index', label: 'Évaluations', icon: ClipboardList },
    { href: 'admin.attendance.index', label: 'Absences & retards', icon: ClipboardCheck },
    { href: 'admin.academic.results.index', label: 'Résultats académiques', icon: BookOpenCheck },
    { href: 'admin.report-cards.index', label: 'Bulletins', icon: FileText },
    { href: 'admin.notifications.index', label: 'Notifications', icon: BellRing },
    { href: 'admin.students.index', label: 'Élèves', icon: GraduationCap },
    { href: 'admin.guardians.index', label: 'Parents', icon: UserRound },
    { href: 'admin.teachers.index', label: 'Enseignants', icon: Users },
    { href: 'admin.classes.index', label: 'Classes', icon: SchoolIcon },
    { href: 'admin.subjects.index', label: 'Matières', icon: BookOpen },
    { href: 'admin.academic-years.index', label: 'Années scolaires', icon: CalendarRange },
    { href: 'admin.billing.index', label: 'Services & contributions', icon: Building2 },
    { href: 'admin.users.index', label: 'Utilisateurs', icon: Users },
    { href: 'admin.settings.edit', label: 'Établissement', icon: Building2 },
    { href: 'admin.settings.public-site.edit', label: 'Site public', icon: Globe2 },
];


const censeurNav = [
    { href: 'censeur.dashboard', label: 'Tableau de bord', icon: LayoutDashboard },
    { href: 'censeur.evaluations.index', label: 'Évaluations à contrôler', icon: ClipboardList },
    { href: 'censeur.attendance.index', label: 'Absences & retards', icon: ClipboardCheck },
    { href: 'censeur.academic.results.index', label: 'Résultats académiques', icon: BookOpenCheck },
    { href: 'censeur.report-cards.index', label: 'Bulletins', icon: FileText },
    { href: 'censeur.students.index', label: 'Élèves', icon: GraduationCap },
    { href: 'censeur.guardians.index', label: 'Parents', icon: UserRound },
    { href: 'censeur.teachers.index', label: 'Enseignants', icon: Users },
    { href: 'censeur.classes.index', label: 'Classes', icon: SchoolIcon },
    { href: 'censeur.subjects.index', label: 'Matières', icon: BookOpen },
];

const secretaryNav = [
    { href: 'secretary.dashboard', label: 'Tableau de bord', icon: LayoutDashboard },
    { href: 'teacher.evaluations.index', label: 'Évaluations', icon: ClipboardList },
    { href: 'teacher.evaluations.create', label: 'Saisir une évaluation', icon: ClipboardList },
    { href: 'teacher.attendance.index', label: 'Absences & retards', icon: ClipboardCheck },
    { href: 'teacher.academic.results.index', label: 'Résultats académiques', icon: BookOpenCheck },
    { href: 'teacher.report-cards.index', label: 'Bulletins', icon: FileText },
];

const teacherNav = [
    { href: 'teacher.dashboard', label: 'Tableau de bord', icon: LayoutDashboard },
    { href: 'teacher.evaluations.index', label: 'Mes évaluations', icon: ClipboardList },
    { href: 'teacher.attendance.index', label: 'Assiduité des classes', icon: ClipboardCheck },
    { href: 'teacher.academic.results.index', label: 'Résultats académiques', icon: BookOpenCheck },
    { href: 'teacher.report-cards.index', label: 'Bulletins', icon: FileText },
];

const parentNav = [
    { href: 'parent.dashboard', label: 'Mes enfants', icon: LayoutDashboard },
];

export default function AuthenticatedLayout({ children, title }) {
    const { auth, school, url } = usePage().props;
    const currentUrl = usePage().url;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [toast, setToast] = useFlash();
    const unreadCount = auth.user.unread_notifications_count ?? 0;
    const notifications = auth.user.notifications ?? [];

    const nav = auth.user.role === 'admin'
        ? adminNav
        : auth.user.role === 'censeur'
            ? censeurNav
            : auth.user.role === 'secretary'
                ? secretaryNav
                : auth.user.role === 'parent'
                    ? parentNav
                    : teacherNav;

    function logout() {
        router.post(route('logout'));
    }

    return (
        <div className="min-h-screen bg-slate-50">
            {/* Sidebar desktop */}
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white lg:flex">
                <SidebarContent nav={nav} school={school} currentUrl={currentUrl} />
            </aside>

            {/* Drawer mobile */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div className="fixed inset-0 bg-slate-900/50" onClick={() => setSidebarOpen(false)} />
                    <aside className="fixed inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col bg-white shadow-xl">
                        <div className="flex justify-end p-3">
                            <button
                                onClick={() => setSidebarOpen(false)}
                                className="rounded-md p-2 text-slate-500 hover:bg-slate-100"
                                aria-label="Fermer le menu"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <SidebarContent nav={nav} school={school} currentUrl={currentUrl} onNavigate={() => setSidebarOpen(false)} />
                    </aside>
                </div>
            )}

            <div className="lg:pl-64">
                <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                            aria-label="Ouvrir le menu"
                        >
                            <Menu className="h-6 w-6" />
                        </button>
                        {title && <h1 className="text-lg font-semibold text-slate-900">{title}</h1>}
                    </div>

                    <div className="flex items-center gap-3">
                        <Dropdown
                            trigger={
                                <button className="relative rounded-lg p-2 text-slate-700 hover:bg-slate-100">
                                    <BellRing className="h-5 w-5" />
                                    {unreadCount > 0 && (
                                        <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-600 px-1 text-[10px] font-semibold text-white">
                                            {unreadCount}
                                        </span>
                                    )}
                                </button>
                            }
                        >
                            <div className="w-80 max-w-[85vw] p-2">
                                <div className="mb-2 flex items-center justify-between border-b border-slate-100 pb-2">
                                    <p className="text-sm font-semibold text-slate-800">Notifications</p>
                                    {unreadCount > 0 && (
                                        <button
                                            type="button"
                                            onClick={() => router.post(route('notifications.read-all'))}
                                            className="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"
                                        >
                                            <CheckCheck className="h-3.5 w-3.5" /> Tout marquer comme lu
                                        </button>
                                    )}
                                </div>
                                {notifications.length === 0 ? (
                                    <p className="px-2 py-3 text-sm text-slate-500">Aucune notification récente.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {notifications.map((notification) => (
                                            <div
                                                key={notification.id}
                                                className={cn(
                                                    'rounded-lg border p-2 text-left',
                                                    notification.is_read ? 'border-slate-200 bg-slate-50' : 'border-emerald-200 bg-emerald-50'
                                                )}
                                            >
                                                <div className="flex items-start justify-between gap-2">
                                                    <p className="text-sm font-medium text-slate-800">{notification.title}</p>
                                                    {!notification.is_read && <span className="mt-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500" />}
                                                </div>
                                                <p className="mt-1 text-xs text-slate-600">{notification.message}</p>
                                                <div className="mt-2 flex items-center justify-between text-[11px] text-slate-500">
                                                    <span>{notification.type}</span>
                                                    <span>{notification.created_at}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                <div className="mt-2 border-t border-slate-100 pt-2 text-center">
                                    <Link href={route('notifications.index')} className="text-sm font-medium text-emerald-700 hover:text-emerald-800">
                                        Voir toutes les notifications
                                    </Link>
                                </div>
                            </div>
                        </Dropdown>

                        <Dropdown
                            trigger={
                                <button className="flex min-h-[44px] items-center gap-2 rounded-lg px-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span
                                        className="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold text-white"
                                        style={{ backgroundColor: 'var(--color-primary)' }}
                                    >
                                        {auth.user.name.slice(0, 1).toUpperCase()}
                                    </span>
                                    <span className="hidden sm:block">{auth.user.name}</span>
                                    <ChevronDown className="h-4 w-4 text-slate-400" />
                                </button>
                            }
                        >
                            <div className="border-b border-slate-100 px-4 py-2">
                                <p className="text-sm font-medium text-slate-800">{auth.user.name}</p>
                                <p className="text-xs text-slate-500">{auth.user.role_label}</p>
                            </div>
                            <Dropdown.Item onClick={logout}>
                                <LogOut className="h-4 w-4" /> Déconnexion
                            </Dropdown.Item>
                        </Dropdown>
                    </div>
                </header>

                <main className="p-4 sm:p-6">{children}</main>
            </div>

            <Toast toast={toast} onDismiss={() => setToast(null)} />
        </div>
    );
}

function SidebarContent({ nav, school, currentUrl, onNavigate }) {
    return (
        <>
            <div className="flex items-center gap-3 border-b border-slate-100 px-5 py-5">
                {school.logo_url ? (
                    <img src={school.logo_url} alt={school.name} className="h-10 w-10 shrink-0 object-contain" />
                ) : (
                    <div
                        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-sm font-bold text-white"
                        style={{ backgroundColor: 'var(--color-primary)' }}
                    >
                        {school.short_name?.slice(0, 2).toUpperCase() ?? 'EC'}
                    </div>
                )}
                <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-900">{school.short_name || school.name}</p>
                    <p className="truncate text-xs text-slate-500">{school.name}</p>
                </div>
            </div>
            <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                {nav.map((item) => {
                    const href = route(item.href);
                    const active = currentUrl.startsWith(new URL(href, window.location.origin).pathname);
                    const Icon = item.icon;

                    return (
                        <Link
                            key={item.href}
                            href={href}
                            onClick={onNavigate}
                            className={cn(
                                'flex min-h-[44px] items-center gap-3 rounded-lg px-3 text-sm font-medium transition',
                                active ? 'text-white' : 'text-slate-600 hover:bg-slate-100',
                            )}
                            style={active ? { backgroundColor: 'var(--color-primary)' } : undefined}
                        >
                            <Icon className="h-4.5 w-4.5 shrink-0" />
                            {item.label}
                        </Link>
                    );
                })}
            </nav>
        </>
    );
}
