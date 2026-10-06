import { useEffect, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Pencil,
    Phone,
    RotateCcw,
    Search,
    ShieldCheck,
    Trash2,
    UserCheck,
    UserPlus,
    Users,
    UserX,
    X,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import EmptyState from '@/Components/UI/EmptyState';
import Field from '@/Components/UI/Field';
import FileUpload from '@/Components/UI/FileUpload';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Select from '@/Components/UI/Select';
import Table from '@/Components/UI/Table';

const emptyMember = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    role: 'teacher',
    is_active: true,
    password: '',
    photo: null,
    remove_photo: false,
};

export default function Index({ staff = [], roles = [], counts = {}, filters = {} }) {
    const [editing, setEditing] = useState(null);
    const [confirming, setConfirming] = useState(null);
    const [confirmingProcessing, setConfirmingProcessing] = useState(false);

    const [search, setSearch] = useState(filters.search ?? '');
    const [role, setRole] = useState(filters.role ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const createForm = useForm(emptyMember, { forceFormData: true });
    const editForm = useForm(emptyMember, { forceFormData: true });

    const hasFilters = Boolean(search || role || status);

    // Le filtrage est fait par le serveur : `staff.index` reçoit search/role/status
    // et renvoie la liste déjà restreinte, avec `filters` pour réhydrater le formulaire.
    useEffect(() => {
        const timer = setTimeout(() => {
            router.get(
                route('admin.staff.index'),
                {
                    search: search || undefined,
                    role: role || undefined,
                    status: status || undefined,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['staff', 'counts', 'filters'],
                }
            );
        }, 300);

        return () => clearTimeout(timer);
    }, [search, role, status]);

    function resetFilters() {
        setSearch('');
        setRole('');
        setStatus('');
    }

    function submitCreate(event) {
        event.preventDefault();

        createForm.post(route('admin.staff.store'), {
            onSuccess: () => createForm.reset(),
        });
    }

    function startEdit(member) {
        setEditing(member);

        editForm.clearErrors();
        editForm.setData({
            first_name: member.first_name,
            last_name: member.last_name === '—' ? '' : member.last_name,
            email: member.email,
            phone: member.phone ?? '',
            role: member.role,
            is_active: member.is_active,
            password: '',
            photo: null,
            remove_photo: false,
        });
    }

    function submitEdit(event) {
        event.preventDefault();

        editForm.put(route('admin.staff.update', editing.id), {
            onSuccess: () => setEditing(null),
        });
    }

    function toggleAccess() {
        router.post(
            route('admin.staff.toggle', confirming.id),
            {},
            {
                onStart: () => setConfirmingProcessing(true),
                onFinish: () => {
                    setConfirmingProcessing(false);
                    setConfirming(null);
                },
            }
        );
    }

    function destroy() {
        router.delete(route('admin.staff.destroy', confirming.id), {
            onStart: () => setConfirmingProcessing(true),
            onFinish: () => {
                setConfirmingProcessing(false);
                setConfirming(null);
            },
        });
    }

    const stats = useMemo(
        () => [
            {
                icon: Users,
                label: 'Effectif total',
                value: counts.total ?? staff.length,
                description: 'Comptes du personnel',
                className: 'from-blue-500 to-indigo-600',
            },
            {
                icon: UserCheck,
                label: 'Comptes actifs',
                value: counts.active ?? 0,
                description: 'Accès autorisés',
                className: 'from-emerald-500 to-teal-600',
            },
            {
                icon: UserX,
                label: 'Comptes inactifs',
                value: counts.inactive ?? 0,
                description: 'Accès suspendus',
                className: 'from-amber-500 to-orange-600',
            },
            {
                icon: ShieldCheck,
                label: 'Enseignants',
                value: counts.teachers ?? 0,
                description: 'Comptes avec fiche enseignant',
                className: 'from-violet-500 to-purple-600',
            },
        ],
        [counts, staff.length]
    );

    return (
        <AuthenticatedLayout title="Personnel">
            <Head title="Personnel" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-700 via-teal-700 to-cyan-800 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-emerald-100">
                                <Users className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Administration & personnel
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Personnel
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/90 sm:text-base">
                                Composez l'équipe de votre établissement :
                                chaque membre dispose de son propre compte,
                                de son rôle et de son accès.
                            </p>
                        </div>

                        <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-sm">
                            <UserPlus className="h-8 w-8" />
                        </div>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                {/* Statistiques */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map((stat) => (
                        <StatCard key={stat.label} {...stat} />
                    ))}
                </div>

                {/* Layout */}
                <div className="grid gap-6 xl:grid-cols-[380px_1fr]">
                    {/* Création */}
                    <Card className="h-fit">
                        <Card.Header
                            title="Ajouter un membre"
                            description="Créez un compte et attribuez son rôle dans l'établissement."
                        />

                        <Card.Body>
                            <form
                                onSubmit={submitCreate}
                                className="space-y-4"
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Prénom"
                                        required
                                        error={createForm.errors.first_name}
                                    >
                                        <Input
                                            value={createForm.data.first_name}
                                            onChange={(e) =>
                                                createForm.setData(
                                                    'first_name',
                                                    e.target.value
                                                )
                                            }
                                            placeholder="Aïcha"
                                            required
                                        />
                                    </Field>

                                    <Field
                                        label="Nom"
                                        required
                                        error={createForm.errors.last_name}
                                    >
                                        <Input
                                            value={createForm.data.last_name}
                                            onChange={(e) =>
                                                createForm.setData(
                                                    'last_name',
                                                    e.target.value
                                                )
                                            }
                                            placeholder="Sossou"
                                            required
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Email"
                                    required
                                    error={createForm.errors.email}
                                    hint="Sert d'identifiant de connexion."
                                >
                                    <Input
                                        type="email"
                                        value={createForm.data.email}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'email',
                                                e.target.value
                                            )
                                        }
                                        placeholder="aicha.sossou@ecole.bj"
                                        required
                                    />
                                </Field>

                                <Field
                                    label="Téléphone"
                                    error={createForm.errors.phone}
                                >
                                    <Input
                                        type="tel"
                                        value={createForm.data.phone}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'phone',
                                                e.target.value
                                            )
                                        }
                                        placeholder="+229 01 97 00 00 00"
                                    />
                                </Field>

                                <Field
                                    label="Rôle"
                                    required
                                    error={createForm.errors.role}
                                >
                                    <Select
                                        value={createForm.data.role}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'role',
                                                e.target.value
                                            )
                                        }
                                        required
                                    >
                                        {roles.map((item) => (
                                            <option
                                                key={item.value}
                                                value={item.value}
                                            >
                                                {item.label}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>

                                <Field
                                    label="Mot de passe"
                                    required
                                    error={createForm.errors.password}
                                    hint="8 caractères minimum."
                                >
                                    <Input
                                        type="password"
                                        value={createForm.data.password}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'password',
                                                e.target.value
                                            )
                                        }
                                        placeholder="••••••••"
                                        autoComplete="new-password"
                                        required
                                    />
                                </Field>

                                <FileUpload
                                    label="Photo"
                                    hint="JPG, PNG ou WebP, 2 Mo maximum."
                                    error={createForm.errors.photo}
                                    onChange={(file) =>
                                        createForm.setData('photo', file)
                                    }
                                    onRemove={() =>
                                        createForm.setData('photo', null)
                                    }
                                />

                                <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <input
                                        type="checkbox"
                                        checked={createForm.data.is_active}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'is_active',
                                                e.target.checked
                                            )
                                        }
                                        className="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    />

                                    <span>
                                        <span className="block text-sm font-semibold text-slate-800">
                                            Compte actif
                                        </span>

                                        <span className="block text-xs text-slate-500">
                                            Autoriser l'accès à la plateforme.
                                        </span>
                                    </span>
                                </label>

                                <p className="flex gap-2 rounded-xl border border-amber-100 bg-amber-50 p-3 text-xs leading-5 text-amber-700">
                                    <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                                    <span>
                                        L'ajout, la modification et la
                                        suppression exigent une
                                        vérification Authenticator.
                                    </span>
                                </p>

                                <Button
                                    className="w-full"
                                    type="submit"
                                    loading={createForm.processing}
                                >
                                    <UserPlus className="h-4 w-4" />
                                    Ajouter au personnel
                                </Button>
                            </form>
                        </Card.Body>
                    </Card>

                    {/* Liste */}
                    <Card>
                        <Card.Header
                            title="Équipe de l'établissement"
                            description="Rôles, accès et état de chaque compte."
                        />

                        <Card.Body>
                            {/* Filtres */}
                            <div className="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4 lg:grid-cols-[1fr_auto_auto]">
                                <div className="relative">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                    <Input
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(e.target.value)
                                        }
                                        placeholder="Rechercher par nom ou email..."
                                        className="pl-10"
                                    />
                                </div>

                                <Select
                                    value={role}
                                    onChange={(e) =>
                                        setRole(e.target.value)
                                    }
                                >
                                    <option value="">
                                        Tous les rôles
                                    </option>

                                    {roles.map((item) => (
                                        <option
                                            key={item.value}
                                            value={item.value}
                                        >
                                            {item.label}
                                        </option>
                                    ))}
                                </Select>

                                <Select
                                    value={status}
                                    onChange={(e) =>
                                        setStatus(e.target.value)
                                    }
                                >
                                    <option value="">
                                        Tous les statuts
                                    </option>

                                    <option value="active">
                                        Actifs
                                    </option>

                                    <option value="inactive">
                                        Inactifs
                                    </option>
                                </Select>
                            </div>

                            {hasFilters && (
                                <div className="mb-4 flex items-center justify-between">
                                    <p className="text-sm text-slate-500">
                                        <span className="font-semibold text-slate-700">
                                            {staff.length}
                                        </span>{' '}
                                        résultat
                                        {staff.length > 1 ? 's' : ''}
                                    </p>

                                    <button
                                        type="button"
                                        onClick={resetFilters}
                                        className="inline-flex items-center gap-1 text-sm font-medium text-emerald-700 hover:text-emerald-800"
                                    >
                                        <X className="h-4 w-4" />
                                        Réinitialiser
                                    </button>
                                </div>
                            )}

                            <Table
                                rows={staff}
                                emptyState={
                                    hasFilters ? (
                                        <EmptyState
                                            icon={Search}
                                            title="Aucun résultat"
                                            description="Aucun membre du personnel ne correspond aux critères sélectionnés."
                                            action={
                                                <Button
                                                    variant="secondary"
                                                    onClick={
                                                        resetFilters
                                                    }
                                                >
                                                    Réinitialiser
                                                </Button>
                                            }
                                        />
                                    ) : (
                                        <EmptyState
                                            icon={Users}
                                            title="Aucun membre du personnel"
                                            description="Utilisez le formulaire pour ajouter le premier membre de l'équipe."
                                        />
                                    )
                                }
                                columns={[
                                    {
                                        key: 'full_name',
                                        header: 'Membre',
                                        render: (member) => (
                                            <div className="flex items-center gap-3">
                                                <Avatar member={member} />

                                                <div className="min-w-0">
                                                    <div className="truncate font-semibold text-slate-800">
                                                        {member.full_name}
                                                        {member.is_self && (
                                                            <span className="ml-2 text-xs font-normal text-slate-400">
                                                                (vous)
                                                            </span>
                                                        )}
                                                    </div>

                                                    <div className="truncate text-xs text-slate-400">
                                                        {member.email}
                                                    </div>
                                                </div>
                                            </div>
                                        ),
                                    },
                                    {
                                        key: 'phone',
                                        header: 'Téléphone',
                                        render: (member) =>
                                            member.phone ? (
                                                <span className="inline-flex items-center gap-1.5 text-sm text-slate-600">
                                                    <Phone className="h-3.5 w-3.5 text-slate-400" />
                                                    {member.phone}
                                                </span>
                                            ) : (
                                                <span className="text-sm text-slate-300">
                                                    —
                                                </span>
                                            ),
                                    },
                                    {
                                        key: 'role_label',
                                        header: 'Rôle',
                                        render: (member) => (
                                            <Badge tone="blue">
                                                <ShieldCheck className="h-3.5 w-3.5" />
                                                {member.role_label}
                                            </Badge>
                                        ),
                                    },
                                    {
                                        key: 'two_factor_configured',
                                        header: '2FA',
                                        render: (member) =>
                                            member.two_factor_configured ? (
                                                <Badge tone="green">
                                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                                    Activé
                                                </Badge>
                                            ) : (
                                                <Badge tone="amber">
                                                    À configurer
                                                </Badge>
                                            ),
                                    },
                                    {
                                        key: 'is_active',
                                        header: 'Statut',
                                        render: (member) =>
                                            member.is_active ? (
                                                <Badge tone="green">
                                                    Actif
                                                </Badge>
                                            ) : (
                                                <Badge tone="slate">
                                                    Inactif
                                                </Badge>
                                            ),
                                    },
                                    {
                                        key: 'actions',
                                        header: '',
                                        render: (member) => (
                                            <RowActions
                                                member={member}
                                                onEdit={() =>
                                                    startEdit(member)
                                                }
                                                onToggle={() =>
                                                    setConfirming({
                                                        mode: 'toggle',
                                                        member,
                                                    })
                                                }
                                                onDelete={() =>
                                                    setConfirming({
                                                        mode: 'delete',
                                                        member,
                                                    })
                                                }
                                            />
                                        ),
                                    },
                                ]}
                            />
                        </Card.Body>
                    </Card>
                </div>
            </div>

            {/* Modification */}
            <Modal
                show={Boolean(editing)}
                onClose={() => setEditing(null)}
                title="Modifier le membre"
                size="lg"
                footer={
                    <>
                        <Button
                            variant="secondary"
                            onClick={() => setEditing(null)}
                        >
                            Annuler
                        </Button>

                        <Button
                            type="submit"
                            form="staff-edit-form"
                            loading={editForm.processing}
                        >
                            <CheckCircle2 className="h-4 w-4" />
                            Enregistrer
                        </Button>
                    </>
                }
            >
                {editing && (
                    <form
                        id="staff-edit-form"
                        onSubmit={submitEdit}
                        className="grid gap-4 sm:grid-cols-2"
                    >
                        <Field
                            label="Prénom"
                            required
                            error={editForm.errors.first_name}
                        >
                            <Input
                                value={editForm.data.first_name}
                                onChange={(e) =>
                                    editForm.setData(
                                        'first_name',
                                        e.target.value
                                    )
                                }
                                required
                            />
                        </Field>

                        <Field
                            label="Nom"
                            required
                            error={editForm.errors.last_name}
                        >
                            <Input
                                value={editForm.data.last_name}
                                onChange={(e) =>
                                    editForm.setData(
                                        'last_name',
                                        e.target.value
                                    )
                                }
                                required
                            />
                        </Field>

                        <Field
                            label="Email"
                            required
                            error={editForm.errors.email}
                        >
                            <Input
                                type="email"
                                value={editForm.data.email}
                                onChange={(e) =>
                                    editForm.setData(
                                        'email',
                                        e.target.value
                                    )
                                }
                                required
                            />
                        </Field>

                        <Field
                            label="Téléphone"
                            error={editForm.errors.phone}
                        >
                            <Input
                                type="tel"
                                value={editForm.data.phone}
                                onChange={(e) =>
                                    editForm.setData(
                                        'phone',
                                        e.target.value
                                    )
                                }
                            />
                        </Field>

                        <Field
                            label="Rôle"
                            required
                            error={editForm.errors.role}
                        >
                            <Select
                                value={editForm.data.role}
                                onChange={(e) =>
                                    editForm.setData(
                                        'role',
                                        e.target.value
                                    )
                                }
                                required
                            >
                                {roles.map((item) => (
                                    <option
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </option>
                                ))}
                            </Select>
                        </Field>

                        <Field
                            label="Nouveau mot de passe"
                            error={editForm.errors.password}
                            hint="Laisser vide conserve le mot de passe actuel."
                        >
                            <Input
                                type="password"
                                value={editForm.data.password}
                                onChange={(e) =>
                                    editForm.setData(
                                        'password',
                                        e.target.value
                                    )
                                }
                                placeholder="••••••••"
                                autoComplete="new-password"
                            />
                        </Field>

                        <div className="sm:col-span-2">
                            <FileUpload
                                label="Photo"
                                previewUrl={editing.photo_url}
                                error={editForm.errors.photo}
                                onChange={(file) =>
                                    editForm.setData('photo', file)
                                }
                                onRemove={() =>
                                    editForm.setData('remove_photo', true)
                                }
                            />

                            {editForm.data.remove_photo && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        editForm.setData(
                                            'remove_photo',
                                            false
                                        )
                                    }
                                    className="mt-2 inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-700"
                                >
                                    <RotateCcw className="h-3.5 w-3.5" />
                                    Annuler la suppression de la photo
                                </button>
                            )}
                        </div>

                        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:col-span-2">
                            <input
                                type="checkbox"
                                checked={editForm.data.is_active}
                                onChange={(e) =>
                                    editForm.setData(
                                        'is_active',
                                        e.target.checked
                                    )
                                }
                                disabled={editing.is_self}
                                className="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:opacity-50"
                            />

                            <span>
                                <span className="block text-sm font-semibold text-slate-800">
                                    Compte actif
                                </span>

                                <span className="block text-xs text-slate-500">
                                    {editing.is_self
                                        ? 'Vous ne pouvez pas désactiver votre propre compte.'
                                        : 'Autoriser l’accès à la plateforme.'}
                                </span>
                            </span>
                        </label>
                    </form>
                )}
            </Modal>

            {/* Confirmations */}
            <ConfirmDialog
                show={confirming?.mode === 'toggle'}
                onClose={() => setConfirming(null)}
                onConfirm={toggleAccess}
                loading={confirmingProcessing}
                title={
                    confirming?.member?.is_active
                        ? 'Suspendre l’accès'
                        : 'Rétablir l’accès'
                }
                confirmLabel={
                    confirming?.member?.is_active ? 'Suspendre' : 'Rétablir'
                }
                description={
                    confirming?.member?.is_active
                        ? `Ce membre ne pourra plus se connecter à ${confirming?.member?.role_label === 'Enseignant' ? 'son espace enseignant' : 'la plateforme'} tant que son compte est inactif.`
                        : 'Ce membre pourra de nouveau se connecter avec ses identifiants.'
                }
            />

            <ConfirmDialog
                show={confirming?.mode === 'delete'}
                onClose={() => setConfirming(null)}
                onConfirm={destroy}
                loading={confirmingProcessing}
                variant="danger"
                title="Supprimer le membre"
                confirmLabel="Supprimer"
                description="Un membre rattaché à des classes ou à des évaluations sera désactivé au lieu d'être supprimé, afin de conserver l'historique des notes."
            >
                {confirming?.member && (
                    <div className="mt-4 flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                        <Avatar member={confirming.member} />

                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-slate-800">
                                {confirming.member.full_name}
                            </p>

                            <p className="truncate text-xs text-slate-500">
                                {confirming.member.email}
                            </p>
                        </div>
                    </div>
                )}
            </ConfirmDialog>
        </AuthenticatedLayout>
    );
}

/* ==========================================================================
 | COMPOSANTS LOCAUX
 | ==========================================================================*/

function StatCard({ icon: Icon, label, value, description, className }) {
    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${className} p-5 text-white shadow-md`}
        >
            <div className="relative z-10">
                <div className="mb-4 flex items-center justify-between">
                    <div className="rounded-xl bg-white/15 p-2.5 backdrop-blur-sm">
                        <Icon className="h-5 w-5" />
                    </div>

                    <span className="text-3xl font-bold">{value}</span>
                </div>

                <p className="font-semibold">{label}</p>

                <p className="mt-1 text-xs text-white/75">{description}</p>
            </div>

            <div className="absolute -bottom-10 -right-10 h-28 w-28 rounded-full bg-white/10" />
        </div>
    );
}

function Avatar({ member }) {
    if (member.photo_url) {
        return (
            <img
                src={member.photo_url}
                alt=""
                className="h-10 w-10 shrink-0 rounded-xl object-cover"
            />
        );
    }

    return (
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-sm font-semibold text-white shadow-sm">
            {getInitials(member.full_name)}
        </span>
    );
}

function RowActions({ member, onEdit, onToggle, onDelete }) {
    return (
        <div className="flex items-center justify-end gap-1">
            <button
                type="button"
                onClick={onEdit}
                className="rounded-lg p-2 text-slate-500 transition hover:bg-emerald-50 hover:text-emerald-600"
                title="Modifier"
                aria-label={`Modifier ${member.full_name}`}
            >
                <Pencil className="h-4 w-4" />
            </button>

            <button
                type="button"
                onClick={onToggle}
                disabled={member.is_self}
                className="rounded-lg p-2 text-slate-500 transition hover:bg-amber-50 hover:text-amber-600 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                title={
                    member.is_self
                        ? 'Vous ne pouvez pas modifier votre propre accès'
                        : member.is_active
                          ? 'Suspendre l’accès'
                          : 'Rétablir l’accès'
                }
                aria-label={
                    member.is_active
                        ? `Suspendre l'accès de ${member.full_name}`
                        : `Rétablir l'accès de ${member.full_name}`
                }
            >
                {member.is_active ? (
                    <UserX className="h-4 w-4" />
                ) : (
                    <UserCheck className="h-4 w-4" />
                )}
            </button>

            <button
                type="button"
                onClick={onDelete}
                disabled={member.is_self}
                className="rounded-lg p-2 text-slate-500 transition hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                title={
                    member.is_self
                        ? 'Vous ne pouvez pas supprimer votre propre compte'
                        : 'Supprimer'
                }
                aria-label={`Supprimer ${member.full_name}`}
            >
                <Trash2 className="h-4 w-4" />
            </button>
        </div>
    );
}

function getInitials(name) {
    if (!name) {
        return '?';
    }

    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
