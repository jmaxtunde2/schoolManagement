import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Award,
    BookOpenCheck,
    CalendarDays,
    ClipboardList,
    GraduationCap,
    UserRound,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';

export default function Child({ student, results = [] }) {
    return (
        <AuthenticatedLayout title={student.name}>
            <Head title={student.name} />

            <div className="mx-auto max-w-6xl space-y-6">

                {/* Header */}
                <div className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                            <GraduationCap className="h-7 w-7" />
                        </div>

                        <div>
                            <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">
                                Espace parent
                            </p>

                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                {student.name}
                            </h1>

                            <div className="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                <span>
                                    {student.class_name ||
                                        'Classe non définie'}
                                </span>

                                {student.matricule && (
                                    <>
                                        <span className="text-slate-300">
                                            •
                                        </span>

                                        <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                            {student.matricule}
                                        </span>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Quick actions */}
                    <div className="grid grid-cols-2 gap-2 sm:flex">
                        <Link
                            href={route(
                                'parent.children.attendance',
                                student.id
                            )}
                            className="group inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700"
                        >
                            <CalendarDays className="h-4 w-4" />
                            <span>Assiduité</span>
                            <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        </Link>

                        <Link
                            href={route(
                                'parent.children.report-cards',
                                student.id
                            )}
                            className="group inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            <Award className="h-4 w-4" />
                            <span>Bulletins</span>
                            <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                        </Link>
                    </div>
                </div>

                {/* Summary */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <SummaryCard
                        icon={ClipboardList}
                        label="Évaluations"
                        value={results.length}
                        gradient="from-emerald-500 to-teal-600"
                    />

                    <SummaryCard
                        icon={BookOpenCheck}
                        label="Notes disponibles"
                        value={
                            results.filter(
                                (result) => result.score !== null
                            ).length
                        }
                        gradient="from-blue-500 to-indigo-600"
                    />

                    <SummaryCard
                        icon={GraduationCap}
                        label="Matières"
                        value={
                            new Set(
                                results.map(
                                    (result) => result.subject
                                )
                            ).size
                        }
                        gradient="from-violet-500 to-purple-600"
                    />
                </div>

                {/* Results */}
                <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                    <Card.Header
                        title={
                            <div className="flex items-center gap-2">
                                <span>Résultats scolaires</span>

                                <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                    {results.length} résultat(s)
                                </span>
                            </div>
                        }
                        description="Consultez les notes obtenues aux différentes évaluations."
                    />

                    <Card.Body className="p-0">
                        {results.length === 0 ? (
                            <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <BookOpenCheck className="h-8 w-8" />
                                </div>

                                <h3 className="mt-5 text-lg font-bold text-slate-900">
                                    Aucun résultat disponible
                                </h3>

                                <p className="mt-2 max-w-md text-sm text-slate-500">
                                    Les résultats de cet élève apparaîtront
                                    ici lorsqu’ils seront disponibles.
                                </p>
                            </div>
                        ) : (
                            <>
                                {/* Desktop table */}
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-slate-50/80">
                                            <tr className="border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                                <th className="px-6 py-4">
                                                    Matière
                                                </th>

                                                <th className="px-6 py-4">
                                                    Évaluation
                                                </th>

                                                <th className="px-6 py-4">
                                                    Période
                                                </th>

                                                <th className="px-6 py-4 text-right">
                                                    Note
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-slate-100">
                                            {results.map((result) => (
                                                <tr
                                                    key={result.id}
                                                    className="group transition hover:bg-slate-50/70"
                                                >
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                                                <BookOpenCheck className="h-4 w-4" />
                                                            </div>

                                                            <span className="font-semibold text-slate-800">
                                                                {
                                                                    result.subject
                                                                }
                                                            </span>
                                                        </div>
                                                    </td>

                                                    <td className="px-6 py-4">
                                                        <div>
                                                            <p className="font-medium text-slate-800">
                                                                {
                                                                    result.title
                                                                }
                                                            </p>

                                                            <p className="mt-0.5 text-xs text-slate-400">
                                                                Évaluation
                                                            </p>
                                                        </div>
                                                    </td>

                                                    <td className="px-6 py-4">
                                                        {result.period ? (
                                                            <span className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-600">
                                                                <CalendarDays className="h-3.5 w-3.5" />
                                                                {
                                                                    result.period
                                                                }
                                                            </span>
                                                        ) : (
                                                            <span className="text-slate-300">
                                                                —
                                                            </span>
                                                        )}
                                                    </td>

                                                    <td className="px-6 py-4 text-right">
                                                        {result.score !==
                                                        null ? (
                                                            <span className="inline-flex rounded-xl bg-emerald-50 px-3 py-1.5 font-bold text-emerald-700">
                                                                {result.score} /{' '}
                                                                {
                                                                    result.max_score
                                                                }
                                                            </span>
                                                        ) : (
                                                            <Badge tone="slate">
                                                                Non renseignée
                                                            </Badge>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Mobile cards */}
                                <div className="divide-y divide-slate-100 md:hidden">
                                    {results.map((result) => (
                                        <div
                                            key={result.id}
                                            className="p-4 transition hover:bg-slate-50/70"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="flex min-w-0 items-center gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                                        <BookOpenCheck className="h-5 w-5" />
                                                    </div>

                                                    <div className="min-w-0">
                                                        <p className="truncate font-bold text-slate-900">
                                                            {
                                                                result.subject
                                                            }
                                                        </p>

                                                        <p className="mt-0.5 truncate text-xs text-slate-500">
                                                            {result.title}
                                                        </p>
                                                    </div>
                                                </div>

                                                {result.score !== null ? (
                                                    <span className="shrink-0 rounded-xl bg-emerald-50 px-2.5 py-1.5 text-sm font-bold text-emerald-700">
                                                        {result.score} /{' '}
                                                        {result.max_score}
                                                    </span>
                                                ) : (
                                                    <Badge tone="slate">
                                                        —
                                                    </Badge>
                                                )}
                                            </div>

                                            <div className="mt-3 flex items-center gap-2 text-xs text-slate-500">
                                                <CalendarDays className="h-3.5 w-3.5" />

                                                <span>
                                                    {result.period ||
                                                        'Période non définie'}
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function SummaryCard({
    icon: Icon,
    label,
    value,
    gradient,
}) {
    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${gradient} p-5 text-white shadow-sm`}
        >
            <div className="absolute -right-7 -top-8 h-28 w-28 rounded-full bg-white/10" />
            <div className="absolute -bottom-10 -left-5 h-20 w-20 rounded-full bg-white/5" />

            <div className="relative flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-white/75">
                        {label}
                    </p>

                    <p className="mt-2 text-3xl font-bold tracking-tight">
                        {value}
                    </p>
                </div>

                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    );
}