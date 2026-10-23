import { Head, Link, usePage } from '@inertiajs/react';
import {
    GraduationCap,
    Users,
    School as SchoolIcon,
    ClipboardList,
    BellRing,
    AlertTriangle,
    Eye,
    ArrowRight,
    Wallet,
    ShoppingCart,
    HandCoins,
    PieChart,
    TrendingUp,
    TrendingDown,
    PiggyBank,
    Percent,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';
import ExecutionBrief from '@/Components/Timetable/ExecutionBrief';
import RecentIncidents from '@/Components/Attendance/RecentIncidents';
import StudentGenderChart from '@/Components/Students/StudentGenderChart';
import PilotagePanel from '@/Components/Dashboard/PilotagePanel';

const statusTones = {
    draft: 'slate',
    in_progress: 'amber',
    validated: 'green',
    returned: 'red',
};

const statusLabels = {
    draft: 'Brouillon',
    in_progress: 'En cours de validation',
    validated: 'Validée',
    returned: 'Retournée pour correction',
};

const expenseTones = { pending: 'amber', approved: 'green', rejected: 'red' };
const contributionTones = { paid: 'green', partial: 'amber', pending: 'red' };

const money = (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F`;

export default function Dashboard({
    stats,
    recentEvaluations,
    timetableBrief,
    recentIncidents,
    routePrefix = 'admin',
    finance = false,
    financeSummary = null,
    recentExpenses = [],
    recentSales = [],
    recentContributions = [],
    genderDistribution = null,
    pilotage = null,
}) {
    const { auth } = usePage().props;

    const cards = [
        {
            label: 'Élèves',
            value: stats.students,
            icon: GraduationCap,
            cardClass:
                'bg-gradient-to-br from-blue-500 to-blue-600',
        },
        {
            label: 'Enseignants',
            value: stats.teachers,
            icon: Users,
            cardClass:
                'bg-gradient-to-br from-violet-500 to-purple-600',
        },
        {
            label: 'Classes',
            value: stats.classes,
            icon: SchoolIcon,
            cardClass:
                'bg-gradient-to-br from-emerald-500 to-green-600',
        },
        {
            label: 'Évaluations',
            value: stats.evaluations,
            icon: ClipboardList,
            cardClass:
                'bg-gradient-to-br from-amber-400 to-orange-500',
        },
        {
            label: 'SMS envoyés',
            value: stats.notifications_sent,
            icon: BellRing,
            cardClass:
                'bg-gradient-to-br from-cyan-500 to-teal-600',
        },
        {
            label: 'SMS en échec',
            value: stats.notifications_failed,
            icon: AlertTriangle,
            cardClass:
                stats.notifications_failed > 0
                    ? 'bg-gradient-to-br from-red-500 to-rose-600'
                    : 'bg-gradient-to-br from-slate-400 to-slate-500',
        },
    ];

    const recentEvaluationsCard = (
        <Card className="overflow-hidden">
            <Card.Header
                title="Évaluations récentes"
                actions={
                    <Link
                        href={route(
                            `${routePrefix}.evaluations.index`
                        )}
                        className="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-80"
                        style={{
                            color: 'var(--color-primary)',
                        }}
                    >
                        Voir tout
                        <ArrowRight className="h-4 w-4" />
                    </Link>
                }
            />

            <Card.Body className="p-0">
                {recentEvaluations.length === 0 ? (
                    <div className="p-6">
                        <EmptyState
                            title="Aucune évaluation pour l'instant"
                            description="Les évaluations créées par les enseignants apparaîtront ici."
                        />
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {recentEvaluations.map((evaluation) => (
                            <li key={evaluation.id}>
                                <Link
                                    href={route(
                                        `${routePrefix}.evaluations.grades`,
                                        evaluation.id
                                    )}
                                    className="group flex items-center justify-between gap-4 px-6 py-4 transition-colors hover:bg-slate-50"
                                >
                                    {/* Evaluation information */}
                                    <div className="flex min-w-0 items-center gap-4">
                                        {/* Icon */}
                                        <div className="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:flex">
                                            <ClipboardList className="h-5 w-5" />
                                        </div>

                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <p className="truncate text-sm font-semibold text-slate-800 group-hover:text-primary">
                                                    {evaluation.title}
                                                </p>
                                            </div>

                                            <p className="mt-1 truncate text-xs text-slate-500">
                                                {evaluation.subject.name}
                                                {' · '}
                                                {evaluation.class_room.name}
                                                {' · '}
                                                {evaluation.teacher?.user?.name ?? 'Saisie par l’administration'}
                                                {' · '}
                                                {evaluation.evaluation_date}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Status + consultation */}
                                    <div className="flex shrink-0 items-center gap-3">
                                        <Badge
                                            tone={
                                                statusTones[
                                                    evaluation.status
                                                ] ?? 'slate'
                                            }
                                        >
                                            {statusLabels[
                                                evaluation.status
                                            ] ?? evaluation.status}
                                        </Badge>

                                        <span className="hidden items-center gap-1 text-xs font-medium text-slate-500 transition-colors group-hover:text-primary sm:flex">
                                            <Eye className="h-4 w-4" />
                                            Consulter
                                        </span>

                                        <ArrowRight className="h-4 w-4 text-slate-300 transition-all group-hover:translate-x-1 group-hover:text-primary" />
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </Card.Body>
        </Card>
    );

    const summary = financeSummary ?? {
        revenue: 0,
        expenses: 0,
        profit: 0,
        margin: 0,
    };
    const profitIsPositive = summary.profit >= 0;
    const signedMoney = (value) =>
        `${value >= 0 ? '+ ' : '- '}${money(Math.abs(value))}`;

    const financeTiles = [
        {
            label: 'Recettes',
            value: money(summary.revenue),
            hint: 'Ventes et scolarité encaissée',
            icon: TrendingUp,
            accent: 'bg-emerald-50 text-emerald-600',
            valueClass: 'text-emerald-700',
        },
        {
            label: 'Dépenses',
            value: money(summary.expenses),
            hint: 'Charges validées sur la période',
            icon: TrendingDown,
            accent: 'bg-rose-50 text-rose-600',
            valueClass: 'text-rose-700',
        },
        {
            label: 'Bénéfice',
            value: signedMoney(summary.profit),
            hint: 'Recettes − dépenses',
            icon: PiggyBank,
            accent: profitIsPositive
                ? 'bg-emerald-50 text-emerald-600'
                : 'bg-rose-50 text-rose-600',
            valueClass: profitIsPositive ? 'text-emerald-700' : 'text-rose-700',
        },
        {
            label: 'Marge bénéficiaire',
            value: `${summary.margin} %`,
            hint: 'Part du bénéfice dans les recettes',
            icon: Percent,
            accent: 'bg-sky-50 text-sky-600',
            valueClass:
                summary.margin >= 15
                    ? 'text-emerald-700'
                    : summary.margin >= 0
                      ? 'text-slate-900'
                      : 'text-rose-700',
        },
    ];

    return (
        <AuthenticatedLayout
            title={`Bonjour, ${auth.user.name.split(' ')[0]}`}
        >
            <Head title="Tableau de bord" />

            {/* Statistics */}
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                {cards.map((card) => {
                    const Icon = card.icon;

                    return (
                        <Card
                            key={card.label}
                            className={`group relative overflow-hidden border-0 p-5 text-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg ${card.cardClass}`}
                        >
                            {/* Decorative background */}
                            <div className="absolute -right-5 -top-5 h-20 w-20 rounded-full bg-white/10" />
                            <div className="absolute -bottom-8 -left-5 h-24 w-24 rounded-full bg-white/5" />

                            <div className="relative">
                                <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 shadow-sm backdrop-blur-sm">
                                    <Icon
                                        className="h-6 w-6 text-white"
                                        strokeWidth={2}
                                    />
                                </div>

                                <p className="text-3xl font-bold tracking-tight">
                                    {card.value}
                                </p>

                                <p className="mt-1 text-sm font-medium text-white/80">
                                    {card.label}
                                </p>
                            </div>
                        </Card>
                    );
                })}
            </div>

            {/* Timetable execution brief */}
            <ExecutionBrief brief={timetableBrief} routePrefix={routePrefix} />

            {/* Decision & pilotage */}
            <PilotagePanel pilotage={pilotage} />

            {/* Latest delays & absences + recent evaluations */}
                {finance ? (
                    <RecentIncidents
                        incidents={recentIncidents}
                        routePrefix={routePrefix}
                    />
                ) : (
                    <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <RecentIncidents
                            incidents={recentIncidents}
                            routePrefix={routePrefix}
                            className=""
                        />

                        {recentEvaluationsCard}
                    </div>
                )}

                {/* Recent finance */}
                {finance && (
                    <section className="mt-6">
                        <div className="mb-4 flex items-end justify-between">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-wider text-violet-600">Gestion financière</p>
                                <h2 className="mt-1 text-lg font-bold tracking-tight text-slate-950">Bénéfice</h2>
                            </div>
                            <Link
                                href={route(`${routePrefix}.expenses.index`)}
                                className="inline-flex items-center gap-1.5 text-sm font-medium hover:opacity-80"
                                style={{ color: 'var(--color-primary)' }}
                            >
                                Ouvrir la finance
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </div>

                        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                            {financeTiles.map((tile) => (
                                <FinanceTile key={tile.label} {...tile} />
                            ))}
                        </div>

                        <h3 className="mb-4 mt-8 text-lg font-bold tracking-tight text-slate-950">Finances récentes</h3>

                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <FinanceCard
                                title="Dépenses récentes"
                                icon={Wallet}
                                href={route(`${routePrefix}.expenses.index`)}
                                items={recentExpenses}
                                emptyTitle="Aucune dépense enregistrée"
                                renderItem={(expense) => (
                                    <>
                                        <div className="flex min-w-0 items-center gap-4">
                                            <div className="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 sm:flex">
                                                <Wallet className="h-5 w-5" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold text-slate-800">{expense.label}</p>
                                                <p className="mt-0.5 truncate text-xs text-slate-500">
                                                    {expense.category_label} · {expense.expense_date}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-3">
                                            <Badge tone={expenseTones[expense.status] ?? 'slate'}>{expense.status_label}</Badge>
                                            <span className="whitespace-nowrap text-sm font-bold text-slate-800">{money(expense.amount)}</span>
                                        </div>
                                    </>
                                )}
                            />

                            <FinanceCard
                                title="Ventes récentes"
                                icon={ShoppingCart}
                                href={route(`${routePrefix}.sales.index`)}
                                items={recentSales}
                                emptyTitle="Aucune vente enregistrée"
                                renderItem={(sale) => (
                                    <>
                                        <div className="flex min-w-0 items-center gap-4">
                                            <div className="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 sm:flex">
                                                <ShoppingCart className="h-5 w-5" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold text-slate-800">{sale.item}</p>
                                                <p className="mt-0.5 truncate text-xs text-slate-500">
                                                    {sale.category_label} · {sale.quantity} article(s) · {sale.sold_at}
                                                </p>
                                            </div>
                                        </div>
                                        <span className="whitespace-nowrap text-sm font-bold text-emerald-700">{money(sale.total)}</span>
                                    </>
                                )}
                            />

                            <FinanceCard
                                title="Contributions récentes"
                                icon={HandCoins}
                                href={route(`${routePrefix}.contributions.index`)}
                                items={recentContributions}
                                emptyTitle="Aucune contribution enregistrée"
                                renderItem={(contribution) => (
                                    <>
                                        <div className="flex min-w-0 items-center gap-4">
                                            <div className="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 sm:flex">
                                                <HandCoins className="h-5 w-5" />
                                            </div>
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold text-slate-800">{contribution.student}</p>
                                                <p className="mt-0.5 truncate text-xs text-slate-500">{contribution.academic_year}</p>
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-3">
                                            <Badge tone={contributionTones[contribution.status] ?? 'slate'}>
                                                {contribution.status === 'paid' ? 'Payée' : contribution.status === 'partial' ? 'Partielle' : 'En attente'}
                                            </Badge>
                                            <span className="whitespace-nowrap text-sm font-bold text-slate-800">
                                                {money(contribution.amount_paid)}<span className="font-normal text-slate-400"> / {money(contribution.amount_due)}</span>
                                            </span>
                                        </div>
                                    </>
                                )}
                            />

                            {recentEvaluationsCard}
                        </div>
                    </section>
                )}

            {/* Gender distribution */}
            {genderDistribution && (
                <Card className="mt-6 overflow-hidden">
                    <Card.Header
                        title="Répartition des élèves"
                        description="Filles / garçons par classe, pour tout l’établissement."
                        actions={
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-pink-100 px-3 py-1.5 text-sm font-bold text-pink-700">
                                <PieChart className="h-4 w-4" />
                                {genderDistribution.totals.students} élève
                                {genderDistribution.totals.students > 1
                                    ? 's'
                                    : ''}
                            </span>
                        }
                    />

                    <Card.Body className="p-5 sm:p-6">
                        <StudentGenderChart
                            distribution={genderDistribution}
                        />
                    </Card.Body>
                </Card>
            )}
        </AuthenticatedLayout>
    );
}

function FinanceTile({ label, value, hint, icon: Icon, accent, valueClass = 'text-slate-900' }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className={`mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl ${accent}`}>
                <Icon className="h-5 w-5" />
            </div>
            <p className={`text-lg font-bold tracking-tight ${valueClass}`}>{value}</p>
            <p className="mt-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
            <p className="mt-1 text-xs text-slate-400">{hint}</p>
        </div>
    );
}

function FinanceCard({ title, icon: Icon, href, items, emptyTitle, renderItem, className = '' }) {
    return (
        <Card className={`overflow-hidden ${className}`}>
            <Card.Header
                title={title}
                actions={
                    <Link
                        href={href}
                        className="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-80"
                        style={{ color: 'var(--color-primary)' }}
                    >
                        Voir tout
                        <ArrowRight className="h-4 w-4" />
                    </Link>
                }
            />
            <Card.Body className="p-0">
                {items.length === 0 ? (
                    <div className="p-6">
                        <EmptyState title={emptyTitle} description="Les dernières opérations apparaîtront ici." />
                    </div>
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {items.map((item, index) => (
                            <li key={`${item.id}-${index}`}>
                                <Link
                                    href={href}
                                    className="group flex items-center justify-between gap-4 px-6 py-4 transition-colors hover:bg-slate-50"
                                >
                                    {renderItem(item)}
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </Card.Body>
        </Card>
    );
}
