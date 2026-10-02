import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download, FileText } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';

export default function ParentReportCards({ student, reportCards }) {
    return (
        <AuthenticatedLayout title={`Bulletins · ${student.name}`}>
            <Head title={`Bulletins · ${student.name}`} />
            <div className="space-y-6">
                <Link href={route('parent.children.show', student.id)} className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-teal-700"><ArrowLeft className="h-4 w-4" />Retour au dossier</Link>
                <div><p className="text-xs font-semibold uppercase text-teal-700">Espace parent · {student.class_name}</p><h1 className="mt-1 text-2xl font-bold text-slate-950">Bulletins de {student.name}</h1></div>
                {reportCards.length === 0 ? <Card className="p-12 text-center"><FileText className="mx-auto h-9 w-9 text-slate-300" /><p className="mt-3 font-semibold text-slate-800">Aucun bulletin publié</p><p className="mt-1 text-sm text-slate-500">Les bulletins seront affichés ici après publication par l’établissement.</p></Card> : (
                    <div className="space-y-3">{reportCards.map((card) => <Card key={card.id} className="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center"><div><div className="flex items-center gap-2"><Badge tone="green">Publié</Badge><span className="text-xs text-slate-500">Version {card.version}</span></div><h2 className="mt-2 text-lg font-bold text-slate-900">{card.period} · {card.academic_year}</h2><p className="mt-1 text-sm text-slate-600">Moyenne générale <strong>{Number(card.general_average).toFixed(2)} / 20</strong>{card.rank ? ` · Rang ${card.rank} / ${card.total_students}` : ''}</p><p className="mt-1 text-xs text-slate-500">Publié le {card.published_at}</p></div><div className="flex gap-2"><Link href={route('parent.report-cards.show', card.id)}><Button variant="secondary">Consulter</Button></Link><a href={route('parent.report-cards.pdf', card.id)}><Button><Download className="h-4 w-4" />PDF</Button></a></div></Card>)}</div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}