import { Head } from '@inertiajs/react';
import {
    Banknote,
    Building2,
    CreditCard,
    Info,
    MessageSquare,
    Receipt,
    Settings2,
    Smartphone,
    Users,
    WalletCards,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';

export default function Billing({ settings, summary }) {
    const money = (value) =>
        `${Number(value ?? 0).toLocaleString('fr-FR')} F`;

    return (
        <AuthenticatedLayout title="Services numériques & contributions">
            <Head title="Facturation" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-emerald-100">
                                <WalletCards className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Gestion commerciale
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Services numériques & contributions
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-emerald-100 sm:text-base">
                                Suivez les contributions des élèves et
                                visualisez la répartition financière
                                appliquée à votre établissement.
                            </p>
                        </div>

                        <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-sm">
                            <Banknote className="h-8 w-8" />
                        </div>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                {/* Statistiques */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={Users}
                        label="Élèves actifs"
                        value={summary.students}
                        description="Élèves actuellement concernés"
                        className="from-blue-500 to-indigo-600"
                    />

                    <StatCard
                        icon={CreditCard}
                        label="Contributions payées"
                        value={summary.paid_contributions}
                        description="Contributions enregistrées"
                        className="from-emerald-500 to-teal-600"
                    />

                    <StatCard
                        icon={Banknote}
                        label="Total collecté"
                        value={money(summary.collected)}
                        description="Contributions encaissées"
                        className="from-violet-500 to-purple-600"
                    />

                    <StatCard
                        icon={Building2}
                        label="Part établissement"
                        value={money(summary.school_share)}
                        description="Revenu attribué à l'établissement"
                        className="from-amber-500 to-orange-600"
                    />
                </div>

                {/* Répartition */}
                <Card>
                    <Card.Header
                        title="Paramètres commerciaux"
                        description="Valeurs appliquées à cet établissement. La configuration commerciale globale reste contrôlée par Coriyase."
                    />

                    <Card.Body>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Item
                                icon={Receipt}
                                label="Frais d'installation"
                                value={money(
                                    settings.installation_fee
                                )}
                                tone="blue"
                            />

                            <Item
                                icon={CreditCard}
                                label="Contribution annuelle / élève"
                                value={money(
                                    settings.annual_student_fee
                                )}
                                tone="emerald"
                            />

                            <Item
                                icon={WalletCards}
                                label="Part Coriyase"
                                value={money(
                                    settings.coriyase_share
                                )}
                                tone="violet"
                            />

                            <Item
                                icon={Building2}
                                label="Part établissement"
                                value={money(
                                    settings.school_share
                                )}
                                tone="amber"
                            />

                            <Item
                                icon={MessageSquare}
                                label="SMS inclus / élève payant"
                                value={
                                    settings.included_sms_per_paid_student
                                }
                                suffix="SMS"
                                tone="cyan"
                            />

                            <Item
                                icon={Smartphone}
                                label="SMS inclus estimés"
                                value={summary.included_sms}
                                suffix="SMS"
                                tone="rose"
                            />
                        </div>
                    </Card.Body>
                </Card>

                {/* Information */}
                <div className="rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 to-cyan-50 p-5">
                    <div className="flex items-start gap-4">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <Info className="h-5 w-5" />
                        </div>

                        <div>
                            <h3 className="font-semibold text-slate-800">
                                Configuration commerciale
                            </h3>

                            <p className="mt-1 text-sm leading-6 text-slate-600">
                                Les montants et règles commerciales
                                affichés ici sont définis au niveau de
                                la plateforme. L'établissement peut
                                consulter les informations qui lui sont
                                applicables, tandis que la configuration
                                commerciale globale reste administrée
                                par Coriyase.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
    description,
    className,
}) {
    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${className} p-5 text-white shadow-md`}
        >
            <div className="relative z-10">
                <div className="mb-4 flex items-center justify-between">
                    <div className="rounded-xl bg-white/15 p-2.5 backdrop-blur-sm">
                        <Icon className="h-5 w-5" />
                    </div>

                    <span className="text-right text-2xl font-bold">
                        {value}
                    </span>
                </div>

                <p className="font-semibold">
                    {label}
                </p>

                <p className="mt-1 text-xs text-white/75">
                    {description}
                </p>
            </div>

            <div className="absolute -bottom-10 -right-10 h-28 w-28 rounded-full bg-white/10" />
        </div>
    );
}

function Item({
    icon: Icon,
    label,
    value,
    suffix,
    tone = 'blue',
}) {
    const tones = {
        blue: {
            wrapper: 'border-blue-100 bg-blue-50/60',
            icon: 'bg-blue-100 text-blue-600',
        },
        emerald: {
            wrapper: 'border-emerald-100 bg-emerald-50/60',
            icon: 'bg-emerald-100 text-emerald-600',
        },
        violet: {
            wrapper: 'border-violet-100 bg-violet-50/60',
            icon: 'bg-violet-100 text-violet-600',
        },
        amber: {
            wrapper: 'border-amber-100 bg-amber-50/60',
            icon: 'bg-amber-100 text-amber-600',
        },
        cyan: {
            wrapper: 'border-cyan-100 bg-cyan-50/60',
            icon: 'bg-cyan-100 text-cyan-600',
        },
        rose: {
            wrapper: 'border-rose-100 bg-rose-50/60',
            icon: 'bg-rose-100 text-rose-600',
        },
    };

    const currentTone = tones[tone] ?? tones.blue;

    return (
        <div
            className={`rounded-2xl border p-4 ${currentTone.wrapper}`}
        >
            <div className="flex items-start gap-3">
                <div
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${currentTone.icon}`}
                >
                    <Icon className="h-5 w-5" />
                </div>

                <div className="min-w-0">
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-400">
                        {label}
                    </p>

                    <div className="mt-1 flex items-baseline gap-2">
                        <p className="text-lg font-bold text-slate-800">
                            {value}
                        </p>

                        {suffix && (
                            <span className="text-xs font-medium text-slate-400">
                                {suffix}
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}