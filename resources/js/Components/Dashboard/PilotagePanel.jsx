import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    BookOpen,
    ClipboardCheck,
    GraduationCap,
    TrendingDown,
    TrendingUp,
    Users,
    Wallet,
    AlertTriangle,
    CalendarX,
} from 'lucide-react';

import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';

const money = (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F`;

function Metric({ label, value, tone = 'slate', hint }) {
    const tones = {
        slate: 'text-slate-900',
        green: 'text-emerald-700',
        amber: 'text-amber-600',
        red: 'text-rose-700',
        primary: 'text-primary',
    };

    return (
        <div className="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
            <p className={`text-xl font-bold tracking-tight ${tones[tone]}`}>
                {value}
            </p>
            <p className="mt-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </p>
            {hint && <p className="mt-1 text-xs text-slate-400">{hint}</p>}
        </div>
    );
}

function PanelCard({ icon: Icon, accent, title, description, href, children }) {
    return (
        <Card className="flex flex-col overflow-hidden">
            <Card.Header
                title={
                    <span className="flex items-center gap-2">
                        <span
                            className={`inline-flex h-8 w-8 items-center justify-center rounded-lg ${accent}`}
                        >
                            <Icon className="h-4 w-4" />
                        </span>
                        {title}
                    </span>
                }
                description={description}
                actions={
                    href ? (
                        <Link
                            href={route(href)}
                            className="inline-flex items-center gap-1.5 text-sm font-medium transition-opacity hover:opacity-80"
                            style={{ color: 'var(--color-primary)' }}
                        >
                            Détails
                            <ArrowRight className="h-4 w-4" />
                        </Link>
                    ) : null
                }
            />
            <Card.Body className="flex-1">{children}</Card.Body>
        </Card>
    );
}

export default function PilotagePanel({ pilotage }) {
    if (!pilotage) {
        return null;
    }

    const { effectifs, performance, administration, finance } = pilotage;

    return (
        <section className="mt-6">
            <div className="mb-4 flex items-end justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wider text-primary">
                        Pilotage
                    </p>
                    <h2 className="mt-1 text-lg font-bold tracking-tight text-slate-950">
                        Décision &amp; pilotage
                    </h2>
                </div>
                {pilotage.period && <Badge tone="blue">{pilotage.period}</Badge>}
            </div>

            {pilotage.scope_note && (
                <p className="-mt-2 mb-4 text-xs text-slate-500">
                    {pilotage.scope_note}
                </p>
            )}

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {/* Effectifs */}
                <PanelCard
                    icon={Users}
                    accent="bg-blue-50 text-blue-600"
                    title="Effectifs"
                    description="Population scolaire du périmètre."
                    href={effectifs.href}
                >
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <Metric
                            label="Élèves actifs"
                            value={effectifs.students}
                            tone="primary"
                        />
                        <Metric label="Filles" value={effectifs.girls} />
                        <Metric label="Garçons" value={effectifs.boys} />
                        <Metric label="Classes" value={effectifs.classes} />
                        <Metric label="Enseignants" value={effectifs.teachers} />
                    </div>
                </PanelCard>

                {/* Performance scolaire */}
                <PanelCard
                    icon={GraduationCap}
                    accent="bg-violet-50 text-violet-600"
                    title="Performance scolaire"
                    description="Résultats de la période en cours."
                    href={performance.href}
                >
                    {performance.available ? (
                        <>
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <Metric
                                    label="Moyenne"
                                    value={
                                        performance.average !== null
                                            ? `${performance.average}/20`
                                            : '—'
                                    }
                                    tone="primary"
                                />
                                <Metric
                                    label="Réussite"
                                    value={
                                        performance.success_rate !== null
                                            ? `${performance.success_rate}%`
                                            : '—'
                                    }
                                    tone="green"
                                />
                                <Metric
                                    label="En difficulté"
                                    value={performance.struggling}
                                    tone={
                                        performance.struggling > 0
                                            ? 'red'
                                            : 'slate'
                                    }
                                />
                                <Metric
                                    label="Élèves évalués"
                                    value={performance.evaluated}
                                />
                            </div>

                            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <p className="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <Award className="h-3.5 w-3.5 text-emerald-500" />
                                        Meilleures classes
                                    </p>
                                    {performance.best_classes.length === 0 ? (
                                        <p className="text-xs text-slate-400">
                                            Pas encore de classement.
                                        </p>
                                    ) : (
                                        <ul className="space-y-1">
                                            {performance.best_classes.map(
                                                (item) => (
                                                    <li
                                                        key={item.class_id}
                                                        className="flex items-center justify-between text-sm"
                                                    >
                                                        <span className="truncate text-slate-600">
                                                            {item.class_name}
                                                        </span>
                                                        <span className="ml-2 shrink-0 font-semibold text-emerald-700">
                                                            {item.average}/20
                                                        </span>
                                                    </li>
                                                )
                                            )}
                                        </ul>
                                    )}
                                </div>

                                <div>
                                    <p className="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <BookOpen className="h-3.5 w-3.5 text-rose-500" />
                                        Matières fragiles
                                    </p>
                                    {performance.weak_subjects.length ===
                                    0 ? (
                                        <p className="text-xs text-slate-400">
                                            Pas encore de données.
                                        </p>
                                    ) : (
                                        <ul className="space-y-1">
                                            {performance.weak_subjects.map(
                                                (item) => (
                                                    <li
                                                        key={item.subject}
                                                        className="flex items-center justify-between text-sm"
                                                    >
                                                        <span className="truncate text-slate-600">
                                                            {item.subject}
                                                        </span>
                                                        <span className="ml-2 shrink-0 font-semibold text-rose-600">
                                                            {item.average}/20
                                                        </span>
                                                    </li>
                                                )
                                            )}
                                        </ul>
                                    )}
                                </div>
                            </div>
                        </>
                    ) : (
                        <p className="text-sm text-slate-500">
                            Aucune note validée pour la période en cours.
                        </p>
                    )}
                </PanelCard>

                {/* Administration */}
                <PanelCard
                    icon={ClipboardCheck}
                    accent="bg-amber-50 text-amber-600"
                    title="Administration"
                    description="Points à traiter sans délai."
                    href={administration.href}
                >
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <Metric
                            label="Notes à saisir"
                            value={administration.pending_evaluations}
                            tone={
                                administration.pending_evaluations > 0
                                    ? 'amber'
                                    : 'slate'
                            }
                            hint="Évaluations"
                        />
                        <Metric
                            label="Enseignants concernés"
                            value={administration.pending_teachers}
                        />
                        <Metric
                            label="Bulletins à valider"
                            value={administration.report_cards_to_validate}
                            tone={
                                administration.report_cards_to_validate > 0
                                    ? 'amber'
                                    : 'slate'
                            }
                        />
                        <Metric
                            label="Absences répétées"
                            value={administration.important_absences}
                            tone={
                                administration.important_absences > 0
                                    ? 'red'
                                    : 'slate'
                            }
                            hint="≥ 3 ce mois"
                        />
                    </div>

                    <Link
                        href={route(administration.attendance_href)}
                        className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-rose-600 hover:opacity-80"
                    >
                        <CalendarX className="h-4 w-4" />
                        Voir l'assiduité
                    </Link>
                </PanelCard>

                {/* Finance */}
                {finance && (
                    <PanelCard
                        icon={Wallet}
                        accent="bg-emerald-50 text-emerald-600"
                        title="Finance"
                        description="Situation de l'exercice en cours."
                        href={finance.href}
                    >
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <Metric
                                label="Recettes"
                                value={money(finance.revenue)}
                                tone="green"
                            />
                            <Metric
                                label="Dépenses"
                                value={money(finance.expenses)}
                                tone="red"
                            />
                            <Metric
                                label="Solde"
                                value={money(finance.balance)}
                                tone={finance.balance >= 0 ? 'green' : 'red'}
                            />
                            <Metric
                                label="Impayés"
                                value={money(finance.outstanding)}
                                tone={
                                    finance.outstanding > 0 ? 'amber' : 'slate'
                                }
                            />
                        </div>

                        <div className="mt-4 flex items-center gap-4 text-xs text-slate-500">
                            <span className="inline-flex items-center gap-1.5">
                                <TrendingUp className="h-3.5 w-3.5 text-emerald-500" />
                                Recettes encaissées
                            </span>
                            <span className="inline-flex items-center gap-1.5">
                                <TrendingDown className="h-3.5 w-3.5 text-rose-500" />
                                Charges de l'exercice
                            </span>
                            {finance.outstanding > 0 && (
                                <span className="inline-flex items-center gap-1.5 text-amber-600">
                                    <AlertTriangle className="h-3.5 w-3.5" />
                                    Reste à recouvrer
                                </span>
                            )}
                        </div>
                    </PanelCard>
                )}
            </div>
        </section>
    );
}
