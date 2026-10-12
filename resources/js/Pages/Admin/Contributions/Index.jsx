import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { BadgePercent, HandCoins, Plus, Search, Trash2, Wallet } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import EmptyState from '@/Components/UI/EmptyState';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Pagination from '@/Components/UI/Pagination';
import Select from '@/Components/UI/Select';
import StatCard from '@/Components/UI/StatCard';
import Tabs from '@/Components/UI/Tabs';
import MoneyEvolutionChart from '@/Components/Finance/MoneyEvolutionChart';

const statusTones = { paid: 'green', partial: 'amber', pending: 'red' };

export default function Contributions({
    contributions,
    academicYears,
    students,
    filters,
    routePrefix,
    initialTab,
    currentAcademicYearId,
    can_manage,
    stats,
    evolution,
}) {
    const [activeTab, setActiveTab] = useState(initialTab);
    const [creating, setCreating] = useState(false);
    const [target, setTarget] = useState(null);

    const form = useForm({
        student_id: currentAcademicYearId ? '' : '',
        academic_year_id: currentAcademicYearId ? String(currentAcademicYearId) : (academicYears[0]?.id?.toString() ?? ''),
        amount_due: '',
        amount_paid: 0,
        paid_at: new Date().toISOString().slice(0, 10),
    });

    const money = (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F`;
    const remaining = Math.max(0, Number(form.data.amount_due) - Number(form.data.amount_paid));

    function submitContribution(event) {
        event.preventDefault();
        form.post(route(`${routePrefix}.contributions.store`), {
            preserveScroll: true,
            onSuccess: () => {
                setCreating(false);
                form.reset('student_id', 'amount_due', 'amount_paid', 'paid_at');
            },
        });
    }

    function applyFilters(event) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const params = Object.fromEntries(data.entries());
        Object.keys(params).forEach((key) => {
            if (!params[key]) {
                delete params[key];
            }
        });

        router.get(route(`${routePrefix}.contributions.index`), params, {
            preserveState: true,
            replace: true,
        });
    }

    function changeTab(key) {
        setActiveTab(key);
    }

    function remove() {
        router.delete(route(`${routePrefix}.contributions.destroy`, target.id), {
            preserveScroll: true,
            onSuccess: () => setTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Contributions">
            <Head title="Contributions" />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-violet-600">Gestion financière</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Contributions</h1>
                        <p className="mt-1 text-sm text-slate-500">Scolarité et frais annexes des élèves par année académique.</p>
                    </div>

                    {can_manage && (
                        <Button onClick={() => setCreating(true)}>
                            <Plus className="h-4 w-4" /> Saisir une contribution
                        </Button>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard icon={Wallet} label="Encaissé" value={money(stats.collected)} description="Total des paiements reçus" className="from-emerald-500 to-teal-600" />
                    <StatCard icon={HandCoins} label="Dû" value={money(stats.due)} description="Total des frais exigibles" className="from-violet-500 to-purple-600" />
                    <StatCard icon={Wallet} label="Reste à percevoir" value={money(stats.remaining)} description="Solde non encore réglé" className="from-amber-500 to-orange-600" />
                    <StatCard icon={BadgePercent} label="Taux de recouvrement" value={`${stats.rate} %`} description="Part des montants dûs encaissés" className="from-sky-500 to-blue-600" />
                </div>

                <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                    <Tabs
                        tabs={[
                            { key: 'liste', label: 'Liste des contributions' },
                            { key: 'evolution', label: 'Évolution' },
                        ]}
                        active={activeTab}
                        onChange={changeTab}
                    />

                    {activeTab === 'liste' ? (
                        <Card.Body className="p-4 sm:p-6">
                            <form onSubmit={applyFilters} className="mb-5 grid gap-3 md:grid-cols-5">
                                <div className="relative md:col-span-2">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input className="pl-9" name="search" defaultValue={filters.search} placeholder="Rechercher (élève, matricule)…" />
                                </div>
                                <Select name="academic_year_id" defaultValue={filters.academic_year_id}>
                                    <option value="">Toutes années</option>
                                    {academicYears.map((year) => (
                                        <option key={year.id} value={year.id}>{year.name}</option>
                                    ))}
                                </Select>
                                <Select name="status" defaultValue={filters.status}>
                                    <option value="">Tous statuts</option>
                                    <option value="paid">Payée</option>
                                    <option value="partial">Partielle</option>
                                    <option value="pending">En attente</option>
                                </Select>
                                <Button type="submit">Filtrer</Button>
                            </form>

                            {contributions.data.length === 0 ? (
                                <EmptyState
                                    icon={HandCoins}
                                    title="Aucune contribution enregistrée"
                                    description="Les contributions des élèves apparaîtront ici une fois saisies."
                                    action={can_manage && <Button onClick={() => setCreating(true)}><Plus className="h-4 w-4" />Saisir une contribution</Button>}
                                />
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead className="text-left text-xs font-semibold uppercase text-slate-500">
                                            <tr className="border-b border-slate-100">
                                                <th className="py-3 pr-4">Élève</th>
                                                <th className="py-3 pr-4">Année académique</th>
                                                <th className="py-3 pr-4 text-right">Dû</th>
                                                <th className="py-3 pr-4 text-right">Payé</th>
                                                <th className="py-3 pr-4 text-right">Reste</th>
                                                <th className="py-3 pr-4">Statut</th>
                                                {can_manage && <th className="py-3 text-right">Actions</th>}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {contributions.data.map((contribution) => (
                                                <tr key={contribution.id} className="align-top">
                                                    <td className="py-3 pr-4">
                                                        <p className="font-semibold text-slate-800">{contribution.student}</p>
                                                        <p className="mt-0.5 text-xs text-slate-400">{contribution.matricule}</p>
                                                    </td>
                                                    <td className="py-3 pr-4 whitespace-nowrap text-slate-600">{contribution.academic_year}</td>
                                                    <td className="py-3 pr-4 whitespace-nowrap text-right text-slate-600">{money(contribution.amount_due)}</td>
                                                    <td className="py-3 pr-4 whitespace-nowrap text-right font-semibold text-emerald-700">{money(contribution.amount_paid)}</td>
                                                    <td className="py-3 pr-4 whitespace-nowrap text-right text-slate-600">{money(contribution.remaining)}</td>
                                                    <td className="py-3 pr-4">
                                                        <Badge tone={statusTones[contribution.status]}>{contribution.status_label}</Badge>
                                                        {contribution.paid_at && <span className="mt-1 block text-[11px] text-slate-400">le {contribution.paid_at}</span>}
                                                    </td>
                                                    {can_manage && (
                                                        <td className="py-3 text-right">
                                                            <button
                                                                type="button"
                                                                onClick={() => setTarget(contribution)}
                                                                className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </button>
                                                        </td>
                                                    )}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            <div className="mt-4 border-t border-slate-100 pt-4"><Pagination meta={contributions} /></div>
                        </Card.Body>
                    ) : (
                        <Card.Body className="p-4 sm:p-6">
                            <MoneyEvolutionChart
                                evolution={evolution}
                                color="#0ea5e9"
                                legend="Contributions encaissées"
                                emptyTitle="Aucun encaissement sur ces douze mois"
                            />
                        </Card.Body>
                    )}
                </Card>
            </div>

            <Modal
                show={creating}
                onClose={() => setCreating(false)}
                title="Saisir une contribution"
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setCreating(false)}>Annuler</Button>
                        <Button onClick={submitContribution} loading={form.processing}><Plus className="h-4 w-4" />Enregistrer</Button>
                    </>
                }
            >
                <form onSubmit={submitContribution} className="space-y-4">
                    <label className="block text-sm font-semibold text-slate-700">
                        Élève
                        <Select className="mt-1.5" value={form.data.student_id} onChange={(e) => form.setData('student_id', e.target.value)} error={form.errors.student_id}>
                            <option value="">— Choisir un élève —</option>
                            {students.map((student) => (
                                <option key={student.id} value={student.id}>{student.name} · {student.matricule}</option>
                            ))}
                        </Select>
                    </label>
                    <label className="block text-sm font-semibold text-slate-700">
                        Année académique
                        <Select className="mt-1.5" value={form.data.academic_year_id} onChange={(e) => form.setData('academic_year_id', e.target.value)} error={form.errors.academic_year_id}>
                            {academicYears.map((year) => (
                                <option key={year.id} value={year.id}>{year.name}</option>
                            ))}
                        </Select>
                    </label>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <label className="block text-sm font-semibold text-slate-700">
                            Montant dû (F)
                            <Input className="mt-1.5" type="number" min="1" value={form.data.amount_due} onChange={(e) => form.setData('amount_due', e.target.value)} error={form.errors.amount_due} placeholder="50000" />
                        </label>
                        <label className="block text-sm font-semibold text-slate-700">
                            Montant payé (F)
                            <Input className="mt-1.5" type="number" min="0" value={form.data.amount_paid} onChange={(e) => form.setData('amount_paid', e.target.value)} error={form.errors.amount_paid} placeholder="0" />
                        </label>
                    </div>
                    {Number(form.data.amount_paid) > 0 && (
                        <label className="block text-sm font-semibold text-slate-700">
                            Date de paiement
                            <Input className="mt-1.5" type="date" max={new Date().toISOString().slice(0, 10)} value={form.data.paid_at} onChange={(e) => form.setData('paid_at', e.target.value)} error={form.errors.paid_at} />
                        </label>
                    )}
                    <p className="rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-800">
                        Reste à percevoir : <span className="font-bold">{money(remaining)}</span>
                    </p>
                </form>
            </Modal>

            <ConfirmDialog
                show={Boolean(target)}
                onClose={() => setTarget(null)}
                onConfirm={remove}
                title="Supprimer la contribution"
                description={`Supprimer la contribution de ${target?.student} (${money(target?.amount_due)}) ? Cette action est irréversible.`}
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}