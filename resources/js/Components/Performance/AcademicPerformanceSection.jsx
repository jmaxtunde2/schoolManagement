import {
    GraduationCap,
    TrendingUp,
    Users,
    AlertTriangle,
    Award,
    BookOpen,
    BarChart3,
} from 'lucide-react';

import Card from '@/Components/UI/Card';
import EmptyState from '@/Components/UI/EmptyState';

function StatTile({ label, value, icon: Icon, accent, valueClass = 'text-slate-900' }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className={`mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl ${accent}`}>
                <Icon className="h-5 w-5" />
            </div>
            <p className={`text-2xl font-bold tracking-tight ${valueClass}`}>{value}</p>
            <p className="mt-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
        </div>
    );
}

function BarList({ title, icon: Icon, accent, items, keyField, valueField, suffix = '/20' }) {
    const max = Math.max(20, ...items.map((item) => item[valueField]));
    const palette = ['bg-emerald-500', 'bg-sky-500', 'bg-violet-500', 'bg-amber-500', 'bg-rose-500'];

    return (
        <div>
            <p className="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
                <span className={`inline-flex h-7 w-7 items-center justify-center rounded-lg ${accent}`}>
                    <Icon className="h-4 w-4" />
                </span>
                {title}
            </p>

            {items.length === 0 ? (
                <p className="text-xs text-slate-400">Aucune donnée disponible.</p>
            ) : (
                <ul className="space-y-2.5">
                    {items.map((item, index) => (
                        <li key={`${item[keyField]}-${index}`}>
                            <div className="mb-1 flex items-center justify-between text-xs">
                                <span className="truncate font-medium text-slate-600">{item[keyField]}</span>
                                <span className="ml-2 shrink-0 font-bold text-slate-800">
                                    {item[valueField]}
                                    {suffix}
                                </span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    className={`h-full rounded-full ${palette[index % palette.length]}`}
                                    style={{ width: `${Math.min(100, (item[valueField] / max) * 100)}%` }}
                                />
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

export default function AcademicPerformanceSection({ academic }) {
    if (!academic) {
        return null;
    }

    const { overall } = academic;

    return (
        <section className="space-y-6">
            <div>
                <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">Scolarité</p>
                <h2 className="mt-1 text-xl font-bold tracking-tight text-slate-950">Performance scolaire</h2>
                <p className="mt-1 text-sm text-slate-500">
                    Moyennes par classe et par matière, taux de réussite, élèves en difficulté et évolution,
                    {academic.period_name ? ` sur ${academic.period_name}.` : ' sur la période en cours.'}
                </p>
                {academic.scope_note && (
                    <p className="mt-1 text-xs text-slate-400">{academic.scope_note}</p>
                )}
            </div>

            {!academic.available ? (
                <Card>
                    <Card.Body>
                        <EmptyState
                            title="Aucune note validée"
                            description="La performance scolaire s'affichera dès que des évaluations auront été validées."
                        />
                    </Card.Body>
                </Card>
            ) : (
                <>
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <StatTile
                            label="Moyenne générale"
                            value={overall.average !== null ? `${overall.average}/20` : '—'}
                            icon={GraduationCap}
                            accent="bg-emerald-50 text-emerald-600"
                            valueClass="text-emerald-700"
                        />
                        <StatTile
                            label="Taux de réussite"
                            value={overall.success_rate !== null ? `${overall.success_rate}%` : '—'}
                            icon={TrendingUp}
                            accent="bg-sky-50 text-sky-600"
                        />
                        <StatTile
                            label="Élèves évalués"
                            value={overall.evaluated}
                            icon={Users}
                            accent="bg-violet-50 text-violet-600"
                        />
                        <StatTile
                            label="Élèves en difficulté"
                            value={overall.struggling}
                            icon={AlertTriangle}
                            accent={overall.struggling > 0 ? 'bg-rose-50 text-rose-600' : 'bg-slate-100 text-slate-500'}
                            valueClass={overall.struggling > 0 ? 'text-rose-700' : 'text-slate-900'}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <Card>
                            <Card.Body>
                                <BarList
                                    title="Moyenne par classe"
                                    icon={Award}
                                    accent="bg-emerald-50 text-emerald-600"
                                    items={academic.by_class}
                                    keyField="class_name"
                                    valueField="average"
                                />
                            </Card.Body>
                        </Card>

                        <Card>
                            <Card.Body>
                                <BarList
                                    title="Moyenne par matière"
                                    icon={BookOpen}
                                    accent="bg-amber-50 text-amber-600"
                                    items={academic.by_subject}
                                    keyField="subject"
                                    valueField="average"
                                />
                            </Card.Body>
                        </Card>
                    </div>

                    {academic.top_students?.length > 0 && (
                        <Card className="overflow-hidden">
                            <Card.Header
                                title={
                                    <span className="flex items-center gap-2">
                                        <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                            <Award className="h-4 w-4" />
                                        </span>
                                        Palmarès — Meilleurs élèves
                                    </span>
                                }
                                description="Classement des élèves ayant la meilleure moyenne générale sur la période."
                            />
                            <Card.Body className="p-0">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                                <th className="px-6 py-3 font-semibold">Rang</th>
                                                <th className="px-6 py-3 font-semibold">Élève</th>
                                                <th className="px-6 py-3 font-semibold">Classe</th>
                                                <th className="px-6 py-3 font-semibold">Moyenne</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {academic.top_students.map((student, index) => (
                                                <tr key={`${student.student}-${index}`} className="hover:bg-slate-50">
                                                    <td className="px-6 py-3 font-bold text-slate-400">{index + 1}</td>
                                                    <td className="px-6 py-3 font-medium text-slate-800">{student.student}</td>
                                                    <td className="px-6 py-3 text-slate-600">{student.class}</td>
                                                    <td className="px-6 py-3 font-semibold text-emerald-700">{student.average}/20</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </Card.Body>
                        </Card>
                    )}

                    <Card className="overflow-hidden">
                        <Card.Header
                            title={
                                <span className="flex items-center gap-2">
                                    <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                        <BarChart3 className="h-4 w-4" />
                                    </span>
                                    Évolution &amp; comparaison des périodes
                                </span>
                            }
                        />
                        <Card.Body className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                            <th className="px-6 py-3 font-semibold">Période</th>
                                            <th className="px-6 py-3 font-semibold">Moyenne</th>
                                            <th className="px-6 py-3 font-semibold">Taux de réussite</th>
                                            <th className="px-6 py-3 font-semibold">Élèves évalués</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {academic.evolution.map((period) => (
                                            <tr key={period.id} className={period.is_current ? 'bg-emerald-50/50' : ''}>
                                                <td className="px-6 py-3 font-medium text-slate-800">
                                                    {period.name}
                                                    {period.is_current && (
                                                        <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                                            En cours
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-3 text-slate-700">
                                                    {period.average !== null ? `${period.average}/20` : '—'}
                                                </td>
                                                <td className="px-6 py-3 text-slate-700">
                                                    {period.success_rate !== null ? `${period.success_rate}%` : '—'}
                                                </td>
                                                <td className="px-6 py-3 text-slate-500">{period.evaluated}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Card.Body>
                    </Card>

                    {academic.struggling_students.length > 0 && (
                        <Card className="overflow-hidden">
                            <Card.Header
                                title="Élèves en difficulté"
                                description={`Moyenne inférieure à ${academic.threshold}/20 — priorités d'accompagnement.`}
                            />
                            <Card.Body className="p-0">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                                <th className="px-6 py-3 font-semibold">Élève</th>
                                                <th className="px-6 py-3 font-semibold">Classe</th>
                                                <th className="px-6 py-3 font-semibold">Moyenne</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {academic.struggling_students.map((student, index) => (
                                                <tr key={`${student.student}-${index}`}>
                                                    <td className="px-6 py-3 font-medium text-slate-800">{student.student}</td>
                                                    <td className="px-6 py-3 text-slate-600">{student.class}</td>
                                                    <td className="px-6 py-3 font-semibold text-rose-600">{student.average}/20</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </Card.Body>
                        </Card>
                    )}
                </>
            )}
        </section>
    );
}
