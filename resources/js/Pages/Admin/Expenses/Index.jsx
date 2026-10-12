import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    BadgeEuro,
    Check,
    Plus,
    Search,
    Settings2,
    ShieldAlert,
    Trash2,
    Wallet,
    X,
} from 'lucide-react';

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
import Textarea from '@/Components/UI/Textarea';
import MoneyEvolutionChart from '@/Components/Finance/MoneyEvolutionChart';
import { cn } from '@/Utils/cn';

const statusTones = { pending: 'amber', approved: 'green', rejected: 'red' };

export default function Expenses({
    expenses,
    categories,
    statuses,
    filters,
    routePrefix,
    initialTab,
    can_manage,
    threshold,
    stats,
    evolution,
}) {
    const [activeTab, setActiveTab] = useState(initialTab);
    const [creating, setCreating] = useState(false);
    const [editingThreshold, setEditingThreshold] = useState(false);
    const [target, setTarget] = useState(null);

    const form = useForm({
        category: categories[0]?.value ?? '',
        label: '',
        amount: '',
        expense_date: new Date().toISOString().slice(0, 10),
        justification: '',
    });

    const thresholdForm = useForm({ threshold });

    const money = (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F`;

    function submitExpense(event) {
        event.preventDefault();
        form.post(route(`${routePrefix}.expenses.store`), {
            preserveScroll: true,
            onSuccess: () => {
                setCreating(false);
                form.reset();
            },
        });
    }

    function submitThreshold(event) {
        event.preventDefault();
        thresholdForm.put(route(`${routePrefix}.expenses.settings`), {
            preserveScroll: true,
            onSuccess: () => setEditingThreshold(false),
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

        router.get(route(`${routePrefix}.expenses.index`), params, {
            preserveState: true,
            replace: true,
        });
    }

    function changeTab(key) {
        setActiveTab(key);
    }

    function approve(expense) {
        router.post(route(`${routePrefix}.expenses.approve`, expense.id), {}, {
            preserveScroll: true,
        });
    }

    function reject() {
        router.post(route(`${routePrefix}.expenses.reject`, target.id), {}, {
            preserveScroll: true,
            onSuccess: () => setTarget(null),
        });
    }

    function remove() {
        router.delete(route(`${routePrefix}.expenses.destroy`, target.id), {
            preserveScroll: true,
            onSuccess: () => setTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Dépenses">
            <Head title="Dépenses" />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-violet-600">Gestion financière</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dépenses</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Toute dépense supérieure à {money(threshold)} doit être approuvée par l'administrateur.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {can_manage && (
                            <Button variant="secondary" onClick={() => setEditingThreshold(true)}>
                                <Settings2 className="h-4 w-4" /> Seuil : {money(threshold)}
                            </Button>
                        )}
                        <Button onClick={() => setCreating(true)}>
                            <Plus className="h-4 w-4" /> Saisir une dépense
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={Wallet}
                        label="Dépenses du mois"
                        value={money(stats.month_total)}
                        description={`${stats.month_count} opération(s)`}
                        className="from-violet-500 to-purple-600"
                    />
                    <StatCard
                        icon={ShieldAlert}
                        label="À approuver"
                        value={money(stats.pending_total)}
                        description={`${stats.pending_count} en attente · seuil ${money(threshold)}`}
                        className="from-amber-500 to-orange-600"
                    />
                    <StatCard
                        icon={BadgeEuro}
                        label="Salaires du mois"
                        value={money(stats.salary_month)}
                        description="Feuilles de paie du mois en cours"
                        className="from-rose-500 to-red-600"
                    />
                    <StatCard
                        icon={Check}
                        label="Imputées ce mois"
                        value={stats.month_count}
                        description="Opérations enregistrées"
                        className="from-emerald-500 to-teal-600"
                    />
                </div>

                <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                    <Tabs
                        tabs={[
                            { key: 'liste', label: 'Liste des dépenses' },
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
                                    <Input className="pl-9" name="search" defaultValue={filters.search} placeholder="Rechercher (libellé, référence)…" />
                                </div>
                                <Select name="category" defaultValue={filters.category}>
                                    <option value="">Toutes catégories</option>
                                    {categories.map((category) => (
                                        <option key={category.value} value={category.value}>{category.label}</option>
                                    ))}
                                </Select>
                                <Select name="status" defaultValue={filters.status}>
                                    <option value="">Tous statuts</option>
                                    {statuses.map((status) => (
                                        <option key={status.value} value={status.value}>{status.label}</option>
                                    ))}
                                </Select>
                                <div className="flex gap-2">
                                    <Input type="date" name="date_from" defaultValue={filters.date_from} aria-label="Du" />
                                    <Input type="date" name="date_to" defaultValue={filters.date_to} aria-label="Au" />
                                </div>
                                <div className="md:col-span-5">
                                    <Button type="submit">Filtrer</Button>
                                </div>
                            </form>

                            {expenses.data.length === 0 ? (
                                <EmptyState
                                    icon={Wallet}
                                    title="Aucune dépense enregistrée"
                                    description="Saisissez la première dépense de l'établissement."
                                    action={<Button onClick={() => setCreating(true)}><Plus className="h-4 w-4" />Saisir une dépense</Button>}
                                />
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead className="text-left text-xs font-semibold uppercase text-slate-500">
                                            <tr className="border-b border-slate-100">
                                                <th className="py-3 pr-4">Référence</th>
                                                <th className="py-3 pr-4">Date</th>
                                                <th className="py-3 pr-4">Dépense</th>
                                                <th className="py-3 pr-4">Catégorie</th>
                                                <th className="py-3 pr-4 text-right">Montant</th>
                                                <th className="py-3 pr-4">Statut</th>
                                                {can_manage && <th className="py-3 text-right">Actions</th>}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {expenses.data.map((expense) => (
                                                <tr key={expense.id} className="align-top">
                                                    <td className="py-3 pr-4 whitespace-nowrap text-xs font-mono text-slate-500">{expense.reference ?? '—'}</td>
                                                    <td className="py-3 pr-4 whitespace-nowrap text-slate-600">{expense.expense_date}</td>
                                                    <td className="py-3 pr-4">
                                                        <p className="font-semibold text-slate-800">{expense.label}</p>
                                                        {expense.justification && <p className="mt-0.5 max-w-56 text-xs text-slate-500">{expense.justification}</p>}
                                                        <p className="mt-0.5 text-[11px] text-slate-400">par {expense.creator_name ?? '—'}</p>
                                                    </td>
                                                    <td className="py-3 pr-4 whitespace-nowrap">
                                                        <span className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{expense.category_label}</span>
                                                    </td>
                                                    <td className="py-3 pr-4 whitespace-nowrap text-right font-bold text-slate-800">{money(expense.amount)}</td>
                                                    <td className="py-3 pr-4"><Badge tone={statusTones[expense.status]}>{expense.status_label}</Badge></td>
                                                    {can_manage && (
                                                        <td className="py-3 text-right">
                                                            <div className="flex justify-end gap-1.5">
                                                                {expense.status === 'pending' && (
                                                                    <>
                                                                        <Button size="sm" variant="secondary" onClick={() => approve(expense)}>
                                                                            <Check className="h-3.5 w-3.5" /> Approuver
                                                                        </Button>
                                                                        <Button size="sm" variant="secondary" onClick={() => setTarget({ ...expense, action: 'reject' })} className="text-red-600 hover:border-red-200 hover:bg-red-50 hover:text-red-700">
                                                                            <X className="h-3.5 w-3.5" />
                                                                        </Button>
                                                                    </>
                                                                )}
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setTarget({ ...expense, action: 'delete' })}
                                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </button>
                                                            </div>
                                                        </td>
                                                    )}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            <div className="mt-4 border-t border-slate-100 pt-4"><Pagination meta={expenses} /></div>
                        </Card.Body>
                    ) : (
                        <Card.Body className="p-4 sm:p-6">
                            <MoneyEvolutionChart
                                evolution={evolution}
                                color="#7c3aed"
                                legend="Dépenses engagées"
                                emptyTitle="Aucune dépense sur ces douze mois"
                            />
                        </Card.Body>
                    )}
                </Card>
            </div>

            {/* Modal création */}
            <Modal
                show={creating}
                onClose={() => setCreating(false)}
                title="Saisir une dépense"
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setCreating(false)}>Annuler</Button>
                        <Button onClick={submitExpense} loading={form.processing}><Plus className="h-4 w-4" />Enregistrer</Button>
                    </>
                }
            >
                <form onSubmit={submitExpense} className="space-y-4">
                    <label className="block text-sm font-semibold text-slate-700">
                        Catégorie
                        <Select className="mt-1.5" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} error={form.errors.category}>
                            {categories.map((category) => (
                                <option key={category.value} value={category.value}>{category.label}</option>
                            ))}
                        </Select>
                    </label>
                    <label className="block text-sm font-semibold text-slate-700">
                        Libellé
                        <Input className="mt-1.5" value={form.data.label} onChange={(e) => form.setData('label', e.target.value)} error={form.errors.label} placeholder="Facture d'électricité, fournitures…" />
                    </label>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <label className="block text-sm font-semibold text-slate-700">
                            Montant (F)
                            <Input className="mt-1.5" type="number" min="1" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} error={form.errors.amount} placeholder="50000" />
                        </label>
                        <label className="block text-sm font-semibold text-slate-700">
                            Date
                            <Input className="mt-1.5" type="date" max={new Date().toISOString().slice(0, 10)} value={form.data.expense_date} onChange={(e) => form.setData('expense_date', e.target.value)} error={form.errors.expense_date} />
                        </label>
                    </div>
                    <label className="block text-sm font-semibold text-slate-700">
                        Justification
                        <Textarea className="mt-1.5" rows={3} value={form.data.justification} onChange={(e) => form.setData('justification', e.target.value)} error={form.errors.justification} placeholder="Contexte de la dépense (facultatif)…" />
                    </label>
                    <p className={cn('rounded-xl border p-3 text-xs leading-5', Number(form.data.amount) > threshold ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700')}>
                        {Number(form.data.amount) > threshold
                            ? `Le montant dépasse le seuil de ${money(threshold)} : cette dépense devra être approuvée par l'administrateur.`
                            : `Montant inférieur au seuil de ${money(threshold)} : la dépense sera enregistrée directement.`}
                    </p>
                </form>
            </Modal>

            {/* Modal seuil */}
            <Modal
                show={editingThreshold}
                onClose={() => setEditingThreshold(false)}
                title="Seuil d'approbation des dépenses"
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setEditingThreshold(false)}>Annuler</Button>
                        <Button onClick={submitThreshold} loading={thresholdForm.processing}><Check className="h-4 w-4" />Enregistrer</Button>
                    </>
                }
            >
                <form onSubmit={submitThreshold} className="space-y-3">
                    <p className="text-sm text-slate-600">Toute dépense dépassant ce montant doit être approuvée par l'administrateur.</p>
                    <label className="block text-sm font-semibold text-slate-700">
                        Montant (F)
                        <Input className="mt-1.5" type="number" min="1000" value={thresholdForm.data.threshold} onChange={(e) => thresholdForm.setData('threshold', e.target.value)} error={thresholdForm.errors.threshold} />
                    </label>
                </form>
            </Modal>

            <ConfirmDialog
                show={Boolean(target)}
                onClose={() => setTarget(null)}
                onConfirm={target?.action === 'reject' ? reject : remove}
                title={target?.action === 'reject' ? 'Rejeter la dépense' : 'Supprimer la dépense'}
                description={target?.action === 'reject'
                    ? `Rejeter la dépense « ${target?.label} » de ${money(target?.amount)} ?`
                    : `Supprimer définitivement la dépense « ${target?.label} » de ${money(target?.amount)} ? Cette action est irréversible.`}
                confirmLabel={target?.action === 'reject' ? 'Rejeter' : 'Supprimer'}
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}