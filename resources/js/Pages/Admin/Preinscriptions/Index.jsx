import { useMemo } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    FileText,
    GraduationCap,
    Inbox,
    Mail,
    Phone,
    UserRound,
    XCircle,
    X,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import EmptyState from '@/Components/UI/EmptyState';

export default function PreinscriptionsIndex({
    preinscriptions = [],
    classes = [],
    scope_note = null,
    routePrefix = 'admin',
}) {
    const pending = useMemo(
        () => preinscriptions.filter((item) => item.status.value === 'pending'),
        [preinscriptions],
    );

    const processed = useMemo(
        () => preinscriptions.filter((item) => item.status.value !== 'pending'),
        [preinscriptions],
    );

    return (
        <AuthenticatedLayout title="Préinscriptions">
            <Head title="Préinscriptions" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-primary">Inscriptions</p>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                            Demandes de préinscription
                        </h1>
                        <p className="mt-1 max-w-3xl text-sm text-slate-500">
                            Validez une demande pour créer automatiquement l’élève et l’affecter à une classe, ou
                            refusez-la avec un motif.
                        </p>
                        {scope_note && <p className="mt-1 text-xs text-slate-400">{scope_note}</p>}
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p className="text-2xl font-bold text-amber-600">{pending.length}</p>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">En attente</p>
                    </div>
                </div>

                <Card>
                    <Card.Header
                        title={
                            <span className="flex items-center gap-2">
                                <Inbox className="h-4 w-4 text-primary" />
                                À traiter
                            </span>
                        }
                        description="Demandes reçues depuis le mini-site en attente de décision."
                    />
                    <Card.Body className="space-y-4">
                        {pending.length === 0 ? (
                            <EmptyState
                                title="Aucune demande en attente"
                                description="Toutes les demandes reçues ont été traitées."
                            />
                        ) : (
                            pending.map((item) => (
                                <PreinscriptionCard
                                    key={item.id}
                                    item={item}
                                    classes={classes}
                                    routePrefix={routePrefix}
                                />
                            ))
                        )}
                    </Card.Body>
                </Card>

                {processed.length > 0 && (
                    <Card>
                        <Card.Header title="Historique" description="Demandes déjà traitées." />
                        <Card.Body className="space-y-4">
                            {processed.map((item) => (
                                <PreinscriptionCard
                                    key={item.id}
                                    item={item}
                                    classes={classes}
                                    routePrefix={routePrefix}
                                />
                            ))}
                        </Card.Body>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function PreinscriptionCard({ item, classes, routePrefix }) {
    const isPending = item.status.value === 'pending';

    const approveForm = useForm({ class_id: '' });
    const rejectForm = useForm({ reason: '' });

    function approve() {
        approveForm.post(route(`${routePrefix}.preinscriptions.approve`, item.id), { preserveScroll: true });
    }

    function reject() {
        rejectForm.post(route(`${routePrefix}.preinscriptions.reject`, item.id), { preserveScroll: true });
    }

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <span className="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                        <GraduationCap className="h-5 w-5" />
                    </span>
                    <div>
                        <p className="font-semibold text-slate-900">{item.full_name}</p>
                        <p className="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                            {item.requested_level && <span>Niveau : {item.requested_level}</span>}
                            {item.birth_date && <span>Né(e) le {item.birth_date}</span>}
                            {item.gender && <span>{item.gender === 'F' ? 'Féminin' : 'Masculin'}</span>}
                            <span>Reçue le {item.created_at}</span>
                        </p>
                    </div>
                </div>
                <Badge tone={item.status.tone}>{item.status.label}</Badge>
            </div>

            <div className="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">
                <Detail icon={UserRound} label="Parent" value={item.parent_name} />
                <Detail icon={Phone} label="Téléphone" value={item.parent_phone} />
                <Detail icon={Mail} label="Email" value={item.parent_email ?? '—'} />
                <Detail icon={CalendarDays} label="Lien" value={item.parent_relationship ?? '—'} />
            </div>

            {item.notes && (
                <p className="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">{item.notes}</p>
            )}

            {item.documents.length > 0 && (
                <div className="mt-3 flex flex-wrap gap-2">
                    {item.documents.map((document) => (
                        <a
                            key={document.url}
                            href={document.url}
                            target="_blank"
                            rel="noopener"
                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                        >
                            <FileText className="h-3.5 w-3.5" />
                            Pièce jointe
                        </a>
                    ))}
                </div>
            )}

            {item.status.value === 'rejected' && item.rejection_reason && (
                <p className="mt-3 flex items-center gap-2 text-sm text-red-600">
                    <X className="h-4 w-4" /> Motif : {item.rejection_reason}
                </p>
            )}

            {item.status.value === 'approved' && (
                <p className="mt-3 flex items-center gap-2 text-sm text-emerald-700">
                    <CheckCircle2 className="h-4 w-4" /> Élève créé et enregistré.
                </p>
            )}

            {isPending && (
                <div className="mt-4 grid gap-3 border-t border-slate-100 pt-4 lg:grid-cols-2">
                    <div className="space-y-2">
                        <label className="text-sm font-medium text-slate-700">Affecter à une classe</label>
                        <div className="flex flex-wrap gap-2">
                            <select
                                value={approveForm.data.class_id}
                                onChange={(e) => approveForm.setData('class_id', e.target.value)}
                                className="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            >
                                <option value="">— Sans classe —</option>
                                {classes.map((classRoom) => (
                                    <option key={classRoom.id} value={classRoom.id}>
                                        {classRoom.name}
                                    </option>
                                ))}
                            </select>
                            <Button size="sm" loading={approveForm.processing} onClick={approve} className="shrink-0">
                                <CheckCircle2 className="h-4 w-4" />
                                Valider
                            </Button>
                        </div>
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium text-slate-700">Refuser (motif facultatif)</label>
                        <div className="flex flex-wrap gap-2">
                            <input
                                value={rejectForm.data.reason}
                                onChange={(e) => rejectForm.setData('reason', e.target.value)}
                                placeholder="Motif du refus"
                                className="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                            <Button
                                size="sm"
                                variant="danger"
                                loading={rejectForm.processing}
                                onClick={reject}
                                className="shrink-0"
                            >
                                <XCircle className="h-4 w-4" />
                                Refuser
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

function Detail({ icon: Icon, label, value }) {
    return (
        <div className="flex items-start gap-2">
            <Icon className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
            <div className="min-w-0">
                <p className="text-xs uppercase tracking-wide text-slate-400">{label}</p>
                <p className="truncate text-sm font-medium text-slate-700">{value}</p>
            </div>
        </div>
    );
}
