import { Head } from '@inertiajs/react';
import { ArrowLeft, Clock3, GraduationCap, ShieldCheck, UserX } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import Pagination from '@/Components/UI/Pagination';

const statusLabels = { present: 'Présent', absent: 'Absent', late: 'Retard' };
const statusTones = { present: 'green', absent: 'red', late: 'amber' };

export default function AttendanceStudent({ student, records, stats, routePrefix }) {
    return (
        <AuthenticatedLayout title={`Assiduité · ${student.name}`}>
            <Head title={`Assiduité · ${student.name}`} />
            <div className="space-y-6">
                <a href={route(`${routePrefix}.attendance.index`)} className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-emerald-700"><ArrowLeft className="h-4 w-4" />Retour aux relevés</a>
                <div><p className="text-xs font-semibold uppercase text-emerald-700">Historique élève</p><h1 className="mt-1 text-2xl font-bold text-slate-950">{student.name}</h1><p className="mt-1 text-sm text-slate-500">{student.class_name || 'Classe non définie'} {student.matricule ? `· ${student.matricule}` : ''}</p></div>

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
                    <Metric icon={UserX} label="Absences" value={stats.absent} />
                    <Metric icon={ShieldCheck} label="Justifiées" value={stats.justified} />
                    <Metric icon={UserX} label="Non justifiées" value={stats.unjustified} />
                    <Metric icon={Clock3} label="Retards" value={stats.late} />
                    <Metric icon={GraduationCap} label="Minutes de retard" value={stats.delay_minutes} />
                </div>

                <Card>
                    <Card.Header title="Historique" description={`${records.total} relevé(s)`} />
                    <Card.Body>
                        {records.data.length === 0 ? <p className="py-8 text-center text-sm text-slate-500">Aucun relevé d’assiduité pour cet élève.</p> : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead className="text-left text-xs font-semibold uppercase text-slate-500"><tr><th className="py-3 pr-4">Date</th><th className="py-3 pr-4">Type</th><th className="py-3 pr-4">Motif</th><th className="py-3 pr-4">Justification</th><th className="py-3">Enregistré par</th></tr></thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {records.data.map((record) => {
                                            const justification = record.justifications?.[0];
                                            const justificationLabel = record.justified_at ? 'Justifiée' : justification?.status === 'pending' ? 'En attente' : justification?.status === 'rejected' ? 'Refusée' : 'Non justifiée';
                                            return <tr key={record.id}><td className="py-3 pr-4">{record.attendance_date}</td><td className="py-3 pr-4"><Badge tone={statusTones[record.status]}>{statusLabels[record.status]}</Badge>{record.status === 'late' && <span className="ml-2 text-slate-500">{record.delay_minutes} min</span>}</td><td className="py-3 pr-4">{record.reason || record.note || justification?.reason || '—'}</td><td className="py-3 pr-4">{record.status === 'absent' ? justificationLabel : '—'}</td><td className="py-3">{record.recorder?.name || '—'}</td></tr>;
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <div className="mt-4 border-t border-slate-100 pt-4"><Pagination meta={records} /></div>
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function Metric({ icon: Icon, label, value }) {
    return <Card className="p-4"><span className="flex h-9 w-9 items-center justify-center rounded-md bg-slate-100 text-slate-700"><Icon className="h-4 w-4" /></span><p className="mt-3 text-2xl font-bold text-slate-900">{value}</p><p className="mt-1 text-xs text-slate-500">{label}</p></Card>;
}