import { useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    UserPlus,
    Users,
    Search,
    ShieldCheck,
    ShieldAlert,
    UserCheck,
    UserX,
    Pencil,
    X,
    Mail,
    LockKeyhole,
    Settings2,
    CheckCircle2,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Button from '@/Components/UI/Button';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import EmptyState from '@/Components/UI/EmptyState';

export default function Index({ users, roles }) {
    const [editing, setEditing] = useState(null);
    const [search, setSearch] = useState('');
    const [roleFilter, setRoleFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');

    const form = useForm({
        name: '',
        email: '',
        password: '',
        role: 'secretary',
    });

    const edit = useForm({
        name: '',
        role: 'secretary',
        is_active: true,
    });

    const activeUsers = useMemo(
        () => users.filter((user) => user.is_active),
        [users]
    );

    const blockedUsers = useMemo(
        () => users.filter((user) => !user.is_active),
        [users]
    );

    const admins = useMemo(
        () =>
            users.filter(
                (user) =>
                    user.role === 'admin' ||
                    user.role === 'platform_admin'
            ),
        [users]
    );

    const filteredUsers = useMemo(() => {
        const term = search.trim().toLowerCase();

        return users.filter((user) => {
            if (
                roleFilter !== 'all' &&
                user.role !== roleFilter
            ) {
                return false;
            }

            if (
                statusFilter === 'active' &&
                !user.is_active
            ) {
                return false;
            }

            if (
                statusFilter === 'blocked' &&
                user.is_active
            ) {
                return false;
            }

            if (!term) {
                return true;
            }

            return [
                user.name,
                user.email,
                user.role_label,
                user.role,
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value)
                        .toLowerCase()
                        .includes(term)
                );
        });
    }, [
        users,
        search,
        roleFilter,
        statusFilter,
    ]);

    const availableRoles = roles.filter(
        (role) => role.value !== 'teacher'
    );

    function create(e) {
        e.preventDefault();

        form.post(route('admin.users.store'), {
            onSuccess: () => {
                form.reset();
                form.setData('role', 'secretary');
            },
        });
    }

    function start(user) {
        setEditing(user.id);

        edit.setData({
            name: user.name,
            role: user.role,
            is_active: user.is_active,
        });
    }

    function cancelEdit() {
        setEditing(null);
        edit.reset();
    }

    function save(e) {
        e.preventDefault();

        edit.put(
            route(
                'admin.users.update',
                editing
            ),
            {
                onSuccess: () => {
                    setEditing(null);
                    edit.reset();
                },
            }
        );
    }

    function resetFilters() {
        setSearch('');
        setRoleFilter('all');
        setStatusFilter('all');
    }

    return (
        <AuthenticatedLayout title="Utilisateurs">
            <Head title="Utilisateurs" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-violet-100">
                                <Users className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Administration & sécurité
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Utilisateurs
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-violet-100 sm:text-base">
                                Gérez les comptes, les rôles et les
                                accès des membres de votre
                                établissement.
                            </p>
                        </div>

                        <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-sm">
                            <ShieldCheck className="h-8 w-8" />
                        </div>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                {/* Statistiques */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={Users}
                        label="Total comptes"
                        value={users.length}
                        description="Utilisateurs de l'établissement"
                        className="from-blue-500 to-indigo-600"
                    />

                    <StatCard
                        icon={UserCheck}
                        label="Comptes actifs"
                        value={activeUsers.length}
                        description="Accès actuellement autorisés"
                        className="from-emerald-500 to-teal-600"
                    />

                    <StatCard
                        icon={UserX}
                        label="Comptes bloqués"
                        value={blockedUsers.length}
                        description="Accès actuellement désactivés"
                        className="from-rose-500 to-red-600"
                    />

                    <StatCard
                        icon={ShieldCheck}
                        label="Administrateurs"
                        value={admins.length}
                        description="Comptes à privilèges élevés"
                        className="from-violet-500 to-purple-600"
                    />
                </div>

                {/* Layout */}
                <div className="grid gap-6 xl:grid-cols-[360px_1fr]">
                    {/* Création */}
                    <Card className="h-fit">
                        <Card.Header
                            title="Ajouter un utilisateur"
                            description="Créez un nouveau compte pour un membre de l'établissement."
                        />

                        <Card.Body>
                            <form
                                onSubmit={create}
                                className="space-y-5"
                            >
                                <div className="rounded-xl border border-violet-100 bg-violet-50/60 p-4">
                                    <div className="flex items-center gap-3">
                                        <div className="rounded-xl bg-violet-100 p-2 text-violet-600">
                                            <UserPlus className="h-5 w-5" />
                                        </div>

                                        <div>
                                            <p className="font-semibold text-slate-800">
                                                Nouveau compte
                                            </p>

                                            <p className="text-xs text-slate-500">
                                                Les accès seront
                                                contrôlés selon le
                                                rôle attribué.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <Field
                                    label="Nom"
                                    required
                                    error={form.errors.name}
                                >
                                    <Input
                                        value={
                                            form.data.name
                                        }
                                        onChange={(e) =>
                                            form.setData(
                                                'name',
                                                e.target.value
                                            )
                                        }
                                        placeholder="Nom complet"
                                    />
                                </Field>

                                <Field
                                    label="Email Google"
                                    required
                                    error={form.errors.email}
                                    hint="Utilisé pour l'authentification du compte."
                                >
                                    <div className="relative">
                                        <Mail className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                        <Input
                                            type="email"
                                            value={
                                                form.data
                                                    .email
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    'email',
                                                    e.target
                                                        .value
                                                )
                                            }
                                            placeholder="utilisateur@gmail.com"
                                            className="pl-10"
                                        />
                                    </div>
                                </Field>

                                <Field
                                    label="Mot de passe de secours"
                                    error={
                                        form.errors.password
                                    }
                                    hint="Optionnel."
                                >
                                    <div className="relative">
                                        <LockKeyhole className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                        <Input
                                            type="password"
                                            value={
                                                form.data
                                                    .password
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    'password',
                                                    e.target
                                                        .value
                                                )
                                            }
                                            placeholder="Mot de passe"
                                            className="pl-10"
                                        />
                                    </div>
                                </Field>

                                <Field
                                    label="Rôle"
                                    required
                                    error={form.errors.role}
                                >
                                    <Select
                                        value={
                                            form.data.role
                                        }
                                        onChange={(e) =>
                                            form.setData(
                                                'role',
                                                e.target
                                                    .value
                                            )
                                        }
                                    >
                                        {availableRoles.map(
                                            (role) => (
                                                <option
                                                    key={
                                                        role.value
                                                    }
                                                    value={
                                                        role.value
                                                    }
                                                >
                                                    {
                                                        role.label
                                                    }
                                                </option>
                                            )
                                        )}
                                    </Select>
                                </Field>

                                <div className="rounded-xl border border-amber-100 bg-amber-50 p-3">
                                    <div className="flex gap-2">
                                        <ShieldAlert className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />

                                        <p className="text-xs leading-5 text-amber-700">
                                            Les opérations
                                            d'administration et
                                            de sécurité peuvent
                                            nécessiter une
                                            vérification
                                            supplémentaire.
                                        </p>
                                    </div>
                                </div>

                                <Button
                                    className="w-full"
                                    type="submit"
                                    loading={form.processing}
                                >
                                    <UserPlus className="h-4 w-4" />
                                    Créer le compte
                                </Button>
                            </form>
                        </Card.Body>
                    </Card>

                    {/* Liste */}
                    <Card>
                        <Card.Header
                            title="Comptes de l'établissement"
                            description="Gérez les rôles et l'état des comptes utilisateurs."
                        />

                        <Card.Body>
                            {/* Filtres */}
                            <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                                <div className="grid gap-3 lg:grid-cols-[1fr_auto_auto]">
                                    <div className="relative">
                                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                        <Input
                                            value={search}
                                            onChange={(e) =>
                                                setSearch(
                                                    e.target
                                                        .value
                                                )
                                            }
                                            placeholder="Rechercher par nom, email ou rôle..."
                                            className="pl-10"
                                        />
                                    </div>

                                    <Select
                                        value={
                                            roleFilter
                                        }
                                        onChange={(e) =>
                                            setRoleFilter(
                                                e.target.value
                                            )
                                        }
                                    >
                                        <option value="all">
                                            Tous les rôles
                                        </option>

                                        {roles.map(
                                            (role) => (
                                                <option
                                                    key={
                                                        role.value
                                                    }
                                                    value={
                                                        role.value
                                                    }
                                                >
                                                    {
                                                        role.label
                                                    }
                                                </option>
                                            )
                                        )}
                                    </Select>

                                    <Select
                                        value={
                                            statusFilter
                                        }
                                        onChange={(e) =>
                                            setStatusFilter(
                                                e.target.value
                                            )
                                        }
                                    >
                                        <option value="all">
                                            Tous les statuts
                                        </option>

                                        <option value="active">
                                            Actifs
                                        </option>

                                        <option value="blocked">
                                            Bloqués
                                        </option>
                                    </Select>
                                </div>

                                {(search ||
                                    roleFilter !== 'all' ||
                                    statusFilter !==
                                        'all') && (
                                    <div className="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                                        <p className="text-sm text-slate-500">
                                            <span className="font-semibold text-slate-700">
                                                {
                                                    filteredUsers.length
                                                }
                                            </span>{' '}
                                            résultat
                                            {filteredUsers.length >
                                            1
                                                ? 's'
                                                : ''}
                                        </p>

                                        <button
                                            type="button"
                                            onClick={
                                                resetFilters
                                            }
                                            className="inline-flex items-center gap-1 text-sm font-medium text-violet-600 hover:text-violet-700"
                                        >
                                            <X className="h-4 w-4" />
                                            Réinitialiser
                                        </button>
                                    </div>
                                )}
                            </div>

                            <Table
                                rows={filteredUsers}
                                emptyState={
                                    search ||
                                    roleFilter !==
                                        'all' ||
                                    statusFilter !==
                                        'all' ? (
                                        <EmptyState
                                            icon={Search}
                                            title="Aucun résultat"
                                            description="Aucun utilisateur ne correspond aux critères sélectionnés."
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
                                            title="Aucun utilisateur"
                                            description="Aucun compte utilisateur n'est encore enregistré."
                                        />
                                    )
                                }
                                columns={[
                                    {
                                        key: 'name',
                                        header: 'Utilisateur',
                                        render: (
                                            user
                                        ) => (
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 font-semibold text-white shadow-sm">
                                                    {getInitials(
                                                        user.name
                                                    )}
                                                </div>

                                                <div className="min-w-0">
                                                    <div className="truncate font-semibold text-slate-800">
                                                        {
                                                            user.name
                                                        }
                                                    </div>

                                                    <div className="truncate text-xs text-slate-400">
                                                        Compte
                                                        établissement
                                                    </div>
                                                </div>
                                            </div>
                                        ),
                                    },

                                    {
                                        key: 'email',
                                        header: 'Email',
                                        render: (
                                            user
                                        ) => (
                                            <div className="flex items-center gap-2 text-sm text-slate-600">
                                                <Mail className="h-4 w-4 shrink-0 text-slate-400" />

                                                <span className="max-w-[240px] truncate">
                                                    {
                                                        user.email
                                                    }
                                                </span>
                                            </div>
                                        ),
                                    },

                                    {
                                        key: 'role_label',
                                        header: 'Rôle',
                                        render: (
                                            user
                                        ) => (
                                            <span className="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">
                                                <ShieldCheck className="h-3.5 w-3.5" />
                                                {
                                                    user.role_label
                                                }
                                            </span>
                                        ),
                                    },

                                    {
                                        key: 'is_active',
                                        header: 'Statut',
                                        render: (
                                            user
                                        ) =>
                                            user.is_active ? (
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                                    Actif
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                                    <span className="h-1.5 w-1.5 rounded-full bg-slate-400" />
                                                    Bloqué
                                                </span>
                                            ),
                                    },

                                    {
                                        key: 'actions',
                                        header: '',
                                        render: (
                                            user
                                        ) => (
                                            <div className="flex justify-end">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        start(
                                                            user
                                                        )
                                                    }
                                                    className="rounded-lg p-2 text-slate-500 transition hover:bg-violet-50 hover:text-violet-600"
                                                    title="Modifier"
                                                    aria-label="Modifier"
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ),
                                    },
                                ]}
                            />

                            {/* Edition */}
                            {editing && (
                                <div className="mt-6 overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-fuchsia-50">
                                    <div className="flex items-center justify-between border-b border-violet-100 px-5 py-4">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-xl bg-violet-100 p-2 text-violet-600">
                                                <Settings2 className="h-5 w-5" />
                                            </div>

                                            <div>
                                                <h3 className="font-semibold text-slate-800">
                                                    Modifier le
                                                    compte
                                                </h3>

                                                <p className="text-xs text-slate-500">
                                                    {
                                                        edit
                                                            .data
                                                            .name
                                                    }
                                                </p>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={
                                                cancelEdit
                                            }
                                            className="rounded-lg p-2 text-slate-400 hover:bg-white hover:text-slate-600"
                                            title="Fermer"
                                        >
                                            <X className="h-5 w-5" />
                                        </button>
                                    </div>

                                    <form
                                        onSubmit={save}
                                        className="grid gap-4 p-5 sm:grid-cols-2"
                                    >
                                        <Field
                                            label="Nom"
                                            required
                                            error={
                                                edit.errors
                                                    .name
                                            }
                                        >
                                            <Input
                                                value={
                                                    edit
                                                        .data
                                                        .name
                                                }
                                                onChange={(
                                                    e
                                                ) =>
                                                    edit.setData(
                                                        'name',
                                                        e
                                                            .target
                                                            .value
                                                    )
                                                }
                                            />
                                        </Field>

                                        <Field
                                            label="Rôle"
                                            required
                                            error={
                                                edit.errors
                                                    .role
                                            }
                                        >
                                            <Select
                                                value={
                                                    edit
                                                        .data
                                                        .role
                                                }
                                                onChange={(
                                                    e
                                                ) =>
                                                    edit.setData(
                                                        'role',
                                                        e
                                                            .target
                                                            .value
                                                    )
                                                }
                                            >
                                                {roles.map(
                                                    (
                                                        role
                                                    ) => (
                                                        <option
                                                            key={
                                                                role.value
                                                            }
                                                            value={
                                                                role.value
                                                            }
                                                        >
                                                            {
                                                                role.label
                                                            }
                                                        </option>
                                                    )
                                                )}
                                            </Select>
                                        </Field>

                                        <div className="sm:col-span-2">
                                            <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-white bg-white/70 p-4">
                                                <input
                                                    type="checkbox"
                                                    checked={
                                                        edit
                                                            .data
                                                            .is_active
                                                    }
                                                    onChange={(
                                                        e
                                                    ) =>
                                                        edit.setData(
                                                            'is_active',
                                                            e
                                                                .target
                                                                .checked
                                                        )
                                                    }
                                                    className="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500"
                                                />

                                                <div>
                                                    <p className="text-sm font-semibold text-slate-800">
                                                        Compte
                                                        actif
                                                    </p>

                                                    <p className="text-xs text-slate-500">
                                                        Autoriser
                                                        l'utilisateur
                                                        à accéder
                                                        à la
                                                        plateforme.
                                                    </p>
                                                </div>
                                            </label>
                                        </div>

                                        <div className="flex flex-wrap gap-2 sm:col-span-2 sm:justify-end">
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={
                                                    cancelEdit
                                                }
                                            >
                                                Annuler
                                            </Button>

                                            <Button
                                                type="submit"
                                                loading={
                                                    edit.processing
                                                }
                                            >
                                                <CheckCircle2 className="h-4 w-4" />
                                                Enregistrer
                                            </Button>
                                        </div>
                                    </form>
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
    description,
    className,
}) {
    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${className} p-5 text-white shadow-md`}
        >
            <div className="relative z-10">
                <div className="mb-4 flex items-center justify-between">
                    <div className="rounded-xl bg-white/15 p-2.5 backdrop-blur-sm">
                        <Icon className="h-5 w-5" />
                    </div>

                    <span className="text-3xl font-bold">
                        {value}
                    </span>
                </div>

                <p className="font-semibold">
                    {label}
                </p>

                <p className="mt-1 text-xs text-white/75">
                    {description}
                </p>
            </div>

            <div className="absolute -bottom-10 -right-10 h-28 w-28 rounded-full bg-white/10" />
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