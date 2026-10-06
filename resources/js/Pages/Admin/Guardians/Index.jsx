import { useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    UserRound,
    Users,
    UserCheck,
    Phone,
    Mail,
    GraduationCap,
    Search,
    ShieldCheck,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Button from '@/Components/UI/Button';
import Modal from '@/Components/UI/Modal';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import EmptyState from '@/Components/UI/EmptyState';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Checkbox from '@/Components/UI/Checkbox';

const empty = {
    name: '',
    phone: '',
    phone_secondary: '',
    email: '',
    password: '',
    student_ids: [],
};

export default function GuardiansIndex({
    guardians,
    students,
}) {
    const isCenseur = usePage().props.auth.user.role === 'censeur';
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [studentSearch, setStudentSearch] = useState('');
    const [search, setSearch] = useState('');

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
    } = useForm(empty);

    const filteredStudents = useMemo(() => {
        const term = studentSearch
            .trim()
            .toLowerCase();

        if (!term) {
            return students;
        }

        return students.filter((student) =>
            `${student.first_name} ${student.last_name}`
                .toLowerCase()
                .includes(term)
        );
    }, [students, studentSearch]);

    const filteredGuardians = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (!term) {
            return guardians;
        }

        return guardians.filter((guardian) => {
            const studentsText = (guardian.students ?? [])
                .map((student) =>
                    `${student.first_name} ${student.last_name}`
                )
                .join(' ');

            return [
                guardian.name,
                guardian.phone,
                guardian.email,
                studentsText,
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value).toLowerCase().includes(term)
                );
        });
    }, [guardians, search]);

    const parentsWithStudents = guardians.filter(
        (guardian) =>
            guardian.students?.length > 0
    ).length;

    const parentsWithoutStudents =
        guardians.length - parentsWithStudents;

    const totalLinkedStudents = guardians.reduce(
        (total, guardian) =>
            total + (guardian.students?.length ?? 0),
        0
    );

    function openCreate() {
        reset();
        setStudentSearch('');
        setEditing(null);
        setShowForm(true);
    }

    function openEdit(row) {
        setData({
            name: row.name,
            phone: row.phone,
            phone_secondary: row.phone_secondary ?? '',
            email: row.email ?? '',
            password: '',
            student_ids: row.students.map(
                (student) => student.id
            ),
        });

        setStudentSearch('');
        setEditing(row);
        setShowForm(true);
    }

    function closeForm() {
        if (processing) {
            return;
        }

        setShowForm(false);
        setEditing(null);
        setStudentSearch('');
        reset();
    }

    function toggleStudent(id) {
        setData(
            'student_ids',
            data.student_ids.includes(id)
                ? data.student_ids.filter(
                      (studentId) =>
                          studentId !== id
                  )
                : [...data.student_ids, id]
        );
    }

    function submit(e) {
        e.preventDefault();

        const options = {
            onSuccess: () => {
                setShowForm(false);
                setEditing(null);
                setStudentSearch('');
                reset();
            },
        };

        if (editing) {
            put(
                route(
                    'admin.guardians.update',
                    editing.id
                ),
                options
            );
        } else {
            post(
                route('admin.guardians.store'),
                options
            );
        }
    }

    function confirmDelete() {
        if (!toDelete) {
            return;
        }

        setDeleting(true);

        router.delete(
            route(
                'admin.guardians.destroy',
                toDelete.id
            ),
            {
                preserveScroll: true,
                onFinish: () => {
                    setDeleting(false);
                    setToDelete(null);
                },
            }
        );
    }

    return (
        <AuthenticatedLayout title="Parents / tuteurs">
            <Head title="Parents / tuteurs" />

            {/* Header */}
            <div className="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-teal-600 to-emerald-600 text-white shadow-lg">
                <div className="relative p-5 sm:p-6">
                    <div className="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10" />
                    <div className="absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-white/5" />

                    <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                                    <Users className="h-5 w-5" />
                                </div>

                                <span className="text-sm font-medium text-white/80">
                                    Gestion familiale
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Parents / tuteurs
                            </h1>

                            <p className="mt-1 max-w-xl text-sm text-white/75">
                                Gérez les responsables des élèves
                                et leurs coordonnées de communication.
                            </p>
                        </div>

                        {!isCenseur && <Button
                            onClick={openCreate}
                            className="bg-white text-white shadow-md hover:bg-teal-50"
                        >
                            <Plus className="h-4 w-4" />
                            Nouveau parent
                        </Button>}
                    </div>
                </div>
            </div>

            {/* Statistiques */}
            <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    icon={Users}
                    label="Parents"
                    value={guardians.length}
                    className="from-cyan-500 to-blue-600"
                />

                <StatCard
                    icon={UserCheck}
                    label="Avec élèves"
                    value={parentsWithStudents}
                    className="from-emerald-500 to-teal-600"
                />

                <StatCard
                    icon={GraduationCap}
                    label="Élèves liés"
                    value={totalLinkedStudents}
                    className="from-violet-500 to-purple-600"
                />

                <StatCard
                    icon={UserRound}
                    label="Sans élève"
                    value={parentsWithoutStudents}
                    className="from-slate-500 to-slate-700"
                />
            </div>

            {/* Tableau */}
            <Card className="overflow-hidden">
                <Card.Header
                    title="Liste des parents / tuteurs"
                    description={`${guardians.length} responsable(s) enregistré(s)`}
                />

                <Card.Body className="p-0">
                    <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="relative w-full sm:max-w-md">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher un parent, téléphone, email ou élève..."
                                className="pl-10"
                            />
                        </div>

                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700"
                            >
                                Effacer la recherche
                            </button>
                        )}
                    </div>
                    <Table
                        rows={filteredGuardians}
                        columns={[
                            {
                                key: 'name',
                                header: 'Parent / tuteur',
                                render: (row) => !isCenseur && (
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-sm font-bold text-cyan-600">
                                            {row.name
                                                ?.charAt(0)
                                                ?.toUpperCase()}
                                        </div>

                                        <div className="min-w-0">
                                            <p className="truncate font-semibold text-slate-800">
                                                {row.name}
                                            </p>

                                            <p className="text-xs text-slate-400">
                                                Responsable
                                            </p>
                                        </div>
                                    </div>
                                ),
                            },

                            {
                                key: 'phone',
                                header: 'Téléphone',
                                render: (row) => (
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                            <Phone className="h-4 w-4" />
                                        </div>

                                        <div className="min-w-0">
                                            <span className="block text-sm font-medium text-slate-700">
                                                {row.phone}
                                            </span>

                                            {row.phone_secondary && (
                                                <span className="block text-xs text-slate-400">
                                                    {row.phone_secondary}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ),
                            },

                            {
                                key: 'email',
                                header: 'Email',
                                render: (row) =>
                                    row.email ? (
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                                <Mail className="h-4 w-4" />
                                            </div>

                                            <span className="max-w-[220px] truncate text-sm text-slate-600">
                                                {row.email}
                                            </span>
                                        </div>
                                    ) : (
                                        <span className="text-slate-300">
                                            —
                                        </span>
                                    ),
                            },

                            {
                                key: 'students',
                                header: 'Élèves associés',
                                render: (row) =>
                                    row.students?.length ? (
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                                <GraduationCap className="h-4 w-4" />
                                            </div>

                                            <div className="min-w-0">
                                                <p className="max-w-[220px] truncate text-sm font-medium text-slate-700">
                                                    {row.students
                                                        .map(
                                                            (
                                                                student
                                                            ) =>
                                                                `${student.first_name} ${student.last_name}`
                                                        )
                                                        .join(
                                                            ', '
                                                        )}
                                                </p>

                                                <p className="text-xs text-slate-400">
                                                    {
                                                        row
                                                            .students
                                                            .length
                                                    }{' '}
                                                    élève
                                                    {row
                                                        .students
                                                        .length >
                                                    1
                                                        ? 's'
                                                        : ''}
                                                </p>
                                            </div>
                                        </div>
                                    ) : (
                                        <span className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                            Aucun élève
                                        </span>
                                    ),
                            },

                            {
                                key: 'actions',
                                header: '',
                                render: (row) => (
                                    <div className="flex justify-end gap-1">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                openEdit(
                                                    row
                                                )
                                            }
                                            className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-blue-50 hover:text-blue-600"
                                            aria-label="Modifier"
                                            title="Modifier"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() =>
                                                setToDelete(
                                                    row
                                                )
                                            }
                                            className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                            aria-label="Supprimer"
                                            title="Supprimer"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                ),
                            },
                        ]}

                        emptyState={
                            filteredGuardians.length === 0 && search ? (
                                <EmptyState
                                    icon={Search}
                                    title="Aucun résultat"
                                    description={`Aucun parent ne correspond à « ${search} ».`}
                                    action={!isCenseur && (
                                        <Button
                                            variant="secondary"
                                            onClick={() => setSearch('')}
                                        >
                                            Effacer la recherche
                                        </Button>
                                    )}
                                />
                            ) : (
                                <EmptyState
                                    icon={UserRound}
                                    title="Aucun parent enregistré"
                                    description="Ajoutez un parent pour pouvoir l'associer à un élève."
                                    action={
                                        !isCenseur && (
                                            <Button
                                                onClick={
                                                    openCreate
                                                }
                                            >
                                                <Plus className="h-4 w-4" />
                                                Ajouter un
                                                parent
                                            </Button>
                                        )
                                    }
                                />
                            )
                        }
                    />
                </Card.Body>
            </Card>

            {/* Modal */}
            <Modal
                show={showForm}
                onClose={closeForm}
                title={
                    editing
                        ? 'Modifier le parent'
                        : 'Nouveau parent'
                }
                size="lg"
                footer={
                    <>
                        <Button
                            variant="secondary"
                            onClick={closeForm}
                            disabled={processing}
                        >
                            Annuler
                        </Button>

                        <Button
                            onClick={submit}
                            loading={processing}
                        >
                            {editing ? (
                                <Pencil className="h-4 w-4" />
                            ) : (
                                <Plus className="h-4 w-4" />
                            )}

                            {editing
                                ? 'Enregistrer les modifications'
                                : 'Créer le parent'}
                        </Button>
                    </>
                }
            >
                <form
                    onSubmit={submit}
                    className="space-y-6"
                >
                    {/* Identité */}
                    <div className="rounded-2xl border border-cyan-100 bg-cyan-50/40 p-4">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600">
                                <UserRound className="h-4 w-4" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-slate-800">
                                    Identité
                                </p>

                                <p className="text-xs text-slate-500">
                                    Informations du parent ou tuteur
                                </p>
                            </div>
                        </div>

                        <Field
                            label="Nom complet"
                            required
                            error={errors.name}
                        >
                            <Input
                                value={data.name}
                                onChange={(e) =>
                                    setData(
                                        'name',
                                        e.target.value
                                    )
                                }
                                placeholder="Boni Alassane"
                            />
                        </Field>
                    </div>

                    {/* Accès */}
                    <div className="rounded-2xl border border-violet-100 bg-violet-50/40 p-4">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                                <ShieldCheck className="h-4 w-4" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-slate-800">
                                    Accès au compte
                                </p>

                                <p className="text-xs text-slate-500">
                                    Authentification du parent
                                </p>
                            </div>
                        </div>

                        <Field
                            label={
                                editing
                                    ? 'Nouveau mot de passe (optionnel)'
                                    : 'Mot de passe initial'
                            }
                            required={!editing}
                            error={errors.password}
                            hint={
                                editing
                                    ? 'Laissez vide pour conserver le mot de passe actuel.'
                                    : 'Le parent pourra ensuite modifier son mot de passe.'
                            }
                        >
                            <Input
                                type="password"
                                autoComplete="new-password"
                                placeholder="********"
                                value={data.password}
                                onChange={(e) =>
                                    setData(
                                        'password',
                                        e.target.value
                                    )
                                }
                            />
                        </Field>
                    </div>

                    {/* Contact */}
                    <div className="rounded-2xl border border-blue-100 bg-blue-50/40 p-4">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                <Phone className="h-4 w-4" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-slate-800">
                                    Coordonnées
                                </p>

                                <p className="text-xs text-slate-500">
                                    Informations utilisées pour la communication
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field
                                label="Téléphone"
                                required
                                error={errors.phone}
                                hint="Recevra les notifications SMS."
                            >
                                <Input
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData(
                                            'phone',
                                            e.target.value
                                        )
                                    }
                                    placeholder="+22901*********"
                                />
                            </Field>

                            <Field
                                label="Téléphone secondaire"
                                error={errors.phone_secondary}
                                hint="Second numéro du responsable. Les SMS continuent d'être envoyés au numéro principal."
                            >
                                <Input
                                    type="tel"
                                    value={
                                        data.phone_secondary ??
                                        ''
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'phone_secondary',
                                            e.target.value
                                        )
                                    }
                                    placeholder="+22901*********"
                                />
                            </Field>

                            <Field
                                label="Email"
                                error={errors.email}
                                hint="Utilisé pour les notifications email si disponible."
                            >
                                <Input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData(
                                            'email',
                                            e.target.value
                                        )
                                    }
                                    placeholder="parent@gmail.com"
                                />
                            </Field>
                        </div>
                    </div>

                    {/* Élèves */}
                    <div className="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                                <GraduationCap className="h-4 w-4" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-slate-800">
                                    Élèves associés
                                </p>

                                <p className="text-xs text-slate-500">
                                    Sélectionnez les enfants ou élèves dont ce parent est responsable.
                                </p>
                            </div>
                        </div>

                        <Field
                            label="Rechercher un élève"
                            error={errors.student_ids}
                        >
                            <div className="relative mb-3">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                <Input
                                    placeholder="Nom ou prénom…"
                                    value={studentSearch}
                                    onChange={(e) =>
                                        setStudentSearch(
                                            e.target.value
                                        )
                                    }
                                    className="pl-9"
                                />
                            </div>

                            <div className="max-h-52 space-y-1 overflow-y-auto rounded-xl border border-emerald-100 bg-white p-2">
                                {filteredStudents.length ===
                                0 ? (
                                    <div className="p-5 text-center">
                                        <Search className="mx-auto mb-2 h-6 w-6 text-slate-300" />

                                        <p className="text-sm text-slate-400">
                                            Aucun élève trouvé.
                                        </p>
                                    </div>
                                ) : (
                                    filteredStudents.map(
                                        (student) => {
                                            const selected =
                                                data.student_ids.includes(
                                                    student.id
                                                );

                                            return (
                                                <div
                                                    key={
                                                        student.id
                                                    }
                                                    className={`rounded-xl p-2 transition-colors ${
                                                        selected
                                                            ? 'bg-emerald-50'
                                                            : 'hover:bg-slate-50'
                                                    }`}
                                                >
                                                    <Checkbox
                                                        label={`${student.first_name} ${student.last_name}`}
                                                        checked={
                                                            selected
                                                        }
                                                        onChange={() =>
                                                            toggleStudent(
                                                                student.id
                                                            )
                                                        }
                                                    />
                                                </div>
                                            );
                                        }
                                    )
                                )}
                            </div>

                            {data.student_ids.length >
                                0 && (
                                <div className="mt-3 flex items-center gap-2 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-medium text-emerald-700">
                                    <UserCheck className="h-4 w-4" />

                                    {
                                        data.student_ids
                                            .length
                                    }{' '}
                                    élève
                                    {data.student_ids
                                        .length > 1
                                        ? 's'
                                        : ''}{' '}
                                    sélectionné
                                    {data.student_ids
                                        .length > 1
                                        ? 's'
                                        : ''}
                                </div>
                            )}
                        </Field>
                    </div>
                </form>
            </Modal>

            {/* Suppression */}
            <ConfirmDialog
                show={!!toDelete}
                onClose={() =>
                    setToDelete(null)
                }
                onConfirm={confirmDelete}
                loading={deleting}
                title="Supprimer le parent"
                description={
                    toDelete
                        ? `Supprimer « ${toDelete.name} » ?`
                        : ''
                }
                variant="danger"
                confirmLabel="Supprimer"
            />
        </AuthenticatedLayout>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
    className,
}) {
    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${className} p-5 text-white shadow-md`}
        >
            <div className="absolute -right-5 -top-5 h-20 w-20 rounded-full bg-white/10" />

            <div className="relative flex items-center justify-between">
                <div>
                    <p className="text-sm font-medium text-white/75">
                        {label}
                    </p>

                    <p className="mt-1 text-2xl font-bold">
                        {value}
                    </p>
                </div>

                <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20">
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    );
}