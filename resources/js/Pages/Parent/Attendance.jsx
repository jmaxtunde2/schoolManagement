import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Clock3, FileCheck2, Send, UserX } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Modal from '@/Components/UI/Modal';
import Pagination from '@/Components/UI/Pagination';
import Textarea from '@/Components/UI/Textarea';

const statusLabels = { present: 'Présent', absent: 'Absence', late: 'Retard' };
const statusTones = { present: 'green', absent: 'red', late: 'amber' };
const justificationLabels = { pending: 'En attente', approved: 'Justifiée', rejected: 'Refusée' };

export default function Attendance({ student, records, stats }) {
    const [justifying, setJustifying] = useState(null);
    const form = useForm({ reason: '' });

    function submitJustification(event) {
        event.preventDefault();
        form.post(route('parent.attendance.justify', justifying.id), {
            preserveScroll: true,
            onSuccess: () => {
                setJustifying(null);
                form.reset();
            },
        });
    }

    function openJustification(record) {
        form.reset();
        form.clearErrors();
        setJustifying(record);
    }

    return (
        <AuthenticatedLayout title={`Assiduité · ${student.name}`}>
            <Head title={`Assiduité · ${student.name}`} />
            <div className="space-y-6">
                <Link href={route('parent.children.show', student.id)} className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-teal-700"><ArrowLeft className="h-4 w-4" />Retour au dossier</Link>
                <div>
                    <p className="text-xs font-semibold uppercase text-teal-700">Espace parent · {student.class_name || 'Classe non définie'}</p>
                    <h1 className="mt-1 text-2xl font-bold text-slate-950">Absences & retards</h1>
                    <p className="mt-1 text-sm text-slate-500">{student.name}{student.matricule ? ` · ${student.matricule}` : ''}</p>
                </div>

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <Metric icon={UserX} label="Absences" value={stats.absent} />
                    <Metric icon={FileCheck2} label="Justifiées" value={stats.justified} />
                    <Metric icon={Clock3} label="Retards" value={stats.late} />
                    <Metric icon={CalendarDays} label="Minutes de retard" value={stats.delay_minutes} />
                </div>

                <Card>
                    <Card.Header title="Historique d’assiduité" description={`${records.total} relevé(s)`} />
                    <Card.Body>
                        {records.data.length === 0 ? (
                            <div className="py-10 text-center">
                                <CalendarDays className="mx-auto h-8 w-8 text-slate-300" />
                                <p className="mt-3 font-medium text-slate-700">Aucun relevé disponible</p>
                                <p className="mt-1 text-sm text-slate-500">Les absences et retards enregistrés par l’établissement apparaîtront ici.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="text-left text-xs font-semibold uppercase text-slate-500"><tr><th className="py-3 pr-4">Date</th><th className="py-3 pr-4">Type</th><th className="py-3 pr-4">Motif / détail</th><th className="py-3 pr-4">Justification</th><th className="py-3 text-right">Action</th></tr></thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {records.data.map((record) => {
                                            const latest = record.justifications?.at(-1);
                                            const justified = Boolean(record.justified_at) || latest?.status === 'approved';
                                            const state = justified ? 'approved' : latest?.status ?? 'unjustified';
                                            const stateTone = state === 'approved' ? 'green' : state === 'pending' ? 'amber' : state === 'rejected' ? 'red' : 'slate';
                                            return (
                                                <tr key={record.id} className="align-top">
                                                    <td className="py-3 pr-4 whitespace-nowrap">{record.attendance_date}</td>
                                                    <td className="py-3 pr-4"><Badge tone={statusTones[record.status]}>{statusLabels[record.status]}</Badge>{record.status === 'late' && <span className="ml-2 text-xs text-slate-500">{record.delay_minutes} min</span>}</td>
                                                    <td className="py-3 pr-4">{record.status === 'late' ? `Retard de ${record.delay_minutes} minute(s)` : record.reason || record.note || latest?.reason || '—'}</td>
                                                    <td className="py-3 pr-4">{record.status === 'absent' ? <div><Badge tone={stateTone}>{justificationLabels[state] || 'Non justifiée'}</Badge>{latest?.status === 'rejected' && latest.reason && <p className="mt-1 max-w-52 text-xs text-slate-500">Motif soumis : {latest.reason}</p>}</div> : '—'}</td>
                                                    <td className="py-3 text-right">{record.status === 'absent' && !justified && latest?.status !== 'pending' && <Button size="sm" variant="secondary" onClick={() => openJustification(record)}>Justifier</Button>}{record.status === 'absent' && latest?.status === 'pending' && <span className="text-xs text-amber-700">Demande en cours</span>}</td>
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

            <Modal
                show={Boolean(justifying)}
                onClose={() => setJustifying(null)}
                title="Demander la justification de l’absence"
                footer={<><Button type="button" variant="secondary" onClick={() => setJustifying(null)}>Annuler</Button><Button onClick={submitJustification} loading={form.processing}><Send className="h-4 w-4" />Envoyer la demande</Button></>}
            >
                <form onSubmit={submitJustification} className="space-y-3">
                    <p className="text-sm text-slate-600">{student.name} · {justifying?.attendance_date}</p>
                    <label className="block text-sm font-medium text-slate-700">Motif de l’absence<Textarea className="mt-1.5" rows={5} value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} error={form.errors.reason} placeholder="Expliquez le motif de l’absence…" /></label>
                    {form.errors.reason && <p className="text-sm text-red-600">{form.errors.reason}</p>}
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

function Metric({ icon: Icon, label, value }) {
    return <Card className="p-4"><span className="flex h-9 w-9 items-center justify-center rounded-md bg-teal-50 text-teal-700"><Icon className="h-4 w-4" /></span><p className="mt-3 text-2xl font-bold text-slate-900">{value}</p><p className="mt-1 text-xs text-slate-500">{label}</p></Card>;
}