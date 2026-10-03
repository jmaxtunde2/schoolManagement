import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Award,
    BookOpenCheck,
    ChevronDown,
    FileText,
    Filter,
    GraduationCap,
    Medal,
    Printer,
    TrendingUp,
    Users,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Select from '@/Components/UI/Select';

export default function AcademicResults({
    classes,
    periods,
    filters,
    results,
    routePrefix,
}) {
    const { auth } = usePage().props;
    const canGenerate = auth.user.role !== 'teacher';

    function applyFilters(event) {
        event.preventDefault();

        const data = new FormData(event.currentTarget);

        router.get(
            route(`${routePrefix}.academic.results.index`),
            Object.fromEntries(data.entries()),
            {
                preserveState: true,
                replace: true,
            }
        );
    }

    function generate(studentId = null) {
        router.post(
            route(`${routePrefix}.academic.report-cards.generate`),
            {
                class_id: filters.class_id,
                period_id: filters.period_id,
                student_id: studentId,
            },
            {
                preserveScroll: true,
            }
        );
    }

    const calculatedCount =
        results?.students?.filter(
            (student) => student.general_average !== null
        ).length ?? 0;

    const withoutResultCount =
        results?.students?.filter(
            (student) => student.general_average === null
        ).length ?? 0;

    return (
        <AuthenticatedLayout title="Résultats académiques">
            <Head title="Résultats académiques" />

            <div className="mx-auto max-w-7xl space-y-6">

                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                            <GraduationCap className="h-6 w-6" />
                        </div>

                        <div>
                            <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">
                                Calcul académique
                            </p>

                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                Résultats par classe
                            </h1>

                            <p className="mt-1 text-sm text-slate-500">
                                Consultez les moyennes et classements à partir
                                des évaluations validées.
                            </p>
                        </div>
                    </div>

                    {results && (
                        <div className="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm">
                                <Users className="h-5 w-5" />
                            </div>

                            <div>
                                <p className="text-xs font-medium text-slate-500">
                                    Classe
                                </p>

                                <p className="font-bold text-slate-900">
                                    {results.class_name}
                                </p>
                            </div>
                        </div>
                    )}
                </div>

                {/* Filters */}
                <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                    <div className="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-emerald-50/40 px-5 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <Filter className="h-5 w-5" />
                            </div>

                            <div>
                                <h2 className="font-bold text-slate-900">
                                    Sélection des résultats
                                </h2>

                                <p className="text-xs text-slate-500">
                                    Choisissez la classe et la période à
                                    consulter.
                                </p>
                            </div>
                        </div>
                    </div>

                    <Card.Body className="p-5 sm:p-6">
                        <form
                            onSubmit={applyFilters}
                            className="grid gap-4 lg:grid-cols-[1fr_1fr_auto]"
                        >
                            <div>
                                <label className="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                    Classe
                                </label>

                                <Select
                                    name="class_id"
                                    defaultValue={filters.class_id ?? ''}
                                    required
                                >
                                    <option value="">
                                        Sélectionner une classe
                                    </option>

                                    {classes.map((item) => (
                                        <option
                                            key={item.id}
                                            value={item.id}
                                        >
                                            {item.name}
                                        </option>
                                    ))}
                                </Select>
                            </div>

                            <div>
                                <label className="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                                    Période académique
                                </label>

                                <Select
                                    name="period_id"
                                    defaultValue={filters.period_id ?? ''}
                                    required
                                >
                                    <option value="">
                                        Sélectionner une période
                                    </option>

                                    {periods.map((item) => (
                                        <option
                                            key={item.id}
                                            value={item.id}
                                        >
                                            {item.academic_year} · {item.name}
                                        </option>
                                    ))}
                                </Select>
                            </div>

                            <div className="flex items-end">
                                <Button
                                    type="submit"
                                    variant="secondary"
                                    className="w-full lg:w-auto"
                                >
                                    <Filter className="h-4 w-4" />
                                    Afficher les résultats
                                </Button>
                            </div>
                        </form>
                    </Card.Body>
                </Card>

                {/* No selection */}
                {!results ? (
                    <Card className="border-0 shadow-sm ring-1 ring-slate-200">
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <BookOpenCheck className="h-8 w-8" />
                            </div>

                            <h3 className="mt-5 text-lg font-bold text-slate-900">
                                Sélectionnez vos critères
                            </h3>

                            <p className="mt-2 max-w-md text-sm text-slate-500">
                                Choisissez une classe et une période pour
                                afficher les résultats académiques.
                            </p>
                        </div>
                    </Card>
                ) : results.students.length === 0 ? (
                    <Card className="border-0 shadow-sm ring-1 ring-slate-200">
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <Users className="h-8 w-8" />
                            </div>

                            <h3 className="mt-5 text-lg font-bold text-slate-900">
                                Aucun élève actif
                            </h3>

                            <p className="mt-2 text-sm text-slate-500">
                                Aucun élève actif n’est actuellement présent
                                dans cette classe.
                            </p>
                        </div>
                    </Card>
                ) : (
                    <>
                        {/* Result summary */}
                        <div className="grid grid-cols-2 gap-4 lg:grid-cols-3">
                            <SummaryMetric
                                icon={Users}
                                label="Élèves"
                                value={results.students.length}
                                gradient="from-emerald-500 to-teal-600"
                            />

                            <SummaryMetric
                                icon={TrendingUp}
                                label="Résultats calculés"
                                value={calculatedCount}
                                gradient="from-blue-500 to-indigo-600"
                            />

                            <SummaryMetric
                                icon={BookOpenCheck}
                                label="Sans résultat"
                                value={withoutResultCount}
                                gradient="from-slate-500 to-slate-700"
                            />
                        </div>

                        {/* Main results */}
                        <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                            <Card.Header
                                title={
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span>
                                            {results.class_name}
                                        </span>

                                        <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                            {results.period_name}
                                        </span>
                                    </div>
                                }
                                description={`${results.students.length} élève(s) · ${results.ranked_students} résultat(s) calculable(s)`}
                                actions={
                                    canGenerate ? (
                                        <Button
                                            size="sm"
                                            onClick={() => generate()}
                                            disabled={
                                                results.ranked_students === 0
                                            }
                                        >
                                            <FileText className="h-4 w-4" />
                                            <span className="hidden sm:inline">
                                                Générer les bulletins
                                            </span>
                                            <span className="sm:hidden">
                                                Bulletins
                                            </span>
                                        </Button>
                                    ) : null
                                }
                            />

                            <Card.Body className="p-0">
                                {/* Desktop */}
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-slate-50/80">
                                            <tr className="border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                                <th className="px-5 py-4">
                                                    Élève
                                                </th>

                                                <th className="px-5 py-4 text-right">
                                                    Moyenne
                                                </th>

                                                <th className="px-5 py-4 text-center">
                                                    Rang
                                                </th>

                                                <th className="px-5 py-4 text-center">
                                                    Matières
                                                </th>

                                                <th className="px-5 py-4 text-center">
                                                    Évaluations
                                                </th>

                                                <th className="px-5 py-4">
                                                    Résultat
                                                </th>

                                                <th className="px-5 py-4 text-right">
                                                    Bulletin
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-slate-100">
                                            {results.students.map(
                                                (student) => (
                                                    <ResultRow
                                                        key={
                                                            student.student_id
                                                        }
                                                        student={student}
                                                        canGenerate={
                                                            canGenerate
                                                        }
                                                        generate={generate}
                                                        routePrefix={
                                                            routePrefix
                                                        }
                                                    />
                                                )
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Mobile */}
                                <div className="divide-y divide-slate-100 md:hidden">
                                    {results.students.map((student) => (
                                        <MobileResultCard
                                            key={student.student_id}
                                            student={student}
                                            canGenerate={canGenerate}
                                            generate={generate}
                                            routePrefix={routePrefix}
                                        />
                                    ))}
                                </div>

                                {/* Subject details */}
                                <div className="space-y-3 border-t border-slate-100 bg-slate-50/70 p-4 sm:p-5">
                                    {results.students
                                        .filter(
                                            (student) =>
                                                student.general_average !==
                                                null
                                        )
                                        .map((student) => (
                                            <details
                                                key={student.student_id}
                                                className="group overflow-hidden rounded-xl border border-slate-200 bg-white"
                                            >
                                                <summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    <span className="flex min-w-0 items-center gap-2">
                                                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                                            <GraduationCap className="h-4 w-4" />
                                                        </span>

                                                        <span className="truncate">
                                                            Détail des matières ·{' '}
                                                            {
                                                                student.student_name
                                                            }
                                                        </span>
                                                    </span>

                                                    <span className="flex shrink-0 items-center gap-3">
                                                        <span className="hidden text-xs font-normal text-slate-500 sm:block">
                                                            {
                                                                student.subjects
                                                                    .length
                                                            }{' '}
                                                            matière(s) ·{' '}
                                                            {
                                                                student
                                                                    .attendance
                                                                    .absences
                                                            }{' '}
                                                            abs. /{' '}
                                                            {
                                                                student
                                                                    .attendance
                                                                    .late_count
                                                            }{' '}
                                                            ret.
                                                        </span>

                                                        <ChevronDown className="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180" />
                                                    </span>
                                                </summary>

                                                <div className="border-t border-slate-100 bg-slate-50/50 p-4">
                                                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                        {student.subjects.map(
                                                            (subject) => (
                                                                <div
                                                                    key={
                                                                        subject.subject_id
                                                                    }
                                                                    className="flex items-center justify-between gap-3 rounded-xl border border-slate-200 border-l-4 border-l-emerald-500 bg-white p-3"
                                                                >
                                                                    <div className="min-w-0">
                                                                        <p className="truncate text-sm font-bold text-slate-800">
                                                                            {
                                                                                subject.subject_name
                                                                            }
                                                                        </p>

                                                                        <p className="mt-1 text-[11px] text-slate-500">
                                                                            {
                                                                                subject.evaluation_count
                                                                            }{' '}
                                                                            évaluation(s)
                                                                            {' · '}
                                                                            coef.{' '}
                                                                            {
                                                                                subject.coefficient
                                                                            }
                                                                        </p>
                                                                    </div>

                                                                    <span className="shrink-0 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-sm font-bold text-emerald-700">
                                                                        {subject.average.toFixed(
                                                                            2
                                                                        )}{' '}
                                                                        / 20
                                                                    </span>
                                                                </div>
                                                            )
                                                        )}
                                                    </div>
                                                </div>
                                            </details>
                                        ))}
                                </div>
                            </Card.Body>
                        </Card>
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function SummaryMetric({ icon: Icon, label, value, gradient }) {
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

function ResultRow({
    student,
    canGenerate,
    generate,
    routePrefix,
}) {
    return (
        <tr className="group align-middle transition hover:bg-slate-50/70">
            <td className="px-5 py-4">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 text-sm font-bold text-emerald-700">
                        {student.student_name
                            ?.charAt(0)
                            ?.toUpperCase()}
                    </div>

                    <div className="min-w-0">
                        <p className="font-bold text-slate-900">
                            {student.student_name}
                        </p>

                        <p className="mt-0.5 text-xs text-slate-500">
                            {student.matricule || 'Matricule non renseigné'}
                        </p>
                    </div>
                </div>
            </td>

            <td className="px-5 py-4 text-right">
                {student.general_average === null ? (
                    <span className="font-semibold text-slate-400">
                        —
                    </span>
                ) : (
                    <span className="inline-flex rounded-xl bg-emerald-50 px-3 py-1.5 text-base font-bold text-emerald-700">
                        {student.general_average.toFixed(2)} / 20
                    </span>
                )}
            </td>

            <td className="px-5 py-4 text-center">
                {student.rank ? (
                    <span className="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 font-bold text-amber-700">
                        <Award className="h-4 w-4" />
                        {student.rank}
                        <span className="font-normal text-amber-600">
                            / {student.ranked_students}
                        </span>
                    </span>
                ) : (
                    <span className="text-slate-300">—</span>
                )}
            </td>

            <td className="px-5 py-4 text-center">
                <span className="font-semibold text-slate-700">
                    {student.subjects.length}
                </span>
            </td>

            <td className="px-5 py-4 text-center">
                <span className="font-semibold text-slate-700">
                    {student.evaluation_count}
                </span>
            </td>

            <td className="px-5 py-4">
                {student.general_average === null ? (
                    <Badge tone="slate">Sans résultat</Badge>
                ) : (
                    <Badge tone="green">Calculé</Badge>
                )}
            </td>

            <td className="px-5 py-4 text-right">
                {student.report_card ? (
                    <Link
                        href={route(
                            `${routePrefix}.report-cards.show`,
                            student.report_card.id
                        )}
                        className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50"
                    >
                        <FileText className="h-3.5 w-3.5" />
                        V{student.report_card.version}
                        <span className="hidden lg:inline">
                            · Voir
                        </span>
                    </Link>
                ) : canGenerate &&
                  student.general_average !== null ? (
                    <button
                        type="button"
                        onClick={() =>
                            generate(student.student_id)
                        }
                        className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50"
                    >
                        <FileText className="h-3.5 w-3.5" />
                        Générer
                    </button>
                ) : (
                    <span className="text-slate-300">—</span>
                )}
            </td>

            <td className="hidden">
                <span>{student.attendance.absences} abs.</span>
            </td>
        </tr>
    );
}

function MobileResultCard({
    student,
    canGenerate,
    generate,
    routePrefix,
}) {
    return (
        <div className="p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="flex min-w-0 items-center gap-3">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 text-sm font-bold text-emerald-700">
                        {student.student_name
                            ?.charAt(0)
                            ?.toUpperCase()}
                    </div>

                    <div className="min-w-0">
                        <p className="truncate font-bold text-slate-900">
                            {student.student_name}
                        </p>

                        <p className="mt-0.5 text-xs text-slate-500">
                            {student.matricule || 'Matricule non renseigné'}
                        </p>
                    </div>
                </div>

                {student.general_average === null ? (
                    <Badge tone="slate">Sans résultat</Badge>
                ) : (
                    <Badge tone="green">Calculé</Badge>
                )}
            </div>

            <div className="mt-4 grid grid-cols-2 gap-2">
                <div className="rounded-xl bg-emerald-50 p-3">
                    <p className="text-[10px] font-bold uppercase tracking-wide text-emerald-600">
                        Moyenne
                    </p>

                    <p className="mt-1 text-lg font-bold text-emerald-800">
                        {student.general_average === null
                            ? '—'
                            : `${student.general_average.toFixed(2)} / 20`}
                    </p>
                </div>

                <div className="rounded-xl bg-amber-50 p-3">
                    <p className="text-[10px] font-bold uppercase tracking-wide text-amber-600">
                        Rang
                    </p>

                    <p className="mt-1 flex items-center gap-1 text-lg font-bold text-amber-800">
                        {student.rank ? (
                            <>
                                <Medal className="h-4 w-4" />
                                {student.rank}
                                <span className="text-xs font-normal">
                                    / {student.ranked_students}
                                </span>
                            </>
                        ) : (
                            '—'
                        )}
                    </p>
                </div>
            </div>

            <div className="mt-3 flex flex-wrap gap-2 text-xs">
                <span className="rounded-lg bg-slate-100 px-2.5 py-1.5 font-medium text-slate-600">
                    {student.subjects.length} matière(s)
                </span>

                <span className="rounded-lg bg-slate-100 px-2.5 py-1.5 font-medium text-slate-600">
                    {student.evaluation_count} évaluation(s)
                </span>
            </div>

            <div className="mt-4 flex justify-end border-t border-slate-100 pt-3">
                {student.report_card ? (
                    <Link
                        href={route(
                            `${routePrefix}.report-cards.show`,
                            student.report_card.id
                        )}
                        className="inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700"
                    >
                        <FileText className="h-4 w-4" />
                        Voir le bulletin · V{student.report_card.version}
                    </Link>
                ) : canGenerate &&
                  student.general_average !== null ? (
                    <button
                        type="button"
                        onClick={() =>
                            generate(student.student_id)
                        }
                        className="inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700"
                    >
                        <FileText className="h-4 w-4" />
                        Générer le bulletin
                    </button>
                ) : (
                    <span className="text-xs text-slate-400">
                        Bulletin non disponible
                    </span>
                )}
            </div>
        </div>
    );
}
