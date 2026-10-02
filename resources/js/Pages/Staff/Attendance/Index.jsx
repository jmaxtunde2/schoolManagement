import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { CalendarDays, ClipboardCheck, Clock3, Filter, Plus, Search, ShieldCheck } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Pagination from '@/Components/UI/Pagination';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';

const statusLabels = { present: 'Présent', absent: 'Absent', late: 'Retard' };
const statusTones = { present: 'green', absent: 'red', late: 'amber' };
const justificationLabels = { pending: 'En attente', approved: 'Justifiée', rejected: 'Refusée' };

export default function AttendanceIndex({ records, classes, filters, stats, routePrefix }) {
    const [editing, setEditing] = useState(null);
    const editForm = useForm({ status: 'absent', delay_minutes: '', reason: '', note: '' });

    function applyFilters(event) {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const params = Object.fromEntries(formData.entries());
        Object.keys(params).forEach((key) => { if (!params[key]) delete params[key]; });
        router.get(route(`${routePrefix}.attendance.index`), params, { preserveState: true, replace: true });
    }

    function openEdit(record) {
        editForm.setData({
            status: record.status,
            delay_minutes: record.delay_minutes ?? '',
            reason: record.reason ?? '',
            note: record.note ?? '',
        });
        editForm.clearErrors();
        setEditing(record);
    }

    function saveEdit(event) {
        event.preventDefault();
        editForm.put(route(`${routePrefix}.attendance.update`, editing.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(null),
        });
    }

    function review(record, justification, status) {
        router.post(route(`${routePrefix}.attendance.justify`, record.id), {
            justification_id: justification?.id,
            status,
        }, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout title="Absences & retards">
            <Head title="Absences & retards" />
            <div className="space-y-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-xs font-semibold uppercase text-emerald-700">Suivi de l’assiduité</p>
                        <h1 className="mt-1 text-2xl font-bold text-slate-950">Absences & retards</h1>
                        <p className="mt-1 text-sm text-slate-500">Relevés journaliers de votre établissement.</p>
                    </div>
                    <Link href={route(`${routePrefix}.attendance.create`)}>
                        <Button><Plus className="h-4 w-4" />Enregistrer l’assiduité</Button>
                    </Link>
                </div>

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <Summary icon={CalendarDays} label="Absents aujourd’hui" value={stats.absent_today} tone="red" />
                    <Summary icon={Clock3} label="Retards aujourd’hui" value={stats.late_today} tone="amber" />
                    <Summary icon={ClipboardCheck} label="Absences ce mois" value={stats.absent_month} tone="blue" />
                    <Summary icon={ShieldCheck} label="Non justifiées" value={stats.unjustified} tone="slate" />
                </div>

                <Card>
                    <Card.Header title="Relevés d’assiduité" description={`${records.total} relevé(s)`} />
                    <Card.Body>
                        <form onSubmit={applyFilters} className="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                            <Input name="student" defaultValue={filters.student ?? ''} placeholder="Élève ou matricule" />
                            <Select name="class_id" defaultValue={filters.class_id ?? ''}>
                                <option value="">Toutes les classes</option>
                                {classes.map((classRoom) => <option key={classRoom.id} value={classRoom.id}>{classRoom.name}</option>)}
                            </Select>
                            <Input name="date_from" type="date" defaultValue={filters.date_from ?? ''} aria-label="Date de début" />
                            <Input name="date_to" type="date" defaultValue={filters.date_to ?? ''} aria-label="Date de fin" />
                            <Select name="status" defaultValue={filters.status ?? ''}>
                                <option value="">Tous les statuts</option>
                                <option value="absent">Absent</option>
                                <option value="late">Retard</option>
                                <option value="present">Présent</option>
                            </Select>
                            <Select name="justification" defaultValue={filters.justification ?? ''}>
                                <option value="">Toutes justifications</option>
                                <option value="pending">En attente</option>
                                <option value="approved">Justifiée</option>
                                <option value="rejected">Refusée</option>
                                <option value="unjustified">Non justifiée</option>
                            </Select>
                            <Button type="submit" variant="secondary" className="sm:col-span-2 xl:col-span-6"><Filter className="h-4 w-4" />Appliquer les filtres</Button>
                        </form>

                        {records.data.length === 0 ? (
                            <div className="border-t border-slate-100 py-12 text-center">
                                <Search className="mx-auto h-8 w-8 text-slate-300" />
                                <p className="mt-3 font-medium text-slate-700">Aucun relevé trouvé</p>
                                <p className="mt-1 text-sm text-slate-500">Les saisies d’assiduité apparaîtront ici.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto border-t border-slate-100">
                                <table className="min-w-full text-sm">
                                    <thead className="text-left text-xs font-semibold uppercase text-slate-500">
                                        <tr><th className="py-3 pr-4">Date</th><th className="py-3 pr-4">Élève</th><th className="py-3 pr-4">Classe</th><th className="py-3 pr-4">Statut</th><th className="py-3 pr-4">Motif / retard</th><th className="py-3 pr-4">Justification</th><th className="py-3 text-right">Actions</th></tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {records.data.map((record) => {
                                            const latestJustification = record.justifications.at(-1);
                                            return (
                                                <tr key={record.id} className="align-top">
                                                    <td className="py-3 pr-4 whitespace-nowrap">{record.attendance_date}</td>
                                                    <td className="py-3 pr-4"><Link className="font-medium text-slate-800 hover:text-emerald-700" href={route(`${routePrefix}.attendance.students.show`, record.student.id)}>{record.student.name}</Link><span className="mt-0.5 block text-xs text-slate-500">{record.student.matricule || '—'}</span></td>
                                                    <td className="py-3 pr-4">{record.class_name || '—'}</td>
                                                    <td className="py-3 pr-4"><Badge tone={statusTones[record.status]}>{statusLabels[record.status]}</Badge></td>
                                                    <td className="py-3 pr-4">{record.status === 'late' ? `${record.delay_minutes} min` : (record.reason || record.note || '—')}</td>
                                                    <td className="py-3 pr-4">
                                                        {latestJustification ? <div><Badge tone={latestJustification.status === 'approved' ? 'green' : latestJustification.status === 'rejected' ? 'red' : 'amber'}>{justificationLabels[latestJustification.status]}</Badge><p className="mt-1 max-w-48 text-xs text-slate-500">{latestJustification.reason}</p></div> : record.justified_at ? <Badge tone="green">Justifiée</Badge> : record.status === 'absent' ? <Badge tone="red">Non justifiée</Badge> : '—'}
                                                    </td>
                                                    <td className="py-3 text-right">
                                                        <div className="flex justify-end gap-2 whitespace-nowrap">
                                                            {record.can_update && <button type="button" onClick={() => openEdit(record)} className="text-xs font-semibold text-emerald-700 hover:text-emerald-900">Modifier</button>}
                                                            {record.can_justify && record.status === 'absent' && (
                                                                latestJustification?.status === 'pending' ? <><button type="button" onClick={() => review(record, latestJustification, 'approved')} className="text-xs font-semibold text-emerald-700">Justifier</button><button type="button" onClick={() => review(record, latestJustification, 'rejected')} className="text-xs font-semibold text-red-600">Refuser</button></> : <button type="button" onClick={() => review(record, null, 'approved')} className="text-xs font-semibold text-emerald-700">Justifier</button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <div className="mt-4 border-t border-slate-100 pt-4"><Pagination meta={records} /></div>
                    </Card.Body>
                </Card>
            </div>

            <Modal show={Boolean(editing)} onClose={() => setEditing(null)} title="Corriger le relevé" footer={<><Button variant="secondary" onClick={() => setEditing(null)}>Annuler</Button><Button onClick={saveEdit} loading={editForm.processing}>Enregistrer</Button></>}>
                <form onSubmit={saveEdit} className="space-y-4">
                    <p className="text-sm text-slate-600">{editing?.student.name} · {editing?.attendance_date}</p>
                    <Select value={editForm.data.status} onChange={(event) => editForm.setData('status', event.target.value)}>
                        <option value="present">Présent</option><option value="absent">Absent</option><option value="late">Retard</option>
                    </Select>
                    {editForm.data.status === 'late' && <label className="block text-sm font-medium text-slate-700">Minutes de retard<Input className="mt-1" type="number" min="0" max="1440" value={editForm.data.delay_minutes} onChange={(event) => editForm.setData('delay_minutes', event.target.value)} error={editForm.errors.delay_minutes} /></label>}
                    {editForm.data.status === 'absent' && <label className="block text-sm font-medium text-slate-700">Motif (facultatif)<Input className="mt-1" value={editForm.data.reason} onChange={(event) => editForm.setData('reason', event.target.value)} /></label>}
                    <label className="block text-sm font-medium text-slate-700">Observation<Textarea className="mt-1" value={editForm.data.note} onChange={(event) => editForm.setData('note', event.target.value)} rows={3} /></label>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

function Summary({ icon: Icon, label, value, tone }) {
    const tones = { red: 'text-red-700 bg-red-50', amber: 'text-amber-700 bg-amber-50', blue: 'text-blue-700 bg-blue-50', slate: 'text-slate-700 bg-slate-100' };
    return <Card className="p-4"><div className="flex items-center justify-between gap-2"><span className={`flex h-9 w-9 items-center justify-center rounded-md ${tones[tone]}`}><Icon className="h-4 w-4" /></span><span className="text-2xl font-bold text-slate-900">{value}</span></div><p className="mt-3 text-xs font-medium text-slate-500">{label}</p></Card>;
}