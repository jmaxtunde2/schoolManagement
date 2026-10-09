import { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    CreditCard,
    DollarSign,
    Key,
    Loader2,
    RotateCcw,
    XCircle,
    Eye,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import Input from '@/Components/UI/Input';
import StatCard from '@/Components/UI/StatCard';

const statusConfig = {
    active: { icon: CheckCircle2, color: 'bg-emerald-100 text-emerald-700', label: 'Active', iconColor: 'text-emerald-600' },
    expired: { icon: AlertCircle, color: 'bg-red-100 text-red-700', label: 'Expirée', iconColor: 'text-red-600' },
    pending: { icon: Loader2, color: 'bg-amber-100 text-amber-700', label: 'En attente', iconColor: 'text-amber-600' },
    suspended: { icon: XCircle, color: 'bg-slate-100 text-slate-700', label: 'Suspendue', iconColor: 'text-slate-400' },
};

export default function LicenseShow({ license, payments }) {
    const [renewing, setRenewing] = useState(false);
    const [months, setMonths] = useState(12);
    const [amount, setAmount] = useState(license.amount);
    const [confirming, setConfirming] = useState(null);
    const [confirmingProcessing, setConfirmingProcessing] = useState(false);

    const licenseStatus = statusConfig[license.status] || { icon: CreditCard, color: 'bg-slate-100 text-slate-700', label: license.status, iconColor: 'text-slate-400' };

    function handleRenew() {
        setConfirming({
            mode: 'renew',
            title: 'Renouveler la licence',
            description: `Renouveler la licence de « ${license.school.name} » pour ${months} mois (${Number(amount).toLocaleString('fr-FR')} FCFA) ?`,
            confirmLabel: `Renouveler pour ${months} mois`,
            variant: 'primary',
            onConfirm: () => {
                setConfirmingProcessing(true);
                const formData = new FormData();
                formData.append('months', months);
                formData.append('amount', amount);
                router.post(
                    route('platform.licenses.renew', license.id),
                    formData,
                    {
                        onFinish: () => {
                            setConfirmingProcessing(false);
                            setConfirming(null);
                            window.location.reload();
                        },
                    }
                );
            },
        });
    }

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title={`Licence : ${license.school.name}`} />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 text-white shadow-lg mb-6">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <Link
                                href={route('platform.licenses.index')}
                                className="mb-4 inline-flex items-center gap-2 text-white/80 hover:text-white transition-colors"
                            >
                                <ArrowLeft className="h-5 w-5" strokeWidth={2} />
                                Retour aux licences
                            </Link>

                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <Key className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Licence — {license.school.name}
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                {license.type_label} • {license.status_label}
                            </p>
                        </div>

                        <div className="flex items-center gap-3">
                            <Link href={route('platform.schools.show', license.school.id)}>
                                <Button variant="outline" className="gap-2 bg-white/10 text-white border-white/20 hover:bg-white/20">
                                    <ArrowRight className="h-4 w-4" strokeWidth={2} />
                                    Voir l'école
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-6">
                    {/* Détails de la licence */}
                    <Card>
                        <Card.Header title="Détails de la licence" icon={Key} />
                        <Card.Body>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm text-slate-500">École</dt>
                                    <dd className="mt-1">
                                        <Link href={route('platform.schools.show', license.school.id)} className="font-medium text-slate-900 hover:text-blue-600">
                                            {license.school.name}
                                        </Link>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Type</dt>
                                    <dd className="mt-1 font-medium text-slate-900">{license.type_label}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Statut</dt>
                                    <dd className="mt-1">
                                        <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${licenseStatus.color}`}>
                                            <licenseStatus.icon className={`h-3 w-3 ${licenseStatus.iconColor}`} strokeWidth={2} />
                                            {licenseStatus.label}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Début</dt>
                                    <dd className="mt-1 text-slate-900">{license.starts_at || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Fin</dt>
                                    <dd className="mt-1 text-slate-900">{license.ends_at || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Jours restants</dt>
                                    <dd className="mt-1 text-slate-900">
                                        {license.days_remaining !== null
                                            ? license.days_remaining > 0
                                                ? `${license.days_remaining} jour(s)`
                                                : license.is_expired
                                                    ? 'Expirée'
                                                    : 'Illimitée'
                                            : '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Montant</dt>
                                    <dd className="mt-1 text-slate-900">{Number(license.amount).toLocaleString('fr-FR')} {license.currency}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Réf. paiement</dt>
                                    <dd className="mt-1 font-mono text-sm text-slate-600">{license.payment_reference || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Renouvellement auto</dt>
                                    <dd className="mt-1 text-slate-900">{license.auto_renew ? 'Oui' : 'Non'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Créée le</dt>
                                    <dd className="mt-1 text-slate-900">{license.created_at}</dd>
                                </div>
                            </dl>

                            {(license.status === 'active' || license.status === 'expired') && (
                                <div className="mt-6 p-4 rounded-lg bg-slate-50 border">
                                    <h4 className="font-medium text-slate-900 mb-4">Renouveler la licence</h4>
                                    <div className="grid gap-4 sm:grid-cols-2 mb-4">
                                        <div>
                                            <label className="block text-sm text-slate-500 mb-1">Durée (mois)</label>
                                            <Input
                                                type="number"
                                                min="1"
                                                max="60"
                                                value={months}
                                                onChange={(e) => setMonths(parseInt(e.target.value) || 12)}
                                                className="w-full"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm text-slate-500 mb-1">Montant (FCFA)</label>
                                            <Input
                                                type="number"
                                                min="0"
                                                value={amount}
                                                onChange={(e) => setAmount(parseInt(e.target.value) || license.amount)}
                                                className="w-full"
                                            />
                                        </div>
                                    </div>
                                    <Button
                                        onClick={handleRenew}
                                        disabled={renewing || confirmingProcessing}
                                        className="gap-2"
                                    >
                                        {renewing || confirmingProcessing ? <Loader2 className="h-4 w-4 animate-spin" strokeWidth={2} /> : <RotateCcw className="h-4 w-4" strokeWidth={2} />}
                                        {renewing || confirmingProcessing ? 'Renouvellement...' : `Renouveler pour ${months} mois`}
                                    </Button>
                                </div>
                            )}
                        </Card.Body>
                    </Card>

                    {/* Historique des paiements */}
                    <Card>
                        <Card.Header title="Historique des paiements" icon={DollarSign} />
                        <Card.Body className="p-0">
                            {payments.length === 0 ? (
                                <div className="py-8 text-center text-slate-500">Aucun paiement</div>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {payments.map((payment) => (
                                        <div key={payment.id} className="flex items-center justify-between p-4 hover:bg-slate-50">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                                    <DollarSign className="h-4 w-4" strokeWidth={2} />
                                                </div>
                                                <div>
                                                    <p className="font-medium text-slate-900">{Number(payment.amount).toLocaleString('fr-FR')} FCFA</p>
                                                    <p className="text-sm text-slate-500">{payment.reference}</p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                                                    payment.status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' :
                                                    payment.status === 'pending' ? 'bg-amber-100 text-amber-700' :
                                                    'bg-red-100 text-red-700'
                                                }`}>
                                                    {payment.status_label}
                                                </span>
                                                <p className="mt-1 text-xs text-slate-500">{payment.created_at}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </div>

                {/* Sidebar Résumé */}
                <div className="space-y-4">
                    <Card>
                        <Card.Header title="Résumé" icon={CreditCard} />
                        <Card.Body className="space-y-3">
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">École</p>
                                <p className="font-medium text-slate-900">{license.school.name}</p>
                            </div>
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">Statut actuel</p>
                                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${licenseStatus.color}`}>
                                    <licenseStatus.icon className={`h-3 w-3 ${licenseStatus.iconColor}`} strokeWidth={2} />
                                    {licenseStatus.label}
                                </span>
                            </div>
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">Expire</p>
                                <p className="font-medium text-slate-900">{license.ends_at || '—'}</p>
                            </div>
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">Montant</p>
                                <p className="font-medium text-slate-900">{Number(license.amount).toLocaleString('fr-FR')} {license.currency}</p>
                            </div>
                        </Card.Body>
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                show={confirming?.mode === 'renew'}
                onClose={() => setConfirming(null)}
                onConfirm={confirming?.onConfirm}
                title={confirming?.title}
                description={confirming?.description}
                confirmLabel={confirming?.confirmLabel}
                variant={confirming?.variant}
                loading={confirmingProcessing}
            />
        </AuthenticatedLayout>
    );
}