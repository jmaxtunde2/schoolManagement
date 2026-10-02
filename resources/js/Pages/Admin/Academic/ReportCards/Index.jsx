import { Head, Link, router } from '@inertiajs/react';
import { FileText, Filter } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Pagination from '@/Components/UI/Pagination';
import Select from '@/Components/UI/Select';

const labels = { draft: 'Brouillon', generated: 'Généré', published: 'Publié' };
const tones = { draft: 'slate', generated: 'amber', published: 'green' };

export default function ReportCardsIndex({ reportCards, classes, periods, filters, routePrefix }) {
    function applyFilters(event) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        router.get(route(`${routePrefix}.report-cards.index`), Object.fromEntries(data.entries()), { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout title="Bulletins scolaires">
            <Head title="Bulletins scolaires" />
            <div className="space-y-6">
                <div><p className="text-xs font-semibold uppercase text-emerald-700">Années et périodes</p><h1 className="mt-1 text-2xl font-bold text-slate-950">Bulletins scolaires</h1></div>
                <Card>
                    <Card.Header title="Bulletins générés" description={`${reportCards.total} bulletin(s)`} />
                    <Card.Body>
                        <form onSubmit={applyFilters} className="mb-5 grid gap-3 sm:grid-cols-4">
                            <Select name="class_id" defaultValue={filters.class_id ?? ''}><option value="">Toutes les classes</option>{classes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</Select>
                            <Select name="period_id" defaultValue={filters.period_id ?? ''}><option value="">Toutes les périodes</option>{periods.map((item) => <option key={item.id} value={item.id}>{item.academic_year} · {item.name}</option>)}</Select>
                            <Select name="status" defaultValue={filters.status ?? ''}><option value="">Tous les statuts</option><option value="draft">Brouillon</option><option value="generated">Généré</option><option value="published">Publié</option></Select>
                            <Button type="submit" variant="secondary"><Filter className="h-4 w-4" />Filtrer</Button>
                        </form>
                        {reportCards.data.length === 0 ? <div className="border-t border-slate-100 py-10 text-center"><FileText className="mx-auto h-8 w-8 text-slate-300" /><p className="mt-3 font-medium text-slate-700">Aucun bulletin</p><p className="mt-1 text-sm text-slate-500">Calculez les résultats avant de générer les bulletins.</p></div> : (
                            <div className="overflow-x-auto border-t border-slate-100">
                                <table className="min-w-full text-sm"><thead className="text-left text-xs font-semibold uppercase text-slate-500"><tr><th className="py-3 pr-4">Élève</th><th className="py-3 pr-4">Classe</th><th className="py-3 pr-4">Année / période</th><th className="py-3 pr-4 text-right">Moyenne</th><th className="py-3 pr-4 text-center">Rang</th><th className="py-3 pr-4">Statut</th><th className="py-3 text-right">Version</th></tr></thead><tbody className="divide-y divide-slate-100">{reportCards.data.map((card) => <tr key={card.id}><td className="py-3 pr-4 font-semibold text-slate-800">{card.student.first_name} {card.student.last_name}<span className="mt-0.5 block text-xs font-normal text-slate-500">{card.student.matricule || '—'}</span></td><td className="py-3 pr-4">{card.class_room?.name}</td><td className="py-3 pr-4">{card.academic_year?.name}<span className="block text-xs text-slate-500">{card.period?.name}</span></td><td className="py-3 pr-4 text-right font-semibold">{card.general_average} / 20</td><td className="py-3 pr-4 text-center">{card.rank ? `${card.rank} / ${card.total_students}` : '—'}</td><td className="py-3 pr-4"><Badge tone={tones[card.status]}>{labels[card.status]}</Badge></td><td className="py-3 text-right"><Link href={route(`${routePrefix}.report-cards.show`, card.id)} className="font-semibold text-emerald-700">V{card.version} · Consulter</Link></td></tr>)}</tbody></table>
                            </div>
                        )}
                        <div className="mt-4 border-t border-slate-100 pt-4"><Pagination meta={reportCards} /></div>
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}