import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, GraduationCap, Users } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';

const levelDot = {
    red: '🔴',
    amber: '🟠',
    green: '🟢',
    none: '⚪',
};

export default function AtRiskIndex({ students, thresholds, routePrefix = 'admin', scope_note = null }) {
    const total = students.length;
    const lowAverage = students.filter((s) => s.average !== null && s.average < thresholds.low_average).length;
    const attendance = students.filter((s) => s.attendance_level !== 'green').length;

    return (
        <AuthenticatedLayout title="Élèves en difficulté">
            <Head title="Élèves en difficulté" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-rose-600">Suivi</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                            Élèves nécessitant une attention particulière
                        </h1>
                        <p className="mt-1 max-w-3xl text-sm text-slate-500">
                            Signalements purement scolaires (moyenne faible, baisse des résultats, difficultés
                            multiples, absences et retards). Ces indicateurs ne constituent pas un diagnostic
                            médical ou psychologique.
                        </p>
                        {scope_note && <p className="mt-1 text-xs text-slate-400">{scope_note}</p>}
                    </div>

                    <div className="flex gap-3">
                        <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <p className="text-2xl font-bold text-slate-900">{total}</p>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">À suivre</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <p className="text-2xl font-bold text-rose-700">{lowAverage}</p>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Moyenne faible</p>
                        </div>
                        <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <p className="text-2xl font-bold text-amber-600">{attendance}</p>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Assiduité</p>
                        </div>
                    </div>
                </div>

                <Card className="overflow-hidden">
                    <Card.Header
                        title={
                            <span className="flex items-center gap-2">
                                <span className="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                    <AlertTriangle className="h-4 w-4" />
                                </span>
                                Liste des élèves signalés
                            </span>
                        }
                        description={`Seuils : moyenne < ${thresholds.low_average}/20 · absences ≥ ${thresholds.absences_min} · retards ≥ ${thresholds.lates_min}`}
                    />
                    <Card.Body className="p-0">
                        {total === 0 ? (
                            <div className="p-6">
                                <EmptyState
                                    title="Aucun élève signalé"
                                    description="Aucun élève ne franchit les seuils configurés sur la période en cours."
                                />
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                                            <th className="px-6 py-3 font-semibold">Élève</th>
                                            <th className="px-6 py-3 font-semibold">Classe</th>
                                            <th className="px-6 py-3 font-semibold">Moyenne</th>
                                            <th className="px-6 py-3 font-semibold">Performance</th>
                                            <th className="px-6 py-3 font-semibold">Assiduité</th>
                                            <th className="px-6 py-3 font-semibold">Signaux</th>
                                            <th className="px-6 py-3" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {students.map((student) => (
                                            <tr key={student.student_id} className="hover:bg-slate-50">
                                                <td className="px-6 py-3">
                                                    <div className="flex items-center gap-2 font-medium text-slate-800">
                                                        <GraduationCap className="h-4 w-4 text-slate-400" />
                                                        {student.student_name}
                                                    </div>
                                                    {student.matricule && (
                                                        <p className="mt-0.5 text-xs text-slate-400">{student.matricule}</p>
                                                    )}
                                                </td>
                                                <td className="px-6 py-3 text-slate-600">{student.class_name}</td>
                                                <td className="px-6 py-3 font-semibold text-slate-800">
                                                    {student.average !== null ? `${student.average}/20` : '—'}
                                                    {student.previous_average !== null && student.average !== null && (
                                                        <span className="ml-1 text-xs font-normal text-slate-400">
                                                            (préc. {student.previous_average})
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-3">{levelDot[student.performance_level]}</td>
                                                <td className="px-6 py-3">
                                                    {levelDot[student.attendance_level]}
                                                    <span className="ml-1 text-xs text-slate-500">
                                                        {student.absences} abs. · {student.lates} ret.
                                                    </span>
                                                </td>
                                                <td className="px-6 py-3">
                                                    <div className="flex flex-wrap gap-1">
                                                        {student.flags.map((flag) => (
                                                            <Badge key={flag} tone="amber">{flag}</Badge>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-3 text-right">
                                                    <Link
                                                        href={route(`${routePrefix}.students.follow-up`, student.student_id)}
                                                        className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:opacity-80"
                                                    >
                                                        Fiche
                                                        <ArrowRight className="h-4 w-4" />
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
