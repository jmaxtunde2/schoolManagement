import { useEffect, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Download, FileCheck2, RefreshCw, Save } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import Textarea from '@/Components/UI/Textarea';

const labels = { draft: 'Brouillon', generated: 'À vérifier', published: 'Publié' };
const tones = { draft: 'slate', generated: 'amber', published: 'green' };

export default function ReportCardShow({ reportCard, routePrefix, canEditComments, canPublish, canRegenerate }) {
    const [confirmPublish, setConfirmPublish] = useState(false);
    const form = useForm({
        appreciation: reportCard.appreciation ?? '',
        items: reportCard.items.map((item) => ({
            id: item.id,
            teacher_comment: item.teacher_comment ?? '',
            appreciation: item.appreciation ?? '',
        })),
    });
    const snapshot = reportCard.snapshot;

    useEffect(() => {
        form.setData({
            appreciation: reportCard.appreciation ?? '',
            items: reportCard.items.map((item) => ({
                id: item.id,
                teacher_comment: item.teacher_comment ?? '',
                appreciation: item.appreciation ?? '',
            })),
        });
    }, [reportCard.id, reportCard.updated_at]);

    function updateItem(index, key, value) {
        form.setData('items', form.data.items.map((item, itemIndex) => itemIndex === index ? { ...item, [key]: value } : item));
    }

    function saveComments(event) {
        event.preventDefault();
        form.put(route(`${routePrefix}.report-cards.comments.update`, reportCard.id), { preserveScroll: true });
    }

    function publish() {
        router.post(route(`${routePrefix}.report-cards.publish`, reportCard.id), {}, { preserveScroll: true, onSuccess: () => setConfirmPublish(false) });
    }

    function regenerate() {
        router.post(route(`${routePrefix}.report-cards.regenerate`, reportCard.id), {}, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout title="Bulletin scolaire">
            <Head title={`Bulletin · ${snapshot.student.name}`} />
            <div className="mx-auto max-w-5xl space-y-6">
                <Link href={route(`${routePrefix}.report-cards.index`)} className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-emerald-700"><ArrowLeft className="h-4 w-4" />Retour aux bulletins</Link>
                <Card className="overflow-hidden">
                    <div className="flex flex-col justify-between gap-5 border-b border-slate-200 p-5 sm:flex-row sm:items-start sm:p-7">
                        <div className="flex items-start gap-4">
                            {snapshot.school.logo_path && <img src={`/storage/${snapshot.school.logo_path}`} alt={`Logo ${snapshot.school.name}`} className="h-14 w-14 object-contain" />}
                            <div><p className="text-xs font-semibold uppercase text-slate-500">{snapshot.school.name}</p><h1 className="mt-1 text-2xl font-bold text-slate-950">Bulletin scolaire</h1><p className="mt-1 text-sm text-slate-600">{snapshot.academic_year.name} · {snapshot.period.name}</p></div>
                        </div>
                        <div className="flex flex-wrap items-center gap-2"><Badge tone={tones[reportCard.status]}>{labels[reportCard.status]}</Badge><span className="text-xs text-slate-500">Version {reportCard.version}</span></div>
                    </div>

                    <div className="grid gap-4 border-b border-slate-100 bg-slate-50/60 p-5 sm:grid-cols-3 sm:p-7">
                        <Identity label="Élève" value={snapshot.student.name} detail={snapshot.student.matricule || 'Sans matricule'} />
                        <Identity label="Classe" value={snapshot.class.name} />
                        <Identity label="Période" value={snapshot.period.name} detail={snapshot.academic_year.name} />
                    </div>

                    <div className="p-5 sm:p-7">
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm"><thead className="text-left text-xs font-semibold uppercase text-slate-500"><tr><th className="py-3 pr-4">Matière</th><th className="py-3 pr-4 text-right">Moyenne</th><th className="py-3 pr-4 text-center">Coef.</th><th className="py-3 pr-4">Appréciation</th><th className="py-3">Commentaire</th></tr></thead><tbody className="divide-y divide-slate-100">{reportCard.items.map((item, index) => <tr key={item.id} className="align-top"><td className="py-3 pr-4"><p className="font-semibold text-slate-800">{item.subject_name}</p><p className="text-xs text-slate-500">{item.evaluation_count} évaluation(s)</p></td><td className="py-3 pr-4 text-right font-bold">{item.average?.toFixed(2) ?? '—'} / 20</td><td className="py-3 pr-4 text-center">{item.coefficient}</td><td className="py-3 pr-4">{canEditComments ? <Textarea rows={2} value={form.data.items[index].appreciation} onChange={(event) => updateItem(index, 'appreciation', event.target.value)} /> : item.appreciation || '—'}</td><td className="py-3">{canEditComments ? <Textarea rows={2} value={form.data.items[index].teacher_comment} onChange={(event) => updateItem(index, 'teacher_comment', event.target.value)} /> : item.teacher_comment || '—'}</td></tr>)}</tbody></table>
                        </div>

                        <div className="mt-6 grid gap-4 sm:grid-cols-3">
                            <Metric label="Moyenne générale" value={`${Number(reportCard.general_average).toFixed(2)} / 20`} />
                            <Metric label="Classement" value={reportCard.rank ? `${reportCard.rank}e / ${reportCard.total_students}` : '—'} />
                            <Metric label="Émis le" value={reportCard.published_at || reportCard.generated_at} />
                        </div>

                        <div className="mt-5 grid gap-3 sm:grid-cols-2">
                            <Attendance label="Absences" value={reportCard.attendance_summary.absences} />
                            <Attendance label="Justifiées / non justifiées" value={`${reportCard.attendance_summary.justified_absences} / ${reportCard.attendance_summary.unjustified_absences}`} />
                            <Attendance label="Retards" value={reportCard.attendance_summary.late_count} />
                            <Attendance label="Minutes de retard" value={reportCard.attendance_summary.delay_minutes} />
                        </div>

                        {canEditComments && <label className="mt-6 block text-sm font-semibold text-slate-700">Appréciation générale<Textarea className="mt-1.5" rows={3} value={form.data.appreciation} onChange={(event) => form.setData('appreciation', event.target.value)} /></label>}

                        <div className="mt-7 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
                            {reportCard.status === 'published' && <a href={route(`${routePrefix}.report-cards.pdf`, reportCard.id)} className="inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700"><Download className="h-4 w-4" />Télécharger le PDF</a>}
                            {canEditComments && <Button variant="secondary" onClick={saveComments} loading={form.processing}><Save className="h-4 w-4" />Enregistrer les appréciations</Button>}
                            {canPublish && <Button onClick={() => setConfirmPublish(true)}><FileCheck2 className="h-4 w-4" />Publier le bulletin</Button>}
                            {canRegenerate && <Button variant="danger" onClick={regenerate}><RefreshCw className="h-4 w-4" />Régénérer une version</Button>}
                        </div>
                    </div>
                    <div className="grid grid-cols-3 border-t border-slate-200 text-center text-xs text-slate-500"><div className="p-6">Professeur principal<div className="mt-8 border-t border-slate-300 pt-2">Signature</div></div><div className="p-6">Censeur<div className="mt-8 border-t border-slate-300 pt-2">Signature</div></div><div className="p-6">Direction<div className="mt-8 border-t border-slate-300 pt-2">Signature / cachet</div></div></div>
                </Card>
            </div>
            <ConfirmDialog show={confirmPublish} onClose={() => setConfirmPublish(false)} onConfirm={publish} title="Publier ce bulletin ?" description="La publication rendra ce bulletin officiel et accessible au parent. Toute correction ultérieure créera une nouvelle version." confirmLabel="Publier le bulletin" />
        </AuthenticatedLayout>
    );
}

function Identity({ label, value, detail }) {
    return <div><p className="text-xs font-semibold uppercase text-slate-500">{label}</p><p className="mt-1 font-semibold text-slate-900">{value}</p>{detail && <p className="mt-0.5 text-xs text-slate-500">{detail}</p>}</div>;
}

function Metric({ label, value }) {
    return <div className="border-l-2 border-emerald-600 pl-3"><p className="text-xs text-slate-500">{label}</p><p className="mt-1 text-lg font-bold text-slate-950">{value}</p></div>;
}

function Attendance({ label, value }) {
    return <div className="flex items-center justify-between border-b border-slate-100 py-2 text-sm"><span className="text-slate-600">{label}</span><span className="font-semibold text-slate-900">{value ?? 0}</span></div>;
}