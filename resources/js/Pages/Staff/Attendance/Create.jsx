import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Check, Circle, Clock3, Save, UserRound } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';

const statuses = [
    { value: 'present', label: 'Présent', active: 'border-emerald-600 bg-emerald-50 text-emerald-800' },
    { value: 'absent', label: 'Absent', active: 'border-rose-600 bg-rose-50 text-rose-800' },
    { value: 'late', label: 'Retard', active: 'border-amber-600 bg-amber-50 text-amber-800' },
];

export default function AttendanceCreate({ classes, selectedClass, students, attendanceDate, periods, routePrefix }) {
    const [attendance, setAttendance] = useState(() => students.map((student) => ({
        student_id: student.id,
        status: student.attendance?.status ?? 'present',
        delay_minutes: student.attendance?.delay_minutes ?? '',
        reason: student.attendance?.reason ?? '',
        note: student.attendance?.note ?? '',
    })));
    const form = useForm({
        class_room_id: selectedClass?.id ?? '',
        attendance_date: attendanceDate,
        academic_period_id: '',
        records: attendance,
    });

    function loadRoster(classRoomId, date) {
        router.get(route(`${routePrefix}.attendance.create`), {
            class_id: classRoomId || undefined,
            attendance_date: date || undefined,
        }, { preserveState: false, preserveScroll: true });
    }

    function updateRow(index, patch) {
        const next = form.data.records.map((row, rowIndex) => rowIndex === index ? { ...row, ...patch } : row);
        form.setData('records', next);
    }

    function setAll(status) {
        form.setData('records', form.data.records.map((row) => ({
            ...row,
            status,
            delay_minutes: status === 'late' ? (row.delay_minutes || 0) : '',
        })));
    }

    function submit(event) {
        event.preventDefault();
        form.post(route(`${routePrefix}.attendance.store`), { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout title="Saisie de l’assiduité">
            <Head title="Saisie de l’assiduité" />
            <div className="mx-auto max-w-5xl space-y-5">
                <div className="flex items-center gap-3">
                    <Link href={route(`${routePrefix}.attendance.index`)} className="flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-white" aria-label="Retour à la liste"><ArrowLeft className="h-4 w-4" /></Link>
                    <div><p className="text-xs font-semibold uppercase text-emerald-700">Saisie journalière</p><h1 className="text-2xl font-bold text-slate-950">Assiduité de la classe</h1></div>
                </div>

                <Card>
                    <Card.Body className="grid gap-4 sm:grid-cols-3">
                        <label className="block text-sm font-medium text-slate-700">Classe
                            <Select className="mt-1.5" value={selectedClass?.id ?? ''} onChange={(event) => loadRoster(event.target.value, attendanceDate)}>
                                <option value="">Sélectionner une classe</option>
                                {classes.map((classRoom) => <option key={classRoom.id} value={classRoom.id}>{classRoom.name}</option>)}
                            </Select>
                        </label>
                        <label className="block text-sm font-medium text-slate-700">Date
                            <Input className="mt-1.5" type="date" max={new Date().toISOString().slice(0, 10)} value={attendanceDate} onChange={(event) => loadRoster(selectedClass?.id, event.target.value)} />
                        </label>
                        <label className="block text-sm font-medium text-slate-700">Période (facultative)
                            <Select className="mt-1.5" value={form.data.academic_period_id} onChange={(event) => form.setData('academic_period_id', event.target.value)}>
                                <option value="">Aucune période</option>
                                {periods.map((period) => <option key={period.id} value={period.id}>{period.name}</option>)}
                            </Select>
                        </label>
                    </Card.Body>
                </Card>

                {!selectedClass ? (
                    <Card className="p-10 text-center"><UserRound className="mx-auto h-8 w-8 text-slate-300" /><p className="mt-3 font-semibold text-slate-800">Sélectionnez une classe pour charger ses élèves.</p></Card>
                ) : students.length === 0 ? (
                    <Card className="p-10 text-center"><p className="font-semibold text-slate-800">Aucun élève actif dans cette classe.</p></Card>
                ) : (
                    <form onSubmit={submit}>
                        <Card className="overflow-hidden">
                            <Card.Header title={`${selectedClass.name} · ${students.length} élève(s)`} description={`Date : ${attendanceDate}`} actions={<div className="flex gap-2"><Button type="button" variant="secondary" size="sm" onClick={() => setAll('present')}>Tous présents</Button></div>} />
                            <Card.Body className="p-0">
                                <ul className="divide-y divide-slate-100">
                                    {students.map((student, index) => {
                                        const row = form.data.records[index];
                                        return (
                                            <li key={student.id} className="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-5">
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2"><p className="truncate font-semibold text-slate-900">{student.name}</p>{student.matricule && <span className="shrink-0 text-xs text-slate-400">{student.matricule}</span>}</div>
                                                    {student.attendance && <p className="mt-1 text-xs text-slate-500">Saisie existante : {student.attendance.status === 'present' ? 'Présent' : student.attendance.status === 'absent' ? 'Absent' : `Retard · ${student.attendance.delay_minutes} min`}</p>}
                                                </div>
                                                <div className="flex flex-wrap gap-2" role="group" aria-label={`Statut de ${student.name}`}>
                                                    {statuses.map((status) => (
                                                        <label key={status.value} className={`inline-flex min-h-10 cursor-pointer items-center gap-1.5 border px-3 text-xs font-semibold transition ${row.status === status.value ? status.active : 'border-slate-200 text-slate-500 hover:bg-slate-50'}`}>
                                                            <input type="radio" name={`status-${student.id}`} className="sr-only" value={status.value} checked={row.status === status.value} onChange={() => updateRow(index, { status: status.value, delay_minutes: status.value === 'late' ? (row.delay_minutes || '') : '' })} />
                                                            {row.status === status.value ? <Check className="h-3.5 w-3.5" /> : <Circle className="h-3.5 w-3.5" />}
                                                            {status.label}
                                                        </label>
                                                    ))}
                                                </div>
                                                {row.status === 'absent' && <label className="text-xs font-medium text-slate-600 sm:col-span-2">Motif facultatif<Input className="mt-1" value={row.reason} onChange={(event) => updateRow(index, { reason: event.target.value })} maxLength={500} placeholder="Maladie, motif familial…" /></label>}
                                                {row.status === 'late' && <label className="text-xs font-medium text-slate-600 sm:col-span-2">Minutes de retard<Input className="mt-1 max-w-40" type="number" min="0" max="1440" value={row.delay_minutes} onChange={(event) => updateRow(index, { delay_minutes: event.target.value })} error={form.errors[`records.${index}.delay_minutes`]} placeholder="0" /><span className="mt-1 flex items-center gap-1 text-slate-400"><Clock3 className="h-3 w-3" />Nombre de minutes, sans valeur négative.</span></label>}
                                                {(row.status === 'absent' || row.status === 'late') && <label className="text-xs font-medium text-slate-600 sm:col-span-2">Observation (facultative)<Textarea rows={2} className="mt-1" value={row.note} onChange={(event) => updateRow(index, { note: event.target.value })} maxLength={2000} /></label>}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </Card.Body>
                            <div className="flex flex-col justify-between gap-3 border-t border-slate-100 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:px-5">
                                <p className="flex items-center gap-2 text-xs text-slate-500"><CalendarDays className="h-4 w-4" />Une seule présence journalière par élève.</p>
                                <Button type="submit" loading={form.processing} disabled={!selectedClass}><Save className="h-4 w-4" />Enregistrer l’assiduité</Button>
                            </div>
                        </Card>
                    </form>
                )}
            </div>
        </AuthenticatedLayout>
    );
}