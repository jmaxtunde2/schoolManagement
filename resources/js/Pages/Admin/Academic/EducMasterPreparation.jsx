import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    Download,
    GraduationCap,
    ListChecks,
    ShieldCheck,
    XCircle,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';

const issueLabels = {
    missing_matricule: 'Matricule manquant',
    missing_name: 'Identité incomplète',
    missing_birth_date: 'Date de naissance manquante',
    missing_gender: 'Sexe manquant',
    no_results: 'Aucun résultat calculable',
    class_without_subjects: 'Aucune matière associée',
    duplicate_matricule: 'Matricule en doublon',
};

function issueLabel(type) {
    return issueLabels[type] ?? 'Anomalie';
}

export default function EducMasterPreparation({
    report,
    classRoom,
    period,
    routePrefix,
}) {
    const errors = report.issues.filter((issue) => issue.severity === 'error');
    const warnings = report.issues.filter(
        (issue) => issue.severity === 'warning'
    );

    function exportFile() {
        router.post(
            route(`${routePrefix}.academic.results.export-educmaster`),
            {
                class_id: classRoom.id,
                period_id: period.id,
            },
            { preserveScroll: true }
        );
    }

    return (
        <AuthenticatedLayout title="Préparation EducMaster">
            <Head title="Préparation EducMaster" />

            <div className="mx-auto max-w-5xl space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                            <ShieldCheck className="h-6 w-6" />
                        </div>

                        <div>
                            <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">
                                Préparation / export des données
                            </p>

                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                Préparer les données pour EducMaster
                            </h1>

                            <p className="mt-1 text-sm text-slate-500">
                                {classRoom.name} · {period.name}
                                {report.academic_year_name
                                    ? ` · ${report.academic_year_name}`
                                    : ''}
                            </p>
                        </div>
                    </div>

                    <Link
                        href={route(`${routePrefix}.academic.results.index`, {
                            class_id: classRoom.id,
                            period_id: period.id,
                        })}
                    >
                        <Button variant="secondary" size="sm">
                            <ArrowLeft className="h-4 w-4" />
                            Retour aux résultats
                        </Button>
                    </Link>
                </div>

                <div
                    className={
                        report.ready
                            ? 'flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5'
                            : 'flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-5'
                    }
                >
                    {report.ready ? (
                        <CheckCircle2 className="mt-0.5 h-6 w-6 shrink-0 text-emerald-600" />
                    ) : (
                        <XCircle className="mt-0.5 h-6 w-6 shrink-0 text-red-600" />
                    )}

                    <div>
                        <p
                            className={
                                report.ready
                                    ? 'font-semibold text-emerald-800'
                                    : 'font-semibold text-red-800'
                            }
                        >
                            {report.ready
                                ? 'Données prêtes pour l’export'
                                : 'Corrections nécessaires avant export'}
                        </p>

                        <p
                            className={
                                report.ready
                                    ? 'mt-0.5 text-sm text-emerald-700'
                                    : 'mt-0.5 text-sm text-red-700'
                            }
                        >
                            {report.ready
                                ? 'Aucune erreur bloquante détectée. Vous pouvez préparer le fichier au format requis.'
                                : 'Corrigez les erreurs ci-dessous puis relancez la vérification.'}
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <StatCard
                        icon={GraduationCap}
                        label="Élèves"
                        value={report.student_count}
                    />
                    <StatCard
                        icon={ListChecks}
                        label="Résultats calculés"
                        value={report.ranked_students}
                    />
                    <StatCard
                        icon={XCircle}
                        label="Erreurs"
                        value={report.error_count}
                        tone="red"
                    />
                    <StatCard
                        icon={AlertTriangle}
                        label="Avertissements"
                        value={report.warning_count}
                        tone="amber"
                    />
                </div>

                <Card>
                    <Card.Header
                        title="Anomalies détectées"
                        description="Champs obligatoires manquants, doublons et incohérences."
                        actions={
                            report.issues.length > 0 ? (
                                <Badge tone={report.error_count > 0 ? 'red' : 'amber'}>
                                    {report.issues.length} élément(s)
                                </Badge>
                            ) : null
                        }
                    />

                    <Card.Body>
                        {report.issues.length === 0 ? (
                            <div className="flex items-center gap-3 text-sm text-slate-500">
                                <CheckCircle2 className="h-5 w-5 text-emerald-500" />
                                Aucune anomalie détectée.
                            </div>
                        ) : (
                            <ul className="divide-y divide-slate-100">
                                {report.issues.map((issue, index) => (
                                    <li
                                        key={`${issue.type}-${index}`}
                                        className="flex items-start gap-3 py-3 first:pt-0 last:pb-0"
                                    >
                                        {issue.severity === 'error' ? (
                                            <XCircle className="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
                                        ) : (
                                            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                                        )}

                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-sm font-medium text-slate-800">
                                                    {issueLabel(issue.type)}
                                                </span>

                                                <Badge
                                                    tone={
                                                        issue.severity === 'error'
                                                            ? 'red'
                                                            : 'amber'
                                                    }
                                                >
                                                    {issue.severity === 'error'
                                                        ? 'Erreur'
                                                        : 'Avertissement'}
                                                </Badge>
                                            </div>

                                            <p className="mt-0.5 text-sm text-slate-500">
                                                {issue.message}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card.Body>
                </Card>

                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button
                        onClick={exportFile}
                        disabled={!report.ready}
                        className="sm:min-w-64"
                    >
                        <Download className="h-4 w-4" />
                        Préparer / exporter le fichier EducMaster
                    </Button>
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

function StatCard({ icon: Icon, label, value, tone = 'slate' }) {
    const tones = {
        slate: 'text-slate-500',
        red: 'text-red-500',
        amber: 'text-amber-500',
    };

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <Icon className={`h-5 w-5 ${tones[tone]}`} />
            <p className="mt-2 text-2xl font-bold text-slate-900">{value}</p>
            <p className="text-xs font-medium text-slate-500">{label}</p>
        </div>
    );
}
