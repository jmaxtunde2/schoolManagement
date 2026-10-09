import { Head, Link } from '@inertiajs/react';
import {
    BadgeDollarSign,
    Bell,
    Building2,
    CheckCircle2,
    FileText,
    GraduationCap,
    Key,
    LayoutDashboard,
    Users,
    Wallet,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import StatCard from '@/Components/UI/StatCard';
import { cn } from '@/Utils/cn';

export default function Dashboard({ stats, schools }) {
    const quickActions = [
        { label: 'Demandes de démo', href: route('platform.demo-requests.index'), icon: FileText, color: 'from-blue-500 to-indigo-600' },
        { label: 'Gérer les écoles', href: route('platform.schools.index'), icon: Building2, color: 'from-green-500 to-teal-600' },
        { label: 'Licences', href: route('platform.licenses.index'), icon: Key, color: 'from-purple-500 to-indigo-600' },
        { label: 'Journaux d\'audit', href: route('platform.audit-logs.index'), icon: FileText, color: 'from-slate-600 to-slate-800' },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title="Plateforme Coriyase" />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 text-white shadow-lg mb-6">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <LayoutDashboard className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Tableau de bord plateforme
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                Vue d'ensemble de l'activité Coriyase sur tous les établissements.
                            </p>
                        </div>

                        <div className="flex items-center gap-3 rounded-2xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                <Building2 className="h-5 w-5" strokeWidth={2} />
                            </div>

                            <div>
                                <p className="text-xs text-white/70">
                                    Établissements
                                </p>

                                <p className="text-2xl font-bold">
                                    {stats.schools}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Statistiques principales */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-7 mb-6">
                <StatCard
                    icon={Building2}
                    label="Établissements"
                    value={stats.schools}
                    description="Total enregistré"
                    className="from-blue-500 to-indigo-600"
                />
                <StatCard
                    icon={CheckCircle2}
                    label="Actifs"
                    value={stats.active_schools}
                    description="Écoles accessibles"
                    className="from-emerald-500 to-teal-600"
                />
                <StatCard
                    icon={GraduationCap}
                    label="Élèves"
                    value={stats.students}
                    description="Sur la plateforme"
                    className="from-violet-500 to-purple-600"
                />
                <StatCard
                    icon={Users}
                    label="Payants"
                    value={stats.paid_contributions}
                    description="Contributions réglées"
                    className="from-cyan-500 to-blue-600"
                />
                <StatCard
                    icon={Wallet}
                    label="Collecté"
                    value={`${stats.collected.toLocaleString('fr-FR')} F`}
                    description="Total encaissé"
                    className="from-amber-500 to-orange-600"
                />
                <StatCard
                    icon={BadgeDollarSign}
                    label="Part Coriyase"
                    value={`${stats.coriyase_share.toLocaleString('fr-FR')} F`}
                    description="Revenus plateforme"
                    className="from-teal-500 to-cyan-600"
                />
                <StatCard
                    icon={Bell}
                    label="Notifications"
                    value={stats.notifications}
                    description="Envoyées globalement"
                    className="from-rose-500 to-pink-600"
                />
            </div>

            {/* Actions rapides */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4 mb-6">
                {quickActions.map(({ label, href, icon: Icon, color }) => (
                    <Link key={label} href={href}>
                        <div className={cn(
                            'relative overflow-hidden rounded-2xl p-5 transition-all hover:shadow-lg hover:-translate-y-1 group',
                            'bg-gradient-to-br',
                            color
                        )}>
                            <div className="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-white/10" />
                            <div className="relative z-10">
                                <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm group-hover:scale-110 transition-transform">
                                    <Icon className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <p className="font-semibold">{label}</p>
                            </div>
                        </div>
                    </Link>
                ))}
            </div>

            {/* Dernières écoles */}
            <Card>
                <Card.Header
                    title="Derniers établissements"
                    description={`${schools.length} établissement(s) — cliquez pour voir les détails`}
                />
                <Card.Body className="p-0">
                    <div className="divide-y divide-slate-100">
                        {schools.map((school) => (
                            <Link
                                key={school.id}
                                href={route('platform.schools.show', school.id)}
                                className="flex items-center justify-between p-4 hover:bg-slate-50 transition-colors"
                            >
                                <div className="flex items-center gap-4 min-w-0">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                        <Building2 className="h-5 w-5" strokeWidth={2} />
                                    </div>

                                    <div className="min-w-0">
                                        <p className="truncate font-medium text-slate-900">
                                            {school.name}
                                        </p>

                                        <p className="text-xs text-slate-500">
                                            {school.users_count}{' '}
                                            utilisateur(s)
                                        </p>
                                    </div>
                                </div>

                                <span
                                    className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ${
                                        school.active
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-500'
                                    }`}
                                >
                                    {school.active ? 'Actif' : 'Suspendu'}
                                </span>
                            </Link>
                        ))}
                    </div>
                </Card.Body>
            </Card>
        </AuthenticatedLayout>
    );
}