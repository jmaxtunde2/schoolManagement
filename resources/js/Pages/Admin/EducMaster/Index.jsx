import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BookOpenCheck,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    Download,
    FileSpreadsheet,
    GraduationCap,
    Users,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Select from '@/Components/UI/Select';

const exportTypes = [
    {
        key: 'students',
        label: 'Liste des élèves',
        description:
            'Identité, classe, statut et tuteur de chaque élève.',
        icon: GraduationCap,
        route: 'educmaster.students',
        usesClass: true,
        usesYear: false,
        usesPeriod: false,
    },
    {
        key: 'notes',
        label: 'Notes d’évaluation',
        description:
            'Toutes les notes saisies par classe, matière et période.',
        icon: ClipboardList,
        route: 'educmaster.notes',
        usesClass: true,
        usesYear: true,
        usesPeriod: true,
    },
    {
        key: 'classes',
        label: 'Classes',
        description:
            'Niveau, capacité, effectif et nombre de matières par classe.',
        icon: BookOpenCheck,
        route: 'educmaster.classes',
        usesClass: false,
        usesYear: false,
        usesPeriod: false,
    },
    {
        key: 'timetable',
        label: 'Emploi du temps',
        description:
            'Cours hebdomadaires par classe, matière, enseignant et salle.',
        icon: CalendarDays,
        route: 'educmaster.timetable',
        usesClass: true,
        usesYear: true,
        usesPeriod: false,
    },
];

export default function EducMasterIndex({
    academicYears,
    classes,
    periods,
    summary,
    routePrefix,
}) {
    const [classId, setClassId] = useState('');
    const [yearId, setYearId] = useState(
        String(academicYears.find((year) => year.is_current)?.id ?? '')
    );
    const [periodId, setPeriodId] = useState('');

    function downloadUrl(type) {
        const params = {};

        if (type.usesClass && classId) {
            params.class_id = classId;
        }

        if (type.usesYear && yearId) {
            params.academic_year_id = yearId;
        }

        if (type.usesPeriod && periodId) {
            params.period_id = periodId;
        }

        return route(type.route, params);
    }

    const filteredPeriods = yearId
        ? periods.filter(
              (period) => String(period.academic_year_id) === String(yearId)
          )
        : periods;

    return (
        <AuthenticatedLayout title="Export EducMaster">
            <Head title="Export EducMaster" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                            <FileSpreadsheet className="h-6 w-6" />
                        </div>

                        <div>
                            <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">
                                Préparation / export des données
                            </p>

                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                Export EducMaster
                            </h1>

                            <p className="mt-1 text-sm text-slate-500">
                                Générez vos données au format Excel pour
                                EducMaster.
                            </p>
                        </div>
                    </div>

                    <Link
                        href={route(
                            `${routePrefix}.academic.results.index`
                        )}
                    >
                        <Button variant="secondary" size="sm">
                            <ClipboardList className="h-4 w-4" />
                            Résultats & bulletins
                        </Button>
                    </Link>
                </div>

                {/* Préparation des données */}
                <Card>
                    <Card.Header
                        title="Préparation des données"
                        description="Vérifiez l’intégrité des informations avant l’export."
                        actions={
                            summary.issue_count > 0 ? (
                                <Badge tone="amber">
                                    {summary.issue_count} point(s) à vérifier
                                </Badge>
                            ) : (
                                <Badge tone="green">
                                    Données complètes
                                </Badge>
                            )
                        }
                    />

                    <Card.Body className="space-y-4">
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <SummaryTile
                                icon={Users}
                                label="Élèves"
                                value={summary.students.total}
                                detail={`${summary.students.active} actif(s)`}
                            />
                            <SummaryTile
                                icon={BookOpenCheck}
                                label="Classes"
                                value={summary.classes.total}
                            />
                            <SummaryTile
                                icon={ClipboardList}
                                label="Évaluations"
                                value={summary.evaluations.total}
                                detail={
                                    summary.evaluations.pending > 0
                                        ? `${summary.evaluations.pending} non validée(s)`
                                        : 'Toutes validées'
                                }
                            />
                            <SummaryTile
                                icon={CalendarDays}
                                label="Cours (emploi du temps)"
                                value={summary.timetable.total}
                            />
                        </div>

                        <IssueList summary={summary} />
                    </Card.Body>
                </Card>

                {/* Filtres */}
                <Card>
                    <Card.Header
                        title="Filtres d’export"
                        description="Affinez les données exportées (facultatif)."
                    />

                    <Card.Body className="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-600">
                                Année scolaire
                            </label>

                            <Select
                                value={yearId}
                                onChange={(event) =>
                                    setYearId(event.target.value)
                                }
                            >
                                <option value="">Toutes les années</option>
                                {academicYears.map((year) => (
                                    <option key={year.id} value={year.id}>
                                        {year.name}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-600">
                                Classe
                            </label>

                            <Select
                                value={classId}
                                onChange={(event) =>
                                    setClassId(event.target.value)
                                }
                            >
                                <option value="">Toutes les classes</option>
                                {classes.map((classRoom) => (
                                    <option
                                        key={classRoom.id}
                                        value={classRoom.id}
                                    >
                                        {classRoom.name}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-600">
                                Période
                            </label>

                            <Select
                                value={periodId}
                                onChange={(event) =>
                                    setPeriodId(event.target.value)
                                }
                            >
                                <option value="">Toutes les périodes</option>
                                {filteredPeriods.map((period) => (
                                    <option key={period.id} value={period.id}>
                                        {period.name}
                                        {period.academic_year
                                            ? ` — ${period.academic_year}`
                                            : ''}
                                    </option>
                                ))}
                            </Select>
                        </div>
                    </Card.Body>
                </Card>

                {/* Exports */}
                <div className="grid gap-4 sm:grid-cols-2">
                    {exportTypes.map((type) => (
                        <Card key={type.key}>
                            <Card.Body className="flex h-full flex-col justify-between gap-4">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <type.icon className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <h3 className="font-semibold text-slate-900">
                                            {type.label}
                                        </h3>
                                        <p className="mt-0.5 text-sm text-slate-500">
                                            {type.description}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex justify-end">
                                    <Button as="a" href={downloadUrl(type)} size="sm">
                                        <Download className="h-4 w-4" />
                                        Exporter en Excel
                                    </Button>
                                </div>
                            </Card.Body>
                        </Card>
                    ))}
                </div>

                <p className="text-center text-xs text-slate-400">
                    CoriSchool prépare et structure vos données au format requis
                    pour EducMaster. Aucune synchronisation automatique n’est
                    effectuée.
                </p>
            </div>
        </AuthenticatedLayout>
    );
}

function SummaryTile({ icon: Icon, label, value, detail }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <Icon className="h-5 w-5 text-slate-500" />
            <p className="mt-2 text-2xl font-bold text-slate-900">{value}</p>
            <p className="text-xs font-medium text-slate-500">{label}</p>
            {detail && (
                <p className="mt-0.5 text-xs text-slate-400">{detail}</p>
            )}
        </div>
    );
}

function IssueList({ summary }) {
    const issues = [];

    if (summary.students.missing_matricule > 0) {
        issues.push(
            `${summary.students.missing_matricule} élève(s) sans matricule`
        );
    }
    if (summary.students.without_class > 0) {
        issues.push(
            `${summary.students.without_class} élève(s) sans classe`
        );
    }
    if (summary.students.missing_birth_date > 0) {
        issues.push(
            `${summary.students.missing_birth_date} élève(s) sans date de naissance`
        );
    }
    if (summary.students.missing_gender > 0) {
        issues.push(
            `${summary.students.missing_gender} élève(s) sans sexe renseigné`
        );
    }
    if (summary.classes.without_subjects > 0) {
        issues.push(
            `${summary.classes.without_subjects} classe(s) sans matière`
        );
    }
    if (summary.evaluations.pending > 0) {
        issues.push(
            `${summary.evaluations.pending} évaluation(s) non validée(s)`
        );
    }

    if (issues.length === 0) {
        return (
            <div className="flex items-center gap-2 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">
                <CheckCircle2 className="h-4 w-4" />
                Toutes les données requises sont complètes.
            </div>
        );
    }

    return (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p className="flex items-center gap-2 text-sm font-semibold text-amber-800">
                <AlertTriangle className="h-4 w-4" />
                Points à vérifier avant l’export
            </p>

            <ul className="mt-2 list-inside list-disc space-y-0.5 text-sm text-amber-700">
                {issues.map((issue) => (
                    <li key={issue}>{issue}</li>
                ))}
            </ul>
        </div>
    );
}
