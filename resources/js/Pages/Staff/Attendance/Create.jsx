import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Check,
    Circle,
    Clock3,
    Save,
    Users,
    UserRound,
    UserCheck,
    UserX,
    Timer,
    RotateCcw,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';

const statuses = [
    {
        value: 'present',
        label: 'Présent',
        icon: UserCheck,
        active: 'border-emerald-500 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        iconBg: 'bg-emerald-100 text-emerald-600',
    },
    {
        value: 'absent',
        label: 'Absent',
        icon: UserX,
        active: 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        iconBg: 'bg-rose-100 text-rose-600',
    },
    {
        value: 'late',
        label: 'Retard',
        icon: Timer,
        active: 'border-amber-500 bg-amber-50 text-amber-700 ring-1 ring-amber-200',
        iconBg: 'bg-amber-100 text-amber-600',
    },
];

export default function AttendanceCreate({
    classes,
    selectedClass,
    students,
    attendanceDate,
    periods,
    routePrefix,
}) {
    const [attendance] = useState(() =>
        students.map((student) => ({
            student_id: student.id,
            status: student.attendance?.status ?? 'present',
            delay_minutes: student.attendance?.delay_minutes ?? '',
            reason: student.attendance?.reason ?? '',
            note: student.attendance?.note ?? '',
        }))
    );

    const form = useForm({
        class_room_id: selectedClass?.id ?? '',
        attendance_date: attendanceDate,
        academic_period_id: '',
        records: attendance,
    });

    function loadRoster(classRoomId, date) {
        router.get(
            route(`${routePrefix}.attendance.create`),
            {
                class_id: classRoomId || undefined,
                attendance_date: date || undefined,
            },
            {
                preserveState: false,
                preserveScroll: true,
            }
        );
    }

    function updateRow(index, patch) {
        const next = form.data.records.map((row, rowIndex) =>
            rowIndex === index ? { ...row, ...patch } : row
        );

        form.setData('records', next);
    }

    function setAll(status) {
        form.setData(
            'records',
            form.data.records.map((row) => ({
                ...row,
                status,
                delay_minutes:
                    status === 'late' ? row.delay_minutes || 0 : '',
            }))
        );
    }

    function resetAll() {
        form.setData(
            'records',
            form.data.records.map((row) => ({
                ...row,
                status: 'present',
                delay_minutes: '',
                reason: '',
                note: '',
            }))
        );
    }

    function submit(event) {
        event.preventDefault();

        form.post(route(`${routePrefix}.attendance.store`), {
            preserveScroll: true,
        });
    }

    const counts = form.data.records.reduce(
        (acc, row) => {
            acc[row.status] = (acc[row.status] || 0) + 1;
            return acc;
        },
        {
            present: 0,
            absent: 0,
            late: 0,
        }
    );

    return (
        <AuthenticatedLayout title="Saisie de l’assiduité">
            <Head title="Saisie de l’assiduité" />

            <div className="mx-auto max-w-6xl space-y-6">

                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route(`${routePrefix}.attendance.index`)}
                            className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                            aria-label="Retour à la liste"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>

                        <div>
                            <div className="flex items-center gap-2">
                                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <CalendarDays className="h-5 w-5" />
                                </div>

                                <p className="text-xs font-bold uppercase tracking-wider text-emerald-600">
                                    Gestion de l’assiduité
                                </p>
                            </div>

                            <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                Saisie journalière
                            </h1>

                            <p className="mt-1 text-sm text-slate-500">
                                Enregistrez la présence, les absences et les retards des élèves.
                            </p>
                        </div>
                    </div>

                    {selectedClass && (
                        <div className="hidden rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 to-teal-50 px-4 py-3 sm:block">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm">
                                    <Users className="h-5 w-5" />
                                </div>

                                <div>
                                    <p className="text-xs font-medium text-slate-500">
                                        Classe sélectionnée
                                    </p>
                                    <p className="font-bold text-slate-900">
                                        {selectedClass.name}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Filters */}
                <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                    <div className="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-emerald-50/40 px-5 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <CalendarDays className="h-5 w-5" />
                            </div>

                            <div>
                                <h2 className="font-bold text-slate-900">
                                    Paramètres de la saisie
                                </h2>
                                <p className="text-xs text-slate-500">
                                    Sélectionnez la classe et la date concernées.
                                </p>
                            </div>
                        </div>
                    </div>

                    <Card.Body className="grid gap-5 p-5 sm:grid-cols-3 sm:p-6">
                        <label className="block text-sm font-semibold text-slate-700">
                            Classe

                            <Select
                                className="mt-2"
                                value={selectedClass?.id ?? ''}
                                onChange={(event) =>
                                    loadRoster(
                                        event.target.value,
                                        attendanceDate
                                    )
                                }
                            >
                                <option value="">
                                    Sélectionner une classe
                                </option>

                                {classes.map((classRoom) => (
                                    <option
                                        key={classRoom.id}
                                        value={classRoom.id}
                                    >
                                        {classRoom.name}
                                    </option>
                                ))}
                            </Select>
                        </label>

                        <label className="block text-sm font-semibold text-slate-700">
                            Date

                            <Input
                                className="mt-2"
                                type="date"
                                max={new Date().toISOString().slice(0, 10)}
                                value={attendanceDate}
                                onChange={(event) =>
                                    loadRoster(
                                        selectedClass?.id,
                                        event.target.value
                                    )
                                }
                            />
                        </label>

                        <label className="block text-sm font-semibold text-slate-700">
                            Période
                            <span className="ml-1 text-xs font-normal text-slate-400">
                                (facultative)
                            </span>

                            <Select
                                className="mt-2"
                                value={form.data.academic_period_id}
                                onChange={(event) =>
                                    form.setData(
                                        'academic_period_id',
                                        event.target.value
                                    )
                                }
                            >
                                <option value="">
                                    Aucune période
                                </option>

                                {periods.map((period) => (
                                    <option
                                        key={period.id}
                                        value={period.id}
                                    >
                                        {period.name}
                                    </option>
                                ))}
                            </Select>
                        </label>
                    </Card.Body>
                </Card>

                {/* Empty states */}
                {!selectedClass ? (
                    <Card className="border-0 shadow-sm ring-1 ring-slate-200">
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
                                <Users className="h-8 w-8" />
                            </div>

                            <h3 className="mt-5 text-lg font-bold text-slate-900">
                                Aucune classe sélectionnée
                            </h3>

                            <p className="mt-2 max-w-md text-sm text-slate-500">
                                Sélectionnez une classe ci-dessus pour charger
                                automatiquement la liste de ses élèves.
                            </p>
                        </div>
                    </Card>
                ) : students.length === 0 ? (
                    <Card className="border-0 shadow-sm ring-1 ring-slate-200">
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <UserRound className="h-8 w-8" />
                            </div>

                            <h3 className="mt-5 text-lg font-bold text-slate-900">
                                Aucun élève actif
                            </h3>

                            <p className="mt-2 text-sm text-slate-500">
                                Aucun élève actif n’est actuellement inscrit
                                dans cette classe.
                            </p>
                        </div>
                    </Card>
                ) : (
                    <form onSubmit={submit}>

                        {/* Statistics */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">

                            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white shadow-sm">
                                <div className="absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/10" />

                                <div className="relative flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-white/80">
                                            Présents
                                        </p>
                                        <p className="mt-1 text-3xl font-bold">
                                            {counts.present}
                                        </p>
                                    </div>

                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                        <UserCheck className="h-6 w-6" />
                                    </div>
                                </div>
                            </div>

                            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 p-5 text-white shadow-sm">
                                <div className="absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/10" />

                                <div className="relative flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-white/80">
                                            Absents
                                        </p>
                                        <p className="mt-1 text-3xl font-bold">
                                            {counts.absent}
                                        </p>
                                    </div>

                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                        <UserX className="h-6 w-6" />
                                    </div>
                                </div>
                            </div>

                            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 p-5 text-white shadow-sm">
                                <div className="absolute -right-6 -top-8 h-28 w-28 rounded-full bg-white/10" />

                                <div className="relative flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-white/80">
                                            Retards
                                        </p>
                                        <p className="mt-1 text-3xl font-bold">
                                            {counts.late}
                                        </p>
                                    </div>

                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                                        <Timer className="h-6 w-6" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Attendance list */}
                        <Card className="mt-5 overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">

                            <Card.Header
                                title={
                                    <div className="flex items-center gap-2">
                                        <span>{selectedClass.name}</span>
                                        <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                            {students.length} élèves
                                        </span>
                                    </div>
                                }
                                description={
                                    <span className="flex items-center gap-1.5">
                                        <CalendarDays className="h-3.5 w-3.5" />
                                        {attendanceDate}
                                    </span>
                                }
                                actions={
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => setAll('present')}
                                        >
                                            <Check className="h-4 w-4 text-emerald-600" />
                                            <span className="hidden sm:inline">
                                                Tous présents
                                            </span>
                                        </Button>

                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => resetAll()}
                                        >
                                            <RotateCcw className="h-4 w-4" />
                                            <span className="hidden sm:inline">
                                                Réinitialiser
                                            </span>
                                        </Button>
                                    </div>
                                }
                            />

                            <Card.Body className="p-0">
                                <ul className="divide-y divide-slate-100">
                                    {students.map((student, index) => {
                                        const row =
                                            form.data.records[index];

                                        return (
                                            <li
                                                key={student.id}
                                                className="group px-4 py-5 transition hover:bg-slate-50/70 sm:px-6"
                                            >
                                                <div className="flex flex-col gap-4">

                                                    {/* Student */}
                                                    <div className="flex items-center justify-between gap-4">
                                                        <div className="flex min-w-0 items-center gap-3">
                                                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 text-sm font-bold text-emerald-700">
                                                                {student.name
                                                                    ?.charAt(0)
                                                                    ?.toUpperCase()}
                                                            </div>

                                                            <div className="min-w-0">
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                    <p className="truncate font-bold text-slate-900">
                                                                        {student.name}
                                                                    </p>

                                                                    {student.matricule && (
                                                                        <span className="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                                                            {student.matricule}
                                                                        </span>
                                                                    )}
                                                                </div>

                                                                {student.attendance && (
                                                                    <p className="mt-1 text-xs text-slate-500">
                                                                        Saisie existante :{' '}
                                                                        <span className="font-medium text-slate-700">
                                                                            {student.attendance.status ===
                                                                            'present'
                                                                                ? 'Présent'
                                                                                : student.attendance.status ===
                                                                                    'absent'
                                                                                ? 'Absent'
                                                                                : `Retard · ${student.attendance.delay_minutes} min`}
                                                                        </span>
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </div>

                                                        <span className="hidden text-xs font-medium text-slate-400 sm:block">
                                                            Élève #{index + 1}
                                                        </span>
                                                    </div>

                                                    {/* Status */}
                                                    <div>
                                                        <p className="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">
                                                            Statut
                                                        </p>

                                                        <div
                                                            className="grid grid-cols-3 gap-2 sm:flex sm:flex-wrap"
                                                            role="group"
                                                            aria-label={`Statut de ${student.name}`}
                                                        >
                                                            {statuses.map((status) => {
                                                                const Icon =
                                                                    status.icon;

                                                                const isActive =
                                                                    row.status ===
                                                                    status.value;

                                                                return (
                                                                    <label
                                                                        key={
                                                                            status.value
                                                                        }
                                                                        className={`flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border px-3 text-xs font-bold transition-all sm:min-w-28 ${
                                                                            isActive
                                                                                ? status.active
                                                                                : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50'
                                                                        }`}
                                                                    >
                                                                        <input
                                                                            type="radio"
                                                                            name={`status-${student.id}`}
                                                                            className="sr-only"
                                                                            value={
                                                                                status.value
                                                                            }
                                                                            checked={
                                                                                isActive
                                                                            }
                                                                            onChange={() =>
                                                                                updateRow(
                                                                                    index,
                                                                                    {
                                                                                        status: status.value,
                                                                                        delay_minutes:
                                                                                            status.value ===
                                                                                            'late'
                                                                                                ? row.delay_minutes ||
                                                                                                  ''
                                                                                                : '',
                                                                                    }
                                                                                )
                                                                            }
                                                                        />

                                                                        <span
                                                                            className={`flex h-7 w-7 items-center justify-center rounded-lg ${
                                                                                isActive
                                                                                    ? status.iconBg
                                                                                    : 'bg-slate-100 text-slate-400'
                                                                            }`}
                                                                        >
                                                                            {isActive ? (
                                                                                <Check className="h-4 w-4" />
                                                                            ) : (
                                                                                <Icon className="h-4 w-4" />
                                                                            )}
                                                                        </span>

                                                                        {status.label}
                                                                    </label>
                                                                );
                                                            })}
                                                        </div>
                                                    </div>

                                                    {/* Absent details */}
                                                    {row.status === 'absent' && (
                                                        <div className="rounded-xl border border-rose-100 bg-rose-50/50 p-4">
                                                            <label className="block text-xs font-bold text-rose-800">
                                                                Motif
                                                                <span className="ml-1 font-normal text-rose-500">
                                                                    (facultatif)
                                                                </span>

                                                                <Input
                                                                    className="mt-2 bg-white"
                                                                    value={
                                                                        row.reason
                                                                    }
                                                                    onChange={(
                                                                        event
                                                                    ) =>
                                                                        updateRow(
                                                                            index,
                                                                            {
                                                                                reason:
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                            }
                                                                        )
                                                                    }
                                                                    maxLength={
                                                                        500
                                                                    }
                                                                    placeholder="Maladie, motif familial…"
                                                                />
                                                            </label>
                                                        </div>
                                                    )}

                                                    {/* Late details */}
                                                    {row.status === 'late' && (
                                                        <div className="rounded-xl border border-amber-100 bg-amber-50/50 p-4">
                                                            <label className="block text-xs font-bold text-amber-800">
                                                                Minutes de retard

                                                                <Input
                                                                    className="mt-2 max-w-40 bg-white"
                                                                    type="number"
                                                                    min="0"
                                                                    max="1440"
                                                                    value={
                                                                        row.delay_minutes
                                                                    }
                                                                    onChange={(
                                                                        event
                                                                    ) =>
                                                                        updateRow(
                                                                            index,
                                                                            {
                                                                                delay_minutes:
                                                                                    event
                                                                                        .target
                                                                                        .value,
                                                                            }
                                                                        )
                                                                    }
                                                                    error={
                                                                        form
                                                                            .errors[
                                                                            `records.${index}.delay_minutes`
                                                                        ]
                                                                    }
                                                                    placeholder="0"
                                                                />

                                                                <span className="mt-2 flex items-center gap-1.5 font-normal text-amber-600">
                                                                    <Clock3 className="h-3.5 w-3.5" />
                                                                    Nombre de minutes de retard.
                                                                </span>
                                                            </label>
                                                        </div>
                                                    )}

                                                    {/* Observation */}
                                                    {(row.status === 'absent' ||
                                                        row.status === 'late') && (
                                                        <label className="block text-xs font-bold text-slate-600">
                                                            Observation
                                                            <span className="ml-1 font-normal text-slate-400">
                                                                (facultative)
                                                            </span>

                                                            <Textarea
                                                                rows={2}
                                                                className="mt-2 bg-white"
                                                                value={row.note}
                                                                onChange={(
                                                                    event
                                                                ) =>
                                                                    updateRow(
                                                                        index,
                                                                        {
                                                                            note: event
                                                                                .target
                                                                                .value,
                                                                        }
                                                                    )
                                                                }
                                                                maxLength={2000}
                                                                placeholder="Ajouter une observation…"
                                                            />
                                                        </label>
                                                    )}
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </Card.Body>

                            {/* Footer */}
                            <div className="border-t border-slate-100 bg-slate-50/80 px-4 py-4 sm:px-6">
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="flex items-start gap-3">
                                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm ring-1 ring-slate-200">
                                            <CalendarDays className="h-4 w-4" />
                                        </div>

                                        <div>
                                            <p className="text-sm font-semibold text-slate-700">
                                                Saisie journalière
                                            </p>

                                            <p className="mt-0.5 text-xs text-slate-500">
                                                Une seule présence est enregistrée
                                                par élève et par journée.
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        type="submit"
                                        loading={form.processing}
                                        disabled={!selectedClass}
                                        className="w-full sm:w-auto"
                                    >
                                        <Save className="h-4 w-4" />
                                        Enregistrer l’assiduité
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    </form>
                )}
            </div>
        </AuthenticatedLayout>
    );
}