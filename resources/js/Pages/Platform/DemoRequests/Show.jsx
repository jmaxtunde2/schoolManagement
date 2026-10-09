import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    Check,
    CheckCircle2,
    ClipboardList,
    Clock,
    History,
    KeyRound,
    Mail,
    MapPin,
    NotebookPen,
    Phone,
    User,
    Wallet,
    X,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Alert from '@/Components/UI/Alert';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';

const toneByStatus = {
    pending: 'amber',
    contacted: 'blue',
    scheduled: 'blue',
    demo_done: 'slate',
    approved: 'green',
    rejected: 'red',
    cancelled: 'slate',
};

const timeline = [
    { key: 'pending', label: 'Reçue' },
    { key: 'contacted', label: 'Contactée' },
    { key: 'scheduled', label: 'Planifiée' },
    { key: 'demo_done', label: 'Démonstration' },
    { key: 'approved', label: 'Approuvée' },
];

export default function DemoRequestShow({
    demoRequest,
    allowedTransitions,
    statusOptions,
    history,
    activation,
    activationFlash,
}) {
    const [transition, setTransition] = useState('');
    const [note, setNote] = useState('');

    const statusForm = useForm({
        status: '',
        internal_notes: '',
        preferred_demo_date: demoRequest.preferred_demo_date_raw ?? '',
        preferred_demo_time: demoRequest.preferred_demo_time ?? '',
    });

    const notesForm = useForm({
        internal_notes: demoRequest.internal_notes ?? '',
    });

    const activateForm = useForm({
        admin_name: '',
        admin_email: '',
        admin_password: '',
    });

    function submitStatus(event) {
        event.preventDefault();

        statusForm.setData('status', transition);
        statusForm.setData('internal_notes', note);

        statusForm.patch(
            route('platform.demo-requests.status', demoRequest.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTransition('');
                    setNote('');
                },
            }
        );
    }

    function submitNotes(event) {
        event.preventDefault();
        notesForm.patch(
            route('platform.demo-requests.notes', demoRequest.id),
            { preserveScroll: true }
        );
    }

    function requestPayment() {
        router.post(
            route('platform.demo-requests.payment.request', demoRequest.id),
            {},
            { preserveScroll: true }
        );
    }

    function confirmPayment() {
        router.post(
            route('platform.demo-requests.payment.confirm', demoRequest.id),
            {},
            { preserveScroll: true }
        );
    }

    function submitActivation(event) {
        event.preventDefault();
        activateForm.post(
            route('platform.demo-requests.activate', demoRequest.id),
            { preserveScroll: true }
        );
    }

    const currentIndex = timeline.findIndex(
        (step) => step.key === demoRequest.status
    );
    const terminal = Object.keys(allowedTransitions).length === 0;

    return (
        <AuthenticatedLayout title="Demande de démonstration">
            <Head title={`Demande — ${demoRequest.school_name}`} />

            <Link
                href={route('platform.demo-requests.index')}
                className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-900"
            >
                <ArrowLeft className="h-4 w-4" />
                Toutes les demandes
            </Link>

            <div className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-bold tracking-tight text-slate-950">
                            {demoRequest.school_name}
                        </h1>

                        <Badge tone={toneByStatus[demoRequest.status] ?? 'slate'}>
                            {demoRequest.status_label}
                        </Badge>
                    </div>

                    <p className="mt-1 text-sm text-slate-500">
                        Reçue le {demoRequest.created_at} ·{' '}
                        {demoRequest.handler
                            ? `traitée par ${demoRequest.handler.name}`
                            : 'non traitée'}
                    </p>
                </div>
            </div>

            {/* Workflow */}
            <Card className="mt-5">
                <Card.Body>
                    <ol className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        {timeline.map((step, index) => {
                            const done =
                                currentIndex >= 0 && index <= currentIndex;
                            const current =
                                step.key === demoRequest.status;

                            return (
                                <li
                                    key={step.key}
                                    className="flex items-center gap-3"
                                >
                                    <span
                                        className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                                            current
                                                ? 'bg-emerald-600 text-white'
                                                : done
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-slate-100 text-slate-400'
                                        }`}
                                    >
                                        {done && !current ? (
                                            <Check className="h-4 w-4" />
                                        ) : (
                                            index + 1
                                        )}
                                    </span>

                                    <span
                                        className={`text-sm font-medium ${
                                            done
                                                ? 'text-slate-900'
                                                : 'text-slate-400'
                                        }`}
                                    >
                                        {step.label}
                                    </span>

                                    {index < timeline.length - 1 && (
                                        <span className="hidden h-px flex-1 bg-slate-200 sm:block" />
                                    )}
                                </li>
                            );
                        })}
                    </ol>

                    {demoRequest.status === 'approved' && (
                        <Alert type="success" className="mt-4">
                            École approuvée. L’activation n’a pas encore eu lieu :
                            elle survient après confirmation du paiement initial
                            de {demoRequest.initial_amount.toLocaleString('fr-FR')}
                            &nbsp;FCFA.
                        </Alert>
                    )}
                </Card.Body>
            </Card>

            {/* Activation */}
            {activation.isApproved && (
                <Card className="mt-5">
                    <Card.Header
                        title="Activation de l’école"
                        description="Approbation → paiement initial (150 000 FCFA) → activation."
                    />

                    <Card.Body className="space-y-4">
                        <ol className="space-y-3">
                            <ActivationStep
                                index={1}
                                done={activation.isPaymentRequested}
                                title="Demander le paiement initial"
                                description={`Un paiement initial de ${demoRequest.initial_amount.toLocaleString('fr-FR')} FCFA est demandé à l’établissement (1ʳᵉ année).`}
                                action={
                                    !activation.isPaymentRequested && (
                                        <Button
                                            onClick={requestPayment}
                                            variant="secondary"
                                            disabled={
                                                !activation.isApproved
                                            }
                                        >
                                            <Wallet className="h-4 w-4" />
                                            Demander le paiement
                                        </Button>
                                    )
                                }
                            />

                            <ActivationStep
                                index={2}
                                done={activation.isPaymentConfirmed}
                                title="Confirmer la réception"
                                description="Le Super Admin confirme manuellement que les 150 000 FCFA ont bien été reçus."
                                action={
                                    activation.isPaymentRequested &&
                                    !activation.isPaymentConfirmed && (
                                        <Button
                                            onClick={confirmPayment}
                                            variant="secondary"
                                        >
                                            <CheckCircle2 className="h-4 w-4" />
                                            Confirmer la réception
                                        </Button>
                                    )
                                }
                            />

                            <ActivationStep
                                index={3}
                                done={activation.isActivated}
                                title="Activer l’école"
                                description="Crée l’école, l’année d’onboarding et le compte administrateur."
                                action={
                                    activation.canBeActivated && (
                                        <form
                                            onSubmit={submitActivation}
                                            className="grid gap-4 sm:grid-cols-2"
                                        >
                                            <Field
                                                label="Nom du compte administrateur"
                                                error={
                                                    activateForm.errors
                                                        .admin_name
                                                }
                                                htmlFor="admin_name"
                                            >
                                                <Input
                                                    id="admin_name"
                                                    value={
                                                        activateForm.data
                                                            .admin_name
                                                    }
                                                    onChange={(e) =>
                                                        activateForm.setData(
                                                            'admin_name',
                                                            e.target.value
                                                        )
                                                    }
                                                    placeholder="Ex. : M. Jean AHOYO"
                                                />
                                            </Field>

                                            <Field
                                                label="E-mail"
                                                error={
                                                    activateForm.errors
                                                        .admin_email
                                                }
                                                htmlFor="admin_email"
                                            >
                                                <Input
                                                    id="admin_email"
                                                    type="email"
                                                    value={
                                                        activateForm.data
                                                            .admin_email
                                                    }
                                                    onChange={(e) =>
                                                        activateForm.setData(
                                                            'admin_email',
                                                            e.target.value
                                                        )
                                                    }
                                                    placeholder="direction@ecole.test"
                                                />
                                            </Field>

                                            <Field
                                                label="Mot de passe (facultatif)"
                                                hint="Laissé vide : un mot de passe temporaire est généré."
                                                error={
                                                    activateForm.errors
                                                        .admin_password
                                                }
                                                htmlFor="admin_password"
                                            >
                                                <Input
                                                    id="admin_password"
                                                    type="password"
                                                    value={
                                                        activateForm.data
                                                            .admin_password
                                                    }
                                                    onChange={(e) =>
                                                        activateForm.setData(
                                                            'admin_password',
                                                            e.target.value
                                                        )
                                                    }
                                                />
                                            </Field>

                                            <div className="flex items-end">
                                                <Button
                                                    type="submit"
                                                    size="lg"
                                                    loading={
                                                        activateForm.processing
                                                    }
                                                >
                                                    <Check className="h-4 w-4" />
                                                    Activer l’école
                                                </Button>
                                            </div>
                                        </form>
                                    )
                                }
                            />
                        </ol>

                        {activation.isActivated && (
                            <Alert type="success">
                                École activée le{' '}
                                {activation.result?.activated_at} ·{' '}
                                {activation.result?.school_id
                                    ? `identifiant école #${activation.result.school_id}`
                                    : ''}
                            </Alert>
                        )}
                    </Card.Body>
                </Card>
            )}

            {/* Compte administrateur créé à l'instant */}
            {activationFlash && (
                <Card className="mt-5">
                    <Card.Header
                        title="Compte administrateur créé"
                        description="Transmettez ces identifiants à l’établissement — le mot de passe ne sera plus jamais affiché."
                        icon={KeyRound}
                    />

                    <Card.Body>
                        <dl className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-lg bg-slate-50 p-3">
                                <dt className="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    École
                                </dt>
                                <dd className="mt-0.5 text-sm font-medium text-slate-900">
                                    {activationFlash.school_name}
                                </dd>
                            </div>

                            <div className="rounded-lg bg-slate-50 p-3">
                                <dt className="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    E-mail
                                </dt>
                                <dd className="mt-0.5 text-sm font-medium text-slate-900">
                                    {activationFlash.admin_email}
                                </dd>
                            </div>

                            <div className="rounded-lg bg-amber-50 p-3">
                                <dt className="text-xs font-medium uppercase tracking-wide text-amber-600">
                                    Mot de passe temporaire
                                </dt>
                                <dd className="mt-0.5 font-mono text-sm font-semibold text-amber-900">
                                    {activationFlash.temporary_password}
                                </dd>
                            </div>
                        </dl>

                        <dl className="mt-3 grid gap-3 sm:grid-cols-2">
                            <div className="rounded-lg bg-emerald-50 p-3">
                                <dt className="text-xs font-medium uppercase tracking-wide text-emerald-600">
                                    Licence initiale
                                </dt>
                                <dd className="mt-0.5 text-sm font-medium text-emerald-900">
                                    {(
                                        activationFlash.license_amount ?? 0
                                    ).toLocaleString('fr-FR')}{' '}
                                    FCFA ·{' '}
                                    <span className="font-mono text-xs">
                                        {activationFlash.license_reference}
                                    </span>
                                </dd>
                            </div>

                            <div className="rounded-lg bg-slate-50 p-3">
                                <dt className="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Réf. paiement
                                </dt>
                                <dd className="mt-0.5 font-mono text-sm text-slate-900">
                                    {activationFlash.payment_reference}
                                </dd>
                            </div>
                        </dl>
                    </Card.Body>
                </Card>
            )}

            <div className="mt-5 grid gap-5 lg:grid-cols-[1.5fr_1fr]">
                <div className="space-y-5">
                    {/* Coordonnées */}
                    <Card>
                        <Card.Header title="Coordonnées de l’établissement" />
                        <Card.Body>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Info
                                    icon={MapPin}
                                    label="Adresse"
                                    value={demoRequest.address}
                                />
                                <Info
                                    icon={Phone}
                                    label="Téléphone"
                                    value={demoRequest.phone}
                                />
                                <Info
                                    icon={Mail}
                                    label="E-mail"
                                    value={demoRequest.email}
                                />
                                <Info
                                    icon={CalendarDays}
                                    label="Créneau souhaité"
                                    value={demoRequest.preferred_slot}
                                />
                            </dl>
                        </Card.Body>
                    </Card>

                    {/* Notes internes */}
                    <Card>
                        <Card.Header
                            title="Notes internes"
                            description="Visibles uniquement par l’équipe Coriyase."
                        />

                        <Card.Body>
                            <form onSubmit={submitNotes}>
                                <Field
                                    label="Notes"
                                    error={notesForm.errors.internal_notes}
                                    htmlFor="internal_notes"
                                >
                                    <Textarea
                                        id="internal_notes"
                                        rows={5}
                                        value={notesForm.data.internal_notes}
                                        onChange={(e) =>
                                            notesForm.setData(
                                                'internal_notes',
                                                e.target.value
                                            )
                                        }
                                        placeholder="Contexte, besoin exprimé, échanges…"
                                    />
                                </Field>

                                <div className="mt-3 flex justify-end">
                                    <Button
                                        type="submit"
                                        loading={notesForm.processing}
                                    >
                                        <NotebookPen className="h-4 w-4" />
                                        Enregistrer les notes
                                    </Button>
                                </div>
                            </form>
                        </Card.Body>
                    </Card>

                    {/* Historique */}
                    <Card>
                        <Card.Header
                            title="Historique"
                            description="Trace de chaque changement d’état."
                        />

                        <Card.Body>
                            {history.length === 0 ? (
                                <p className="text-sm text-slate-500">
                                    Aucune action enregistrée pour l’instant.
                                </p>
                            ) : (
                                <ul className="space-y-3">
                                    {history.map((entry) => (
                                        <li
                                            key={entry.id}
                                            className="flex items-start gap-3 rounded-lg border border-slate-100 bg-slate-50/60 p-3"
                                        >
                                            <History className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />

                                            <div className="min-w-0">
                                                <p className="text-sm text-slate-700">
                                                    <span className="font-medium text-slate-900">
                                                        {entry.user ??
                                                            'Système'}
                                                    </span>{' '}
                                                    a passé la demande à{' '}
                                                    <span className="font-medium">
                                                        {statusOptions[
                                                            entry.metadata?.to
                                                        ] ?? entry.metadata?.to}
                                                    </span>
                                                </p>

                                                {entry.metadata?.note && (
                                                    <p className="mt-1 text-xs text-slate-500">
                                                        {entry.metadata.note}
                                                    </p>
                                                )}

                                                <p className="mt-1 text-xs text-slate-400">
                                                    {entry.created_at}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card.Body>
                    </Card>
                </div>

                {/* Changement de statut */}
                <aside className="space-y-5 lg:sticky lg:top-6 lg:self-start">
                    <Card>
                        <Card.Header
                            title="Changer de statut"
                            description="Seules les transitions du workflow sont autorisées."
                        />

                        <Card.Body>
                            {terminal ? (
                                <Alert type="info">
                                    Statut final : aucune transition possible.
                                </Alert>
                            ) : (
                                <form
                                    onSubmit={submitStatus}
                                    className="space-y-4"
                                >
                                    <Field
                                        label="Nouveau statut"
                                        required
                                        error={statusForm.errors.status}
                                        htmlFor="status"
                                    >
                                        <Select
                                            id="status"
                                            value={transition}
                                            onChange={(e) =>
                                                setTransition(e.target.value)
                                            }
                                            error={statusForm.errors.status}
                                        >
                                            <option value="">
                                                Sélectionner…
                                            </option>
                                            {Object.entries(
                                                allowedTransitions
                                            ).map(([value, label]) => (
                                                <option
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </option>
                                            ))}
                                        </Select>
                                    </Field>

                                    {(transition === 'scheduled' ||
                                        transition === 'contacted') && (
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <Field
                                                label="Date confirmée"
                                                error={
                                                    statusForm.errors
                                                        .preferred_demo_date
                                                }
                                                htmlFor="preferred_demo_date"
                                            >
                                                <Input
                                                    id="preferred_demo_date"
                                                    type="date"
                                                    value={
                                                        statusForm.data
                                                            .preferred_demo_date
                                                    }
                                                    onChange={(e) =>
                                                        statusForm.setData(
                                                            'preferred_demo_date',
                                                            e.target.value
                                                        )
                                                    }
                                                />
                                            </Field>

                                            <Field
                                                label="Heure"
                                                error={
                                                    statusForm.errors
                                                        .preferred_demo_time
                                                }
                                                htmlFor="preferred_demo_time"
                                            >
                                                <Input
                                                    id="preferred_demo_time"
                                                    type="time"
                                                    value={
                                                        statusForm.data
                                                            .preferred_demo_time
                                                    }
                                                    onChange={(e) =>
                                                        statusForm.setData(
                                                            'preferred_demo_time',
                                                            e.target.value
                                                        )
                                                    }
                                                />
                                            </Field>
                                        </div>
                                    )}

                                    <Field
                                        label="Note (optionnelle)"
                                        htmlFor="transition_note"
                                    >
                                        <Textarea
                                            id="transition_note"
                                            rows={3}
                                            value={note}
                                            onChange={(e) =>
                                                setNote(e.target.value)
                                            }
                                            placeholder="Motif, observation…"
                                        />
                                    </Field>

                                    {statusForm.errors.preferred_demo_date && (
                                        <p className="text-xs text-red-600">
                                            {
                                                statusForm.errors
                                                    .preferred_demo_date
                                            }
                                        </p>
                                    )}

                                    <Button
                                        type="submit"
                                        size="lg"
                                        className="w-full"
                                        disabled={!transition}
                                        loading={statusForm.processing}
                                    >
                                        Valider
                                        <ArrowRight className="h-4 w-4" />
                                    </Button>
                                </form>
                            )}
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header title="Actions rapides" />
                        <Card.Body className="space-y-2">
                            <QuickAction
                                icon={Clock}
                                label="Planifier"
                                value="scheduled"
                                transition={transition}
                                setTransition={setTransition}
                                allowed={allowedTransitions}
                            />
                            <QuickAction
                                icon={CheckCircle2}
                                label="Démonstration effectuée"
                                value="demo_done"
                                transition={transition}
                                setTransition={setTransition}
                                allowed={allowedTransitions}
                            />
                            <QuickAction
                                icon={X}
                                label="Rejeter"
                                value="rejected"
                                transition={transition}
                                setTransition={setTransition}
                                allowed={allowedTransitions}
                            />
                        </Card.Body>
                    </Card>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}

function ActivationStep({ index, done, title, description, action }) {
    return (
        <li className="flex items-start gap-4 rounded-lg border border-slate-100 p-4">
            <span
                className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                    done
                        ? 'bg-emerald-600 text-white'
                        : 'bg-slate-100 text-slate-500'
                }`}
            >
                {done ? <Check className="h-4 w-4" /> : index}
            </span>

            <div className="min-w-0 flex-1">
                <p className="text-sm font-semibold text-slate-900">{title}</p>
                <p className="mt-0.5 text-sm text-slate-500">{description}</p>
                {action && <div className="mt-3">{action}</div>}
            </div>
        </li>
    );
}

function Info({ icon: Icon, label, value }) {
    return (
        <div className="flex items-start gap-3">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                <Icon className="h-4 w-4" />
            </span>

            <div className="min-w-0">
                <dt className="text-xs font-medium uppercase tracking-wide text-slate-400">
                    {label}
                </dt>
                <dd className="mt-0.5 break-words text-sm text-slate-900">
                    {value || '—'}
                </dd>
            </div>
        </div>
    );
}

function QuickAction({
    icon: Icon,
    label,
    value,
    transition,
    setTransition,
    allowed,
}) {
    const enabled = Boolean(allowed[value]);

    return (
        <button
            type="button"
            disabled={!enabled}
            onClick={() => setTransition(transition === value ? '' : value)}
            className={`flex w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left text-sm font-medium transition ${
                transition === value
                    ? 'border-emerald-500 bg-emerald-50 text-emerald-800'
                    : enabled
                        ? 'border-slate-200 text-slate-700 hover:bg-slate-50'
                        : 'cursor-not-allowed border-slate-100 text-slate-300'
            }`}
        >
            <Icon className="h-4 w-4 shrink-0" />
            {label}
        </button>
    );
}
