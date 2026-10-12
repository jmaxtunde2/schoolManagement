import { useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { BadgeEuro, Check, Clock, Save, Undo2, Users } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import EmptyState from '@/Components/UI/EmptyState';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import StatCard from '@/Components/UI/StatCard';
import Tabs from '@/Components/UI/Tabs';
import MoneyEvolutionChart from '@/Components/Finance/MoneyEvolutionChart';
import { cn } from '@/Utils/cn';

const months = [
    'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
    'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
];

const statusTones = { pending: 'amber', approved: 'green', rejected: 'red' };

export default function Salaries({ month, salaries, history, routePrefix, can_manage, evolution }) {
    const [activeTab, setActiveTab] = useState('feuille');

    const money = (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F`;

    const [local, setLocal] = useState(() => Object.fromEntries(salaries.map((member) => [member.id, member.amount ?? ''])));

    const form = useForm({ month });

    const sheet = history.find((entry) => entry.month === month);
    const selectedCount = Object.values(local).filter((added) => Number(added) > 0).length;
    const sheetTotal = useMemo(
        () => Object.values(local).reduce((sum, added) => sum + (Number(added) || 0), 0),
        [local],
    );
    const dirty = selectedCount !== salaries.filter((member) => Number(member.amount) > 0).length;
    const unchanged = selectedCount === 0 && !sheet;

    const monthOptions = useMemo(() => {
        const now = new Date();
        return Array.from({ length: 12 }, (_, index) => {
            const date = new Date(now.getFullYear(), now.getMonth() - index, 1);
            const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

            return { key, label: `${months[date.getMonth()]} ${date.getFullYear()}` };
        });
    }, []);

    function changeMonth(key) {
        router.get(route(`${routePrefix}.salaries.index`), { month: key }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function saveSheet(event) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            lines: salaries
                .map((member) => ({ staff_id: member.id, amount: Number(local[member.id]) || null }))
                .filter((line) => line.amount !== null),
        })).post(route(`${routePrefix}.salaries.save`), {
            preserveScroll: true,
        });
    }

    const errors = form.errors;
    const countError = Number(errors.lines?.length ?? 0);

    return (
        <AuthenticatedLayout title="Salaires">
            <Head title="Salaires" />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-violet-600">Gestion financière</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Salaires</h1>
                        <p className="mt-1 text-sm text-slate-500">Feuille de paie mensuelle de l'ensemble du personnel.</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <label className="text-sm font-medium text-slate-600">
                            Mois
                            <Select className="mt-1.5" value={month} onChange={(e) => changeMonth(e.target.value)}>
                                {monthOptions.map((option) => (
                                    <option key={option.key} value={option.key}>{option.label}</option>
                                ))}
                            </Select>
                        </label>
                        <Button onClick={saveSheet} loading={form.processing}>
                            <Save className="h-4 w-4" /> Enregistrer la paie
                        </Button>
                    </div>
                </div>

                {sheet && (
                    <div className={cn(
                        'flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm',
                        sheet.status === 'approved' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800',
                    )}>
                        {sheet.status === 'approved'
                            ? <><Check className="h-5 w-5 shrink-0" /> Paie de {sheet.month_label} enregistrée et approuvée ({money(sheet.amount)}).</>
                            : <><Clock className="h-5 w-5 shrink-0" /> Paie de {sheet.month_label} en attente d'approbation de la direction.</>}
                    </div>
                )}

                {!sheet && selectedCount > 0 && (
                    <div className="flex items-center gap-3 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800">
                        <Undo2 className="h-5 w-5 shrink-0" /> La paie de ce mois n'a pas encore été enregistrée.
                    </div>
                )}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard icon={BadgeEuro} label="Total du mois" value={money(sheetTotal)} description={selectedCount ? `${selectedCount} membre(s) renseigné(s)` : 'Aucun montant saisi'} className="from-violet-500 to-purple-600" />
                    <StatCard icon={Users} label="Personnel" value={salaries.length} description="Éligible à la paie mensuelle" className="from-indigo-500 to-blue-600" />
                    <StatCard icon={Clock} label="Statut actuel" value={sheet ? (sheet.status === 'approved' ? 'Approuvée' : 'En attente') : 'Non enregistrée'} description={sheet ? money(sheet.amount) : `Total saisi : ${money(sheetTotal)}`} className="from-amber-500 to-orange-600" />
                    <StatCard icon={Check} label="Feuilles" value={history.length} description="Mois déjà enregistrés" className="from-emerald-500 to-teal-600" />
                </div>

                <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                    <Tabs
                        tabs={[
                            { key: 'feuille', label: 'Feuille de paie du mois' },
                            { key: 'evolution', label: 'Évolution' },
                            { key: 'historique', label: 'Historique des paies' },
                        ]}
                        active={activeTab}
                        onChange={setActiveTab}
                    />

                    {activeTab === 'feuille' ? (
                        <Card.Body className="p-4 sm:p-6">
                            {countError > 0 && (
                                <p className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    {typeof errors.lines === 'string' ? errors.lines : 'Vérifiez les montants saisis (valeurs numériques attendues).'}
                                </p>
                            )}

                            <form onSubmit={saveSheet} className="space-y-4">
                                <div className="overflow-x-auto rounded-2xl border border-slate-100">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                            <tr>
                                                <th className="py-3 pl-4 pr-4">Membre du personnel</th>
                                                <th className="py-3 pr-4">Fonction</th>
                                                <th className="py-3 pr-4 text-right">Salaire du mois (F)</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {salaries.map((member) => {
                                                const error = errors[`lines.${salaries.indexOf(member)}.amount`];

                                                return (
                                                    <tr key={member.id}>
                                                        <td className="py-3 pl-4 pr-4 font-semibold text-slate-800">{member.name}</td>
                                                        <td className="py-3 pr-4">
                                                            <span className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{member.role_label}</span>
                                                        </td>
                                                        <td className="py-3 pr-4">
                                                            <div className="flex justify-end gap-2">
                                                                {error && <p className="self-center text-xs text-red-600">{error}</p>}
                                                                <Input
                                                                    type="number"
                                                                    min="0"
                                                                    value={local[member.id]}
                                                                    onChange={(e) => setLocal({ ...local, [member.id]: e.target.value })}
                                                                    className="w-40 text-right"
                                                                    placeholder="0"
                                                                    aria-label={`Salaire de ${member.name}`}
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                        <tfoot>
                                            <tr className="border-t border-slate-100 bg-slate-50">
                                                <td className="py-3 pl-4 pr-4 font-bold text-slate-900">Total</td>
                                                <td />
                                                <td className="py-3 pr-4 text-right font-bold text-violet-700">{money(sheetTotal)}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p className="text-xs text-slate-500">
                                        Laisser vide un montant pour retirer le membre de la paie du mois. La saisie est enregistrée en une seule fois.
                                    </p>
                                    <div className="flex items-center gap-2">
                                        {dirty && !unchanged && <span className="text-xs font-medium text-violet-600">Modifications à enregistrer</span>}
                                        <Button onClick={saveSheet} loading={form.processing}>
                                            <Save className="h-4 w-4" /> Enregistrer la paie
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </Card.Body>
                    ) : activeTab === 'evolution' ? (
                        <Card.Body className="p-4 sm:p-6">
                            <MoneyEvolutionChart
                                evolution={evolution}
                                color="#e11d48"
                                legend="Salaires versés"
                                emptyTitle="Aucun salaire versé sur ces douze mois"
                            />
                        </Card.Body>
                    ) : (
                        <Card.Body className="p-4 sm:p-6">
                            {history.length === 0 ? (
                                <EmptyState
                                    icon={BadgeEuro}
                                    title="Aucune paie enregistrée"
                                    description="Les feuilles de paie mensuelles apparaîtront ici une fois enregistrées."
                                />
                            ) : (
                                <div className="space-y-3">
                                    {history.map((entry) => (
                                        <div key={entry.id} className="flex flex-col gap-2 rounded-2xl border border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="flex items-center gap-4">
                                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                                                    <BadgeEuro className="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-slate-800">{entry.month_label}</p>
                                                    <p className="text-xs text-slate-500">{entry.reference} · {entry.staff_count} bénéficiaire(s){entry.approved_at ? ` · approuvée le ${entry.approved_at}` : ''}</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <span className="font-bold text-slate-800">{money(entry.amount)}</span>
                                                <Badge tone={statusTones[entry.status]}>{entry.status_label}</Badge>
                                                <Button size="sm" variant="secondary" onClick={() => changeMonth(entry.month)}>Revoir</Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card.Body>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}