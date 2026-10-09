import { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    Building2,
    CalendarDays,
    CalendarRange,
    CheckCircle2,
    CreditCard,
    DollarSign,
    Eye,
    FileText,
    GraduationCap,
    Key,
    Users,
    XCircle,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import StatCard from '@/Components/UI/StatCard';

const statusConfig = {
    active: { icon: CheckCircle2, color: 'bg-emerald-100 text-emerald-700', label: 'Active', iconColor: 'text-emerald-600' },
    expired: { icon: AlertCircle, color: 'bg-red-100 text-red-700', label: 'Expirée', iconColor: 'text-red-600' },
    pending: { icon: AlertCircle, color: 'bg-amber-100 text-amber-700', label: 'En attente', iconColor: 'text-amber-600' },
    suspended: { icon: XCircle, color: 'bg-slate-100 text-slate-700', label: 'Suspendue', iconColor: 'text-slate-400' },
};

export default function SchoolShow({ school, admins, license, latestPayments }) {
    const [confirming, setConfirming] = useState(null);
    const [confirmingProcessing, setConfirmingProcessing] = useState(false);

    function handleToggle() {
        setConfirming({
            mode: 'toggle',
            title: school.is_active ? 'Suspendre cette école' : 'Réactiver cette école',
            description: school.is_active
                ? 'Les utilisateurs ne pourront plus se connecter.'
                : "L'école redeviendra accessible.",
            confirmLabel: school.is_active ? 'Suspendre' : 'Réactiver',
            variant: school.is_active ? 'destructive' : 'primary',
            onConfirm: () => {
                setConfirmingProcessing(true);
                router.post(
                    route('platform.schools.toggle', school.id),
                    {},
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

    function handleDelete() {
        setConfirming({
            mode: 'delete',
            title: `Supprimer « ${school.name} » ?`,
            description: "Cette action est irréversible. Toutes les données de l'école seront perdues.",
            confirmLabel: 'Supprimer',
            variant: 'destructive',
            onConfirm: () => {
                setConfirmingProcessing(true);
                router.delete(
                    route('platform.schools.destroy', school.id),
                    {
                        onFinish: () => {
                            setConfirmingProcessing(false);
                            setConfirming(null);
                            window.location.href = route('platform.schools.index');
                        },
                    }
                );
            },
        });
    }

    const licenseStatus = license?.status ? statusConfig[license.status] || { icon: CreditCard, color: 'bg-slate-100 text-slate-700', label: license.status, iconColor: 'text-slate-400' } : null;

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title={`École : ${school.name}`} />

            {/* Hero */}
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white shadow-lg mb-6">
                <div className="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-white/10" />
                <div className="absolute -bottom-20 right-28 h-48 w-48 rounded-full bg-white/5" />

                <div className="relative p-6">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <Link
                                href={route('platform.schools.index')}
                                className="mb-4 inline-flex items-center gap-2 text-white/80 hover:text-white transition-colors"
                            >
                                <ArrowLeft className="h-5 w-5" strokeWidth={2} />
                                Retour aux écoles
                            </Link>

                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                                    <Building2 className="h-5 w-5" strokeWidth={2} />
                                </div>
                                <span className="text-sm font-medium text-white/80">
                                    Administration Coriyase
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                {school.name}
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
                                {school.slug} • Créée le {school.created_at}
                            </p>
                        </div>

                        <div className="flex items-center gap-3">
                            <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${school.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                                {school.is_active ? 'Actif' : 'Suspendu'}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Actions rapides */}
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-2">
                    <Button variant="outline" onClick={handleToggle} disabled={confirmingProcessing} className="gap-2">
                        {school.is_active ? <XCircle className="h-4 w-4" strokeWidth={2} /> : <CheckCircle2 className="h-4 w-4" strokeWidth={2} />}
                        {school.is_active ? 'Suspendre' : 'Réactiver'}
                    </Button>
                    <Button variant="outline" onClick={handleDelete} disabled={confirmingProcessing} className="gap-2 text-red-600 border-red-200 hover:bg-red-50">
                        <AlertCircle className="h-4 w-4" strokeWidth={2} />
                        Supprimer
                    </Button>
                </div>
                <div className="flex items-center gap-2">
                    <Link href={route('platform.licenses.index')}>
                        <Button variant="outline" className="gap-2">
                            <Key className="h-4 w-4" strokeWidth={2} />
                            Gérer les licences
                        </Button>
                    </Link>
                    <Link href={route('platform.demo-requests.index')}>
                        <Button variant="outline" className="gap-2">
                            <FileText className="h-4 w-4" strokeWidth={2} />
                            Demandes de démo
                        </Button>
                    </Link>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-6">
                    {/* Infos générales */}
                    <Card>
                        <Card.Header title="Informations générales" icon={Building2} />
                        <Card.Body>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm text-slate-500">Nom</dt>
                                    <dd className="mt-1 font-medium text-slate-900">{school.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Slug</dt>
                                    <dd className="mt-1 font-mono text-sm text-slate-600">{school.slug}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Créée le</dt>
                                    <dd className="mt-1 text-slate-900">{school.created_at}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Statut</dt>
                                    <dd className="mt-1">
                                        <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${school.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                                            {school.is_active ? 'Actif' : 'Suspendu'}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </Card.Body>
                    </Card>

                    {/* Statistiques */}
                    <Card>
                        <Card.Header title="Statistiques" icon={Users} />
                        <Card.Body>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                {[
                                    { label: 'Utilisateurs', value: school.counts.users, icon: Users, color: 'from-blue-500 to-indigo-600', desc: 'Comptes enregistrés' },
                                    { label: 'Élèves', value: school.counts.students, icon: GraduationCap, color: 'from-violet-500 to-purple-600', desc: 'Élèves inscrits' },
                                    { label: 'Enseignants', value: school.counts.teachers, icon: ShieldCheck, color: 'from-emerald-500 to-teal-600', desc: 'Professeurs' },
                                    { label: 'Classes', value: school.counts.classes, icon: Building2, color: 'from-amber-500 to-orange-600', desc: 'Classes créées' },
                                    { label: 'Années scolaires', value: school.counts.academic_years, icon: CreditCard, color: 'from-purple-500 to-pink-600', desc: 'Périodes académiques' },
                                ].map((stat) => (
                                    <StatCard
                                        key={stat.label}
                                        icon={stat.icon}
                                        label={stat.label}
                                        value={stat.value}
                                        description={stat.desc}
                                        className={stat.color}
                                        iconBg="bg-white/15"
                                    />
                                ))}
                            </div>
                        </Card.Body>
                    </Card>

                    {/* Licence actuelle */}
                    {license && (
                        <Card>
                            <Card.Header title="Licence actuelle" icon={Key} />
                            <Card.Body>
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt className="text-sm text-slate-500">Type</dt>
                                        <dd className="mt-1 font-medium text-slate-900">{license.type_label}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-slate-500">Statut</dt>
                                        <dd className="mt-1">
                                            {licenseStatus && (
                                                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${licenseStatus.color}`}>
                                                    <licenseStatus.icon className={`h-3 w-3 ${licenseStatus.iconColor}`} strokeWidth={2} />
                                                    {licenseStatus.label}
                                                </span>
                                            )}
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
                                        <dt className="text-sm text-slate-500">Montant</dt>
                                        <dd className="mt-1 text-slate-900">{Number(license.amount).toLocaleString('fr-FR')} {license.currency}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-slate-500">Réf. paiement</dt>
                                        <dd className="mt-1 font-mono text-sm text-slate-600">{license.payment_reference || '—'}</dd>
                                    </div>
                                    {license.days_remaining !== null && (
                                        <div className="sm:col-span-2">
                                            <dt className="text-sm text-slate-500">Jours restants</dt>
                                            <dd className="mt-1 text-slate-900">
                                                {license.days_remaining > 0
                                                    ? `${license.days_remaining} jour(s)`
                                                    : license.is_expired
                                                        ? 'Expirée'
                                                        : 'Illimitée'}
                                            </dd>
                                        </div>
                                    )}
                                </dl>
                                <Link href={route('platform.licenses.show', license.id)} className="mt-4 inline-block">
                                    <Button variant="outline" className="gap-2">
                                        <Eye className="h-4 w-4" strokeWidth={2} />
                                        Voir détails et paiements
                                    </Button>
                                </Link>
                            </Card.Body>
                        </Card>
                    )}

                    {/* Administrateurs */}
                    <Card>
                        <Card.Header title="Administrateurs de l'école" icon={Users} />
                        <Card.Body className="p-0">
                            {admins.length === 0 ? (
                                <div className="py-8 text-center text-slate-500">Aucun administrateur</div>
                            ) : (
                                <div className="divide-y divide-slate-100">
                                    {admins.map((admin) => (
                                        <div key={admin.id} className="flex items-center justify-between p-4 hover:bg-slate-50">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                                    <Users className="h-4 w-4" strokeWidth={2} />
                                                </div>
                                                <div>
                                                    <p className="font-medium text-slate-900">{admin.name}</p>
                                                    <p className="text-sm text-slate-500">{admin.email}</p>
                                                </div>
                                            </div>
                                            <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${admin.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                                                {admin.is_active ? 'Actif' : 'Inactif'}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card.Body>
                    </Card>

                    {/* Derniers paiements */}
                    {latestPayments.length > 0 && (
                        <Card>
                            <Card.Header title="Derniers paiements" icon={DollarSign} />
                            <Card.Body className="p-0">
                                <div className="divide-y divide-slate-100">
                                    {latestPayments.map((payment) => (
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
                            </Card.Body>
                        </Card>
                    )}
                </div>

                {/* Sidebar */}
                <div className="space-y-4">
                    <Card>
                        <Card.Header title="Résumé" icon={CreditCard} />
                        <Card.Body className="space-y-3">
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">École</p>
                                <p className="font-medium text-slate-900">{school.name}</p>
                            </div>
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">Statut</p>
                                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${school.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>
                                    {school.is_active ? 'Actif' : 'Suspendu'}
                                </span>
                            </div>
                            {license && (
                                <div className="p-3 rounded-lg bg-slate-50">
                                    <p className="text-xs text-slate-500">Licence</p>
                                    {licenseStatus && (
                                        <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${licenseStatus.color}`}>
                                            <licenseStatus.icon className={`h-3 w-3 ${licenseStatus.iconColor}`} strokeWidth={2} />
                                            {licenseStatus.label}
                                        </span>
                                    )}
                                </div>
                            )}
                            {license && license.ends_at && (
                                <div className="p-3 rounded-lg bg-slate-50">
                                    <p className="text-xs text-slate-500">Expire le</p>
                                    <p className="font-medium text-slate-900">{license.ends_at}</p>
                                </div>
                            )}
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header title="Gestion pédagogique" icon={CalendarDays} />
                        <Card.Body className="space-y-3">
                            <Link href={route('platform.schools.academic-years.index', school.id)} className="block">
                                <Button variant="outline" className="w-full justify-start gap-2">
                                    <CalendarDays className="h-4 w-4" strokeWidth={2} />
                                    Années scolaires
                                </Button>
                            </Link>
                            <Link href={route('platform.schools.academic-periods.index', school.id)} className="block">
                                <Button variant="outline" className="w-full justify-start gap-2">
                                    <CalendarRange className="h-4 w-4" strokeWidth={2} />
                                    Périodes scolaires
                                </Button>
                            </Link>
                        </Card.Body>
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                show={confirming?.mode === 'toggle'}
                onClose={() => setConfirming(null)}
                onConfirm={confirming?.onConfirm}
                title={confirming?.title}
                description={confirming?.description}
                confirmLabel={confirming?.confirmLabel}
                variant={confirming?.variant}
                loading={confirmingProcessing}
            />
            <ConfirmDialog
                show={confirming?.mode === 'delete'}
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