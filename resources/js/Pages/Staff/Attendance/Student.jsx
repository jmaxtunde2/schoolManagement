import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Clock3,
    GraduationCap,
    ShieldCheck,
    UserRound,
    UserX,
    Users,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import Pagination from '@/Components/UI/Pagination';

const statusLabels = {
    present: 'Présent',
    absent: 'Absent',
    late: 'Retard',
};

const statusTones = {
    present: 'green',
    absent: 'red',
    late: 'amber',
};

export default function AttendanceStudent({
    student,
    records,
    stats,
    routePrefix,
}) {
    return (
        <AuthenticatedLayout title={`Assiduité · ${student.name}`}>
            <Head title={`Assiduité · ${student.name}`} />

            <div className="mx-auto max-w-6xl space-y-6">

                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route(`${routePrefix}.attendance.index`)}
                            className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                            aria-label="Retour aux relevés"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>

                        <div>
                            <div className="flex items-center gap-2">
                                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <CalendarDays className="h-5 w-5" />
                                </div>

                                <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">
                                    Historique élève
                                </p>
                            </div>

                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                {student.name}
                            </h1>

                            <p className="mt-1 flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
                                <span>
                                    {student.class_name || 'Classe non définie'}
                                </span>

                                {student.matricule && (
                                    <>
                                        <span className="text-slate-300">•</span>
                                        <span>{student.matricule}</span>
                                    </>
                                )}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm">
                            <UserRound className="h-5 w-5" />
                        </div>

                        <div>
                            <p className="text-xs font-medium text-slate-500">
                                Élève
                            </p>
                            <p className="font-bold text-slate-900">
                                Suivi d’assiduité
                            </p>
                        </div>
                    </div>
                </div>

                {/* Metrics */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    <Metric
                        icon={UserX}
                        label="Absences"
                        value={stats.absent}
                        gradient="from-rose-500 to-red-600"
                    />

                    <Metric
                        icon={ShieldCheck}
                        label="Justifiées"
                        value={stats.justified}
                        gradient="from-emerald-500 to-teal-600"
                    />

                    <Metric
                        icon={UserX}
                        label="Non justifiées"
                        value={stats.unjustified}
                        gradient="from-orange-400 to-amber-500"
                    />

                    <Metric
                        icon={Clock3}
                        label="Retards"
                        value={stats.late}
                        gradient="from-amber-400 to-orange-500"
                    />

                    <Metric
                        icon={GraduationCap}
                        label="Minutes de retard"
                        value={stats.delay_minutes}
                        gradient="from-violet-500 to-purple-600"
                    />
                </div>

                {/* History */}
                <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                    <Card.Header
                        title={
                            <div className="flex items-center gap-2">
                                <span>Historique d’assiduité</span>

                                <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                    {records.total} relevé(s)
                                </span>
                            </div>
                        }
                        description="Consultez les absences, retards et éventuelles justifications."
                    />

                    <Card.Body className="p-0">
                        {records.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <CalendarDays className="h-8 w-8" />
                                </div>

                                <h3 className="mt-5 text-lg font-bold text-slate-900">
                                    Aucun relevé
                                </h3>

                                <p className="mt-2 max-w-md text-sm text-slate-500">
                                    Aucun relevé d’assiduité n’est actuellement
                                    enregistré pour cet élève.
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
                                                    Date
                                                </th>
                                                <th className="px-6 py-4">
                                                    Type
                                                </th>
                                                <th className="px-6 py-4">
                                                    Motif
                                                </th>
                                                <th className="px-6 py-4">
                                                    Justification
                                                </th>
                                                <th className="px-6 py-4">
                                                    Enregistré par
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-slate-100">
                                            {records.data.map((record) => {
                                                const justification =
                                                    record.justifications?.[0];

                                                const justificationLabel =
                                                    record.justified_at
                                                        ? 'Justifiée'
                                                        : justification?.status ===
                                                          'pending'
                                                        ? 'En attente'
                                                        : justification?.status ===
                                                          'rejected'
                                                        ? 'Refusée'
                                                        : 'Non justifiée';

                                                return (
                                                    <tr
                                                        key={record.id}
                                                        className="group transition hover:bg-slate-50/70"
                                                    >
                                                        <td className="whitespace-nowrap px-6 py-4">
                                                            <div className="flex items-center gap-2">
                                                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                                    <CalendarDays className="h-4 w-4" />
                                                                </div>

                                                                <span className="font-semibold text-slate-800">
                                                                    {record.attendance_date}
                                                                </span>
                                                            </div>
                                                        </td>

                                                        <td className="px-6 py-4">
                                                            <div className="flex items-center gap-2">
                                                                <Badge
                                                                    tone={
                                                                        statusTones[
                                                                            record.status
                                                                        ]
                                                                    }
                                                                >
                                                                    {
                                                                        statusLabels[
                                                                            record.status
                                                                        ]
                                                                    }
                                                                </Badge>

                                                                {record.status ===
                                                                    'late' && (
                                                                    <span className="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                                                                        <Clock3 className="h-3.5 w-3.5" />
                                                                        {
                                                                            record.delay_minutes
                                                                        }{' '}
                                                                        min
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </td>

                                                        <td className="max-w-xs px-6 py-4">
                                                            <p className="truncate text-slate-600">
                                                                {record.reason ||
                                                                    record.note ||
                                                                    justification?.reason ||
                                                                    '—'}
                                                            </p>
                                                        </td>

                                                        <td className="px-6 py-4">
                                                            {record.status ===
                                                            'absent' ? (
                                                                <JustificationBadge
                                                                    label={
                                                                        justificationLabel
                                                                    }
                                                                />
                                                            ) : (
                                                                <span className="text-slate-300">
                                                                    —
                                                                </span>
                                                            )}
                                                        </td>

                                                        <td className="px-6 py-4">
                                                            <div className="flex items-center gap-2">
                                                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                                                    <UserRound className="h-4 w-4" />
                                                                </div>

                                                                <span className="font-medium text-slate-700">
                                                                    {record.recorder
                                                                        ?.name ||
                                                                        '—'}
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Mobile cards */}
                                <div className="divide-y divide-slate-100 md:hidden">
                                    {records.data.map((record) => {
                                        const justification =
                                            record.justifications?.[0];

                                        const justificationLabel =
                                            record.justified_at
                                                ? 'Justifiée'
                                                : justification?.status ===
                                                  'pending'
                                                ? 'En attente'
                                                : justification?.status ===
                                                  'rejected'
                                                ? 'Refusée'
                                                : 'Non justifiée';

                                        return (
                                            <div
                                                key={record.id}
                                                className="p-4 transition hover:bg-slate-50/70"
                                            >
                                                <div className="flex items-start justify-between gap-3">
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <CalendarDays className="h-4 w-4 text-emerald-600" />

                                                            <p className="font-bold text-slate-900">
                                                                {
                                                                    record.attendance_date
                                                                }
                                                            </p>
                                                        </div>

                                                        <div className="mt-2 flex flex-wrap items-center gap-2">
                                                            <Badge
                                                                tone={
                                                                    statusTones[
                                                                        record.status
                                                                    ]
                                                                }
                                                            >
                                                                {
                                                                    statusLabels[
                                                                        record.status
                                                                    ]
                                                                }
                                                            </Badge>

                                                            {record.status ===
                                                                'late' && (
                                                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-amber-700">
                                                                    <Clock3 className="h-3.5 w-3.5" />
                                                                    {
                                                                        record.delay_minutes
                                                                    }{' '}
                                                                    min
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {record.status ===
                                                        'absent' && (
                                                        <JustificationBadge
                                                            label={
                                                                justificationLabel
                                                            }
                                                        />
                                                    )}
                                                </div>

                                                <div className="mt-4 rounded-xl bg-slate-50 p-3">
                                                    <p className="text-[11px] font-bold uppercase tracking-wide text-slate-400">
                                                        Motif / observation
                                                    </p>

                                                    <p className="mt-1 text-sm text-slate-700">
                                                        {record.reason ||
                                                            record.note ||
                                                            justification?.reason ||
                                                            'Aucune observation'}
                                                    </p>
                                                </div>

                                                <div className="mt-3 flex items-center gap-2 text-xs text-slate-500">
                                                    <UserRound className="h-3.5 w-3.5" />

                                                    <span>
                                                        Enregistré par{' '}
                                                        <span className="font-semibold text-slate-700">
                                                            {record.recorder
                                                                ?.name || '—'}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </>
                        )}

                        <div className="border-t border-slate-100 bg-slate-50/50 px-4 py-4 sm:px-6">
                            <Pagination meta={records} />
                        </div>
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function Metric({ icon: Icon, label, value, gradient }) {
    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${gradient} p-5 text-white shadow-sm`}
        >
            <div className="absolute -right-7 -top-8 h-28 w-28 rounded-full bg-white/10" />

            <div className="absolute -bottom-10 -left-5 h-20 w-20 rounded-full bg-white/5" />

            <div className="relative flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-white/75">
                        {label}
                    </p>

                    <p className="mt-2 text-3xl font-bold tracking-tight">
                        {value}
                    </p>
                </div>

                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15">
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    );
}

function JustificationBadge({ label }) {
    const styles = {
        Justifiée: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'En attente': 'bg-amber-50 text-amber-700 ring-amber-100',
        Refusée: 'bg-rose-50 text-rose-700 ring-rose-100',
        'Non justifiée': 'bg-slate-100 text-slate-600 ring-slate-200',
    };

    return (
        <span
            className={`inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ${
                styles[label] || styles['Non justifiée']
            }`}
        >
            {label}
        </span>
    );
}