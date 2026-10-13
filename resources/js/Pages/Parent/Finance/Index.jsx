import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Download,
    FileText,
    HandCoins,
    Receipt,
    RotateCcw,
    Search,
    Wallet,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Select from '@/Components/UI/Select';

const statusTones = { paid: 'green', partial: 'amber', pending: 'red' };
const statusLabels = { paid: 'Soldé', partial: 'Partiel', pending: 'En attente' };

function money(value) {
    return `${Number(value ?? 0).toLocaleString('fr-FR')} FCFA`;
}

export default function Finance({ children = [], academicYears = [], methods = [], filters = {}, totals = {}, payments = [] }) {
    const [form, setForm] = useState({
        student_id: filters.student_id ?? '',
        academic_year_id: filters.academic_year_id ?? '',
        status: filters.status ?? '',
        method: filters.method ?? '',
    });

    const query = Object.fromEntries(Object.entries(form).filter(([, value]) => value !== '' && value !== null));

    function apply(next) {
        setForm(next);
        const params = Object.fromEntries(Object.entries(next).filter(([, value]) => value !== '' && value !== null));
        router.get(route('parent.finance.index'), params, { preserveState: true, replace: true });
    }

    function reset() {
        setForm({ student_id: '', academic_year_id: '', status: '', method: '' });
        router.get(route('parent.finance.index'), {}, { preserveState: true, replace: true });
    }

    const statementUrl = `${route('parent.finance.statement')}${new URLSearchParams(query).toString() ? `?${new URLSearchParams(query)}` : ''}`;

    return (
        <AuthenticatedLayout title="Ma scolarité">
            <Head title="Ma scolarité" />

            <div className="mx-auto max-w-6xl space-y-6">
                <Link
                    href={route('parent.dashboard')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-teal-700"
                >
                    <ArrowLeft className="h-4 w-4" /> Retour à mon espace
                </Link>

                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-teal-700">Espace parent</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950">Scolarité & paiements</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Historique des versements de frais de scolarité de vos enfants.
                        </p>
                    </div>

                    <a href={statementUrl}>
                        <Button>
                            <Download className="h-4 w-4" /> Télécharger le relevé (PDF)
                        </Button>
                    </a>
                </div>

                {/* Totals */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <TotalTile icon={HandCoins} label="Total dû" value={totals.due} tone="from-sky-500 to-indigo-600" />
                    <TotalTile icon={Wallet} label="Total versé" value={totals.paid} tone="from-emerald-500 to-teal-600" />
                    <TotalTile
                        icon={Receipt}
                        label="Reste à payer"
                        value={totals.remaining}
                        tone={totals.remaining > 0 ? 'from-amber-500 to-orange-600' : 'from-emerald-500 to-teal-600'}
                    />
                </div>

                {/* Filters */}
                <Card>
                    <Card.Body>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                            <label className="block">
                                <span className="mb-1 block text-xs font-semibold uppercase text-slate-500">Enfant</span>
                                <Select value={form.student_id} onChange={(e) => apply({ ...form, student_id: e.target.value })}>
                                    <option value="">Tous les enfants</option>
                                    {children.map((child) => (
                                        <option key={child.id} value={child.id}>{child.name}</option>
                                    ))}
                                </Select>
                            </label>

                            <label className="block">
                                <span className="mb-1 block text-xs font-semibold uppercase text-slate-500">Année</span>
                                <Select value={form.academic_year_id} onChange={(e) => apply({ ...form, academic_year_id: e.target.value })}>
                                    <option value="">Toutes les années</option>
                                    {academicYears.map((year) => (
                                        <option key={year.id} value={year.id}>{year.name}</option>
                                    ))}
                                </Select>
                            </label>

                            <label className="block">
                                <span className="mb-1 block text-xs font-semibold uppercase text-slate-500">Statut</span>
                                <Select value={form.status} onChange={(e) => apply({ ...form, status: e.target.value })}>
                                    <option value="">Tous les statuts</option>
                                    <option value="paid">Soldé</option>
                                    <option value="partial">Partiel</option>
                                    <option value="pending">En attente</option>
                                </Select>
                            </label>

                            <label className="block">
                                <span className="mb-1 block text-xs font-semibold uppercase text-slate-500">Moyen</span>
                                <Select value={form.method} onChange={(e) => apply({ ...form, method: e.target.value })}>
                                    <option value="">Tous les moyens</option>
                                    {methods.map((method) => (
                                        <option key={method.value} value={method.value}>{method.label}</option>
                                    ))}
                                </Select>
                            </label>

                            <div className="flex items-end">
                                <Button variant="secondary" className="w-full" onClick={reset}>
                                    <RotateCcw className="h-4 w-4" /> Réinitialiser
                                </Button>
                            </div>
                        </div>
                    </Card.Body>
                </Card>

                {/* Payments */}
                <Card className="overflow-hidden">
                    <Card.Header
                        title="Historique des versements"
                        description={`${payments.length} versement(s)`}
                    />
                    <Card.Body className="p-0">
                        {payments.length === 0 ? (
                            <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <Search className="h-8 w-8" />
                                </div>
                                <h3 className="mt-5 text-lg font-bold text-slate-900">Aucun versement trouvé</h3>
                                <p className="mt-2 max-w-md text-sm text-slate-500">
                                    Aucun paiement ne correspond aux filtres sélectionnés.
                                </p>
                            </div>
                        ) : (
                            <>
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-slate-50/80">
                                            <tr className="border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                                <th className="px-6 py-4">Date</th>
                                                <th className="px-6 py-4">Élève</th>
                                                <th className="px-6 py-4">Année</th>
                                                <th className="px-6 py-4">Moyen</th>
                                                <th className="px-6 py-4">Référence</th>
                                                <th className="px-6 py-4">Statut</th>
                                                <th className="px-6 py-4 text-right">Montant</th>
                                                <th className="px-6 py-4 text-right">Reçu</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {payments.map((payment) => (
                                                <tr key={payment.id} className="transition hover:bg-slate-50/70">
                                                    <td className="whitespace-nowrap px-6 py-4 text-slate-700">{payment.paid_at}</td>
                                                    <td className="px-6 py-4 font-semibold text-slate-800">{payment.student}</td>
                                                    <td className="px-6 py-4 text-slate-600">{payment.academic_year || '—'}</td>
                                                    <td className="px-6 py-4 text-slate-600">{payment.method_label}</td>
                                                    <td className="px-6 py-4 font-mono text-xs text-slate-500">{payment.reference || '—'}</td>
                                                    <td className="px-6 py-4">
                                                        <Badge tone={statusTones[payment.status] || 'slate'}>
                                                            {payment.status_label || '—'}
                                                        </Badge>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-right font-bold text-slate-800">{money(payment.amount)}</td>
                                                    <td className="px-6 py-4 text-right">
                                                        <a
                                                            href={route('parent.finance.receipt', payment.id)}
                                                            className="inline-flex items-center gap-1.5 font-semibold text-teal-700 hover:text-teal-800"
                                                        >
                                                            <FileText className="h-4 w-4" /> PDF
                                                        </a>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Mobile */}
                                <div className="divide-y divide-slate-100 md:hidden">
                                    {payments.map((payment) => (
                                        <div key={payment.id} className="p-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <p className="font-bold text-slate-900">{payment.student}</p>
                                                    <p className="mt-0.5 text-xs text-slate-500">
                                                        {payment.paid_at} · {payment.method_label}
                                                    </p>
                                                    <p className="mt-1 font-mono text-xs text-slate-400">{payment.reference || '—'}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-bold text-slate-900">{money(payment.amount)}</p>
                                                    <Badge tone={statusTones[payment.status] || 'slate'}>
                                                        {payment.status_label || '—'}
                                                    </Badge>
                                                </div>
                                            </div>
                                            <a
                                                href={route('parent.finance.receipt', payment.id)}
                                                className="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 hover:text-teal-800"
                                            >
                                                <FileText className="h-4 w-4" /> Télécharger le reçu
                                            </a>
                                        </div>
                                    ))}
                                </div>
                            </>
                        )}
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function TotalTile({ icon: Icon, label, value, tone }) {
    return (
        <div className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${tone} p-5 text-white shadow-sm`}>
            <div className="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-white/10" />
            <div className="relative">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15">
                    <Icon className="h-5 w-5" />
                </div>
                <p className="mt-3 text-xl font-bold leading-tight">{money(value)}</p>
                <p className="mt-0.5 text-xs text-white/80">{label}</p>
            </div>
        </div>
    );
}
