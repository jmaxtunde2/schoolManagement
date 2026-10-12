import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { CircleDollarSign, Package, Plus, Search, ShoppingCart, Trash2 } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

export default function Sales({
    sales,
    categories,
    filters,
    routePrefix,
    initialTab,
    can_manage,
    stats,
    evolution,
}) {
    const [activeTab, setActiveTab] = useState(initialTab);
    const [creating, setCreating] = useState(false);
    const [target, setTarget] = useState(null);

    const form = useForm({
        category: categories[0]?.value ?? '',
        item: '',
        quantity: 1,
        unit_price: '',
        sold_at: new Date().toISOString().slice(0, 10),
        buyer: '',
    });

    const money = (value) => `${Number(value ?? 0).toLocaleString('fr-FR')} F`;
    const total = Number(form.data.quantity) * Number(form.data.unit_price) || 0;

    function submitSale(event) {
        event.preventDefault();
        form.post(route(`${routePrefix}.sales.store`), {
            preserveScroll: true,
            onSuccess: () => {
                setCreating(false);
                form.reset();
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

        router.get(route(`${routePrefix}.sales.index`), params, {
            preserveState: true,
            replace: true,
        });
    }

    function changeTab(key) {
        setActiveTab(key);
    }

    function remove() {
        router.delete(route(`${routePrefix}.sales.destroy`, target.id), {
            preserveScroll: true,
            onSuccess: () => setTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Ventes">
            <Head title="Ventes" />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-violet-600">Gestion financière</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Ventes</h1>
                        <p className="mt-1 text-sm text-slate-500">Uniformes, manuels, fournitures scolaires et autres ventes de l'établissement.</p>
                    </div>

                    <Button onClick={() => setCreating(true)}>
                        <Plus className="h-4 w-4" /> Saisir une vente
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard icon={CircleDollarSign} label="Recettes cumulées" value={money(stats.total_revenue)} description={`${stats.total_count} article(s) vendu(s)`} className="from-emerald-500 to-teal-600" />
                    <StatCard icon={ShoppingCart} label="Ventes du mois" value={money(stats.month_revenue)} description={`${stats.month_count} article(s) ce mois-ci`} className="from-sky-500 to-blue-600" />
                    <StatCard icon={Package} label="Articles du mois" value={stats.month_count} description="Quantités écoulées" className="from-indigo-500 to-violet-600" />
                    <StatCard icon={ShoppingCart} label="Total vendus" value={stats.total_count} description="Depuis le début" className="from-rose-500 to-pink-600" />
                </div>

                <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                    <Tabs
                        tabs={[
                            { key: 'liste', label: 'Liste des ventes' },
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
                                    <Input className="pl-9" name="search" defaultValue={filters.search} placeholder="Rechercher (article, acheteur)…" />
                                </div>
                                <Select name="category" defaultValue={filters.category}>
                                    <option value="">Toutes catégories</option>
                                    {categories.map((category) => (
                                        <option key={category.value} value={category.value}>{category.label}</option>
                                    ))}
                                </Select>
                                <div className="flex gap-2">
                                    <Input type="date" name="date_from" defaultValue={filters.date_from} aria-label="Du" />
                                    <Input type="date" name="date_to" defaultValue={filters.date_to} aria-label="Au" />
                                </div>
                                <Button type="submit">Filtrer</Button>
                            </form>

                            {sales.data.length === 0 ? (
                                <EmptyState
                                    icon={ShoppingCart}
                                    title="Aucune vente enregistrée"
                                    description="Saisissez la première vente de l'établissement."
                                    action={<Button onClick={() => setCreating(true)}><Plus className="h-4 w-4" />Saisir une vente</Button>}
                                />
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead className="text-left text-xs font-semibold uppercase text-slate-500">
                                            <tr className="border-b border-slate-100">
                                                <th className="py-3 pr-4">Date</th>
                                                <th className="py-3 pr-4">Article</th>
                                                <th className="py-3 pr-4">Catégorie</th>
                                                <th className="py-3 pr-4">Acheteur</th>
                                                <th className="py-3 pr-4 text-right">Qté</th>
                                                <th className="py-3 pr-4 text-right">PU</th>
                                                <th className="py-3 pr-4 text-right">Total</th>
                                                {can_manage && <th className="py-3 text-right">Actions</th>}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {sales.data.map((sale) => (
                                                <tr key={sale.id} className="align-top">
                                                    <td className="py-3 pr-4 whitespace-nowrap text-slate-600">{sale.sold_at}</td>
                                                    <td className="py-3 pr-4">
                                                        <p className="font-semibold text-slate-800">{sale.item}</p>
                                                        <p className="mt-0.5 text-[11px] text-slate-400">par {sale.creator_name ?? '—'}</p>
                                                    </td>
                                                    <td className="py-3 pr-4 whitespace-nowrap">
                                                        <span className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{sale.category_label}</span>
                                                    </td>
                                                    <td className="py-3 pr-4 text-slate-600">{sale.buyer || '—'}</td>
                                                    <td className="py-3 pr-4 text-right text-slate-600">{sale.quantity}</td>
                                                    <td className="py-3 pr-4 text-right text-slate-600">{money(sale.unit_price)}</td>
                                                    <td className="py-3 pr-4 whitespace-nowrap text-right font-bold text-slate-800">{money(sale.total)}</td>
                                                    {can_manage && (
                                                        <td className="py-3 text-right">
                                                            <button
                                                                type="button"
                                                                onClick={() => setTarget(sale)}
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

                            <div className="mt-4 border-t border-slate-100 pt-4"><Pagination meta={sales} /></div>
                        </Card.Body>
                    ) : (
                        <Card.Body className="p-4 sm:p-6">
                            <MoneyEvolutionChart
                                evolution={evolution}
                                color="#059669"
                                legend="Ventes encaissées"
                                emptyTitle="Aucune vente sur ces douze mois"
                            />
                        </Card.Body>
                    )}
                </Card>
            </div>

            <Modal
                show={creating}
                onClose={() => setCreating(false)}
                title="Saisir une vente"
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setCreating(false)}>Annuler</Button>
                        <Button onClick={submitSale} loading={form.processing}><Plus className="h-4 w-4" />Enregistrer</Button>
                    </>
                }
            >
                <form onSubmit={submitSale} className="space-y-4">
                    <label className="block text-sm font-semibold text-slate-700">
                        Catégorie
                        <Select className="mt-1.5" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} error={form.errors.category}>
                            {categories.map((category) => (
                                <option key={category.value} value={category.value}>{category.label}</option>
                            ))}
                        </Select>
                    </label>
                    <label className="block text-sm font-semibold text-slate-700">
                        Article
                        <Input className="mt-1.5" value={form.data.item} onChange={(e) => form.setData('item', e.target.value)} error={form.errors.item} placeholder="Uniforme scolaire, manuel de mathématiques…" />
                    </label>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <label className="block text-sm font-semibold text-slate-700">
                            Quantité
                            <Input className="mt-1.5" type="number" min="1" value={form.data.quantity} onChange={(e) => form.setData('quantity', e.target.value)} error={form.errors.quantity} />
                        </label>
                        <label className="block text-sm font-semibold text-slate-700">
                            Prix unitaire (F)
                            <Input className="mt-1.5" type="number" min="1" value={form.data.unit_price} onChange={(e) => form.setData('unit_price', e.target.value)} error={form.errors.unit_price} placeholder="15000" />
                        </label>
                        <label className="block text-sm font-semibold text-slate-700">
                            Date
                            <Input className="mt-1.5" type="date" max={new Date().toISOString().slice(0, 10)} value={form.data.sold_at} onChange={(e) => form.setData('sold_at', e.target.value)} error={form.errors.sold_at} />
                        </label>
                    </div>
                    <label className="block text-sm font-semibold text-slate-700">
                        Acheteur
                        <Input className="mt-1.5" value={form.data.buyer} onChange={(e) => form.setData('buyer', e.target.value)} error={form.errors.buyer} placeholder="Nom de l'acheteur (facultatif)…" />
                    </label>
                    <p className="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-sm font-medium text-emerald-700">
                        Total : <span className="font-bold">{money(total)}</span>
                    </p>
                </form>
            </Modal>

            <ConfirmDialog
                show={Boolean(target)}
                onClose={() => setTarget(null)}
                onConfirm={remove}
                title="Supprimer la vente"
                description={`Supprimer la vente « ${target?.item} » de ${money(target?.total)} ? Cette action est irréversible.`}
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}