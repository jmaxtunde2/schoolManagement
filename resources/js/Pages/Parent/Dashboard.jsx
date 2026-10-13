import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    BookOpenCheck,
    CalendarDays,
    CalendarRange,
    GraduationCap,
    HandCoins,
    Mail,
    MessageSquarePlus,
    Wallet,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';

const feeTones = { paid: 'green', partial: 'amber', pending: 'red' };
const feeLabels = { paid: 'Soldé', partial: 'Partiel', pending: 'En attente' };

function money(value) {
    return `${Number(value ?? 0).toLocaleString('fr-FR')} FCFA`;
}

export default function Dashboard({ parent, children = [], stats = {}, recentMessages = [] }) {
    return (
        <AuthenticatedLayout title="Espace parent">
            <Head title="Espace parent" />

            <div className="mx-auto max-w-6xl space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 p-6 text-white shadow-sm sm:p-8">
                    <div className="absolute -right-10 -top-12 h-44 w-44 rounded-full bg-white/10" />
                    <div className="absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-white/5" />

                    <div className="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-xs font-bold uppercase tracking-wider text-emerald-100">
                                Espace parent
                            </p>
                            <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
                                Bonjour {parent.name}
                            </h1>
                            <p className="mt-2 max-w-xl text-sm text-emerald-50/90">
                                Suivez la scolarité de vos enfants : résultats, assiduité,
                                bulletins et paiements — le tout au même endroit.
                            </p>
                        </div>

                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Link
                                href={route('parent.finance.index')}
                                className="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20"
                            >
                                <Wallet className="h-4 w-4" /> Ma scolarité
                            </Link>
                            <Link
                                href={route('parent.messages.index')}
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50"
                            >
                                <MessageSquarePlus className="h-4 w-4" /> Contacter l'école
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatTile
                        icon={GraduationCap}
                        label="Enfants inscrits"
                        value={stats.children ?? children.length}
                        tone="from-emerald-500 to-teal-600"
                    />
                    <StatTile
                        icon={HandCoins}
                        label="Total dû"
                        value={money(stats.due)}
                        tone="from-sky-500 to-indigo-600"
                    />
                    <StatTile
                        icon={Wallet}
                        label="Total payé"
                        value={money(stats.paid)}
                        tone="from-teal-500 to-emerald-600"
                    />
                    <StatTile
                        icon={BookOpenCheck}
                        label="Reste à payer"
                        value={money(stats.remaining)}
                        tone={stats.remaining > 0 ? 'from-amber-500 to-orange-600' : 'from-emerald-500 to-teal-600'}
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Children */}
                    <div className="space-y-4 lg:col-span-2">
                        <h2 className="text-sm font-bold uppercase tracking-wider text-slate-500">
                            Mes enfants
                        </h2>

                        {children.length === 0 ? (
                            <Card className="p-10 text-center">
                                <GraduationCap className="mx-auto h-9 w-9 text-slate-300" />
                                <p className="mt-3 font-semibold text-slate-800">Aucun enfant rattaché</p>
                                <p className="mt-1 text-sm text-slate-500">
                                    Contactez l'établissement pour associer vos enfants à votre compte.
                                </p>
                            </Card>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2">
                                {children.map((child) => (
                                    <Card key={child.id} className="flex flex-col overflow-hidden">
                                        <div className="flex items-start gap-3 p-5">
                                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
                                                <GraduationCap className="h-6 w-6" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-start justify-between gap-2">
                                                    <p className="truncate font-bold text-slate-900">{child.name}</p>
                                                    {child.fee_status && (
                                                        <Badge tone={feeTones[child.fee_status]}>
                                                            {feeLabels[child.fee_status]}
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="mt-0.5 text-sm text-slate-500">
                                                    {child.class_name || 'Classe non définie'}
                                                </p>
                                                {child.matricule && (
                                                    <p className="mt-1 inline-block rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                                        {child.matricule}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        {child.fee_status && child.fee_remaining > 0 && (
                                            <div className="mx-5 mb-4 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                                                Reste à payer : {money(child.fee_remaining)}
                                            </div>
                                        )}

                                        <div className="mt-auto flex flex-wrap gap-2 border-t border-slate-100 p-4">
                                            <Link
                                                href={route('parent.children.show', child.id)}
                                                className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                            >
                                                Dossier <ArrowRight className="h-3.5 w-3.5" />
                                            </Link>
                                            <ChildLink href={route('parent.children.attendance', child.id)} icon={CalendarDays} label="Assiduité" />
                                            <ChildLink href={route('parent.children.report-cards', child.id)} icon={Award} label="Bulletins" />
                                            <ChildLink href={route('parent.children.timetable', child.id)} icon={CalendarRange} label="Emploi du temps" />
                                        </div>
                                    </Card>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Side panel */}
                    <div className="space-y-4">
                        <h2 className="text-sm font-bold uppercase tracking-wider text-slate-500">
                            Activité récente
                        </h2>

                        <Card>
                            <Card.Header
                                title="Mes messages"
                                description={`${stats.unread_messages ?? 0} réponse(s) non lue(s)`}
                                actions={
                                    <Link
                                        href={route('parent.messages.index')}
                                        className="text-xs font-semibold text-teal-700 hover:text-teal-800"
                                    >
                                        Voir
                                    </Link>
                                }
                            />
                            <Card.Body className="space-y-3">
                                {recentMessages.length === 0 ? (
                                    <div className="text-center">
                                        <Mail className="mx-auto h-7 w-7 text-slate-300" />
                                        <p className="mt-2 text-sm text-slate-500">
                                            Aucun message envoyé pour l'instant.
                                        </p>
                                        <Link
                                            href={route('parent.messages.index')}
                                            className="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 hover:text-teal-800"
                                        >
                                            <MessageSquarePlus className="h-4 w-4" /> Écrire à l'établissement
                                        </Link>
                                    </div>
                                ) : (
                                    recentMessages.map((message) => (
                                        <div
                                            key={message.id}
                                            className="flex items-start justify-between gap-3 rounded-lg border border-slate-100 p-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold text-slate-800">
                                                    {message.subject}
                                                </p>
                                                <p className="mt-0.5 text-xs text-slate-500">{message.created_at}</p>
                                            </div>
                                            <Badge tone={message.is_read ? 'green' : 'amber'}>
                                                {message.is_read ? 'Lu' : 'Envoyé'}
                                            </Badge>
                                        </div>
                                    ))
                                )}
                            </Card.Body>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function StatTile({ icon: Icon, label, value, tone }) {
    return (
        <div className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${tone} p-5 text-white shadow-sm`}>
            <div className="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-white/10" />
            <div className="relative">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                    <Icon className="h-5 w-5" />
                </div>
                <p className="mt-3 text-lg font-bold leading-tight">{value}</p>
                <p className="mt-0.5 text-xs text-white/80">{label}</p>
            </div>
        </div>
    );
}

function ChildLink({ href, icon: Icon, label }) {
    return (
        <Link
            href={href}
            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700"
        >
            <Icon className="h-3.5 w-3.5" /> {label}
        </Link>
    );
}
