import { useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    Users,
    X,
    Search,
    Mail,
    Phone,
    BookOpen,
    GraduationCap,
    UserCheck,
    UserX,
    SlidersHorizontal,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Button from '@/Components/UI/Button';
import Badge from '@/Components/UI/Badge';
import Modal from '@/Components/UI/Modal';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import EmptyState from '@/Components/UI/EmptyState';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Checkbox from '@/Components/UI/Checkbox';

const empty = {
    name: '',
    email: '',
    phone: '',
    password: '',
    is_active: true,
    assignments: [],
};

export default function TeachersIndex({
    teachers,
    classes,
    subjects,
}) {
    const isCenseur = usePage().props.auth.user.role === 'censeur';
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
    } = useForm(empty);

    /*
    |--------------------------------------------------------------------------
    | Statistiques
    |--------------------------------------------------------------------------
    */

    const activeTeachers = useMemo(
        () => teachers.filter((teacher) => teacher.is_active),
        [teachers]
    );

    const inactiveTeachers = useMemo(
        () => teachers.filter((teacher) => !teacher.is_active),
        [teachers]
    );

    const teachersWithAssignments = useMemo(
        () =>
            teachers.filter(
                (teacher) =>
                    Array.isArray(teacher.assignments) &&
                    teacher.assignments.length > 0
            ),
        [teachers]
    );

    /*
    |--------------------------------------------------------------------------
    | Recherche + filtres
    |--------------------------------------------------------------------------
    */

    const filteredTeachers = useMemo(() => {
        const term = search.trim().toLowerCase();

        return teachers.filter((teacher) => {
            if (
                statusFilter === 'active' &&
                !teacher.is_active
            ) {
                return false;
            }

            if (
                statusFilter === 'inactive' &&
                teacher.is_active
            ) {
                return false;
            }

            if (!term) {
                return true;
            }

            const assignmentsText = (
                teacher.assignments ?? []
            )
                .map(
                    (assignment) =>
                        `${assignment.subject} ${assignment.class}`
                )
                .join(' ');

            return [
                teacher.name,
                teacher.email,
                teacher.phone,
                assignmentsText,
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value)
                        .toLowerCase()
                        .includes(term)
                );
        });
    }, [teachers, search, statusFilter]);

    /*
    |--------------------------------------------------------------------------
    | Formulaire
    |--------------------------------------------------------------------------
    */

    function openCreate() {
        reset();
        setEditing(null);
        setShowForm(true);
    }

    function openEdit(row) {
        setData({
            name: row.name,
            email: row.email,
            phone: row.phone ?? '',
            password: '',
            is_active: row.is_active,

            // On conserve le comportement V1 actuel.
            // Les affectations existantes ne sont pas
            // préchargées pour éviter une modification
            // accidentelle.
            assignments: [],
        });

        setEditing(row);
        setShowForm(true);
    }

    function closeForm() {
        setShowForm(false);
        setEditing(null);
        reset();
    }

    function addAssignment() {
        setData('assignments', [
            ...data.assignments,
            {
                class_id: '',
                subject_id: '',
            },
        ]);
    }

    function updateAssignment(
        index,
        field,
        value
    ) {
        const next = [...data.assignments];

        next[index] = {
            ...next[index],
            [field]: value,
        };

        setData('assignments', next);
    }

    function removeAssignment(index) {
        setData(
            'assignments',
            data.assignments.filter(
                (_, i) => i !== index
            )
        );
    }

    function submit(e) {
        e.preventDefault();

        const options = {
            onSuccess: () => {
                closeForm();
            },
        };

        if (editing) {
            put(
                route(
                    'admin.teachers.update',
                    editing.id
                ),
                options
            );
        } else {
            post(
                route('admin.teachers.store'),
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
                'admin.teachers.destroy',
                toDelete.id
            ),
            {
                onFinish: () => {
                    setDeleting(false);
                    setToDelete(null);
                },
            }
        );
    }

    return (
        <AuthenticatedLayout title="Enseignants">
            <Head title="Enseignants" />

            <div className="space-y-6">
                {/* =====================================================
                    HEADER
                ====================================================== */}

                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-blue-100">
                                <GraduationCap className="h-5 w-5" />
                                <span className="text-sm font-medium">
                                    Gestion pédagogique
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Enseignants
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-blue-100 sm:text-base">
                                Gérez les enseignants, leurs accès,
                                leurs affectations et leur statut
                                dans l'établissement.
                            </p>
                        </div>

                        {!isCenseur && <Button
                            onClick={openCreate}
                            className="bg-white text-blue-700 shadow-sm hover:bg-blue-50"
                        >
                            <Plus className="h-4 w-4" />
                            Nouvel enseignant
                        </Button>}
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                {/* =====================================================
                    STATISTIQUES
                ====================================================== */}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={Users}
                        label="Total enseignants"
                        value={teachers.length}
                        description="Enseignants enregistrés"
                        className="from-blue-500 to-indigo-600"
                    />

                    <StatCard
                        icon={UserCheck}
                        label="Actifs"
                        value={activeTeachers.length}
                        description="Comptes pouvant se connecter"
                        className="from-emerald-500 to-teal-600"
                    />

                    <StatCard
                        icon={BookOpen}
                        label="Affectés"
                        value={teachersWithAssignments.length}
                        description="Avec au moins une affectation"
                        className="from-violet-500 to-purple-600"
                    />

                    <StatCard
                        icon={UserX}
                        label="Désactivés"
                        value={inactiveTeachers.length}
                        description="Comptes désactivés"
                        className="from-amber-500 to-orange-600"
                    />
                </div>

                {/* =====================================================
                    CONTENU
                ====================================================== */}

                <Card>
                    <Card.Header
                        title="Liste des enseignants"
                        actions={!isCenseur && (
                            <Button
                                onClick={openCreate}
                                size="sm"
                            >
                                <Plus className="h-4 w-4" />
                                Ajouter
                            </Button>
                        )}
                    />

                    <Card.Body>
                        {/* Recherche + filtres */}

                        <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div className="relative w-full lg:max-w-xl">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                    <Input
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(
                                                e.target.value
                                            )
                                        }
                                        placeholder="Rechercher par nom, email, téléphone, classe ou matière..."
                                        className="pl-10"
                                    />
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <div className="flex items-center gap-2 text-sm text-slate-500">
                                        <SlidersHorizontal className="h-4 w-4" />
                                        <span>Statut :</span>
                                    </div>

                                    <FilterButton
                                        active={
                                            statusFilter === 'all'
                                        }
                                        onClick={() =>
                                            setStatusFilter(
                                                'all'
                                            )
                                        }
                                    >
                                        Tous
                                    </FilterButton>

                                    <FilterButton
                                        active={
                                            statusFilter ===
                                            'active'
                                        }
                                        onClick={() =>
                                            setStatusFilter(
                                                'active'
                                            )
                                        }
                                    >
                                        Actifs
                                    </FilterButton>

                                    <FilterButton
                                        active={
                                            statusFilter ===
                                            'inactive'
                                        }
                                        onClick={() =>
                                            setStatusFilter(
                                                'inactive'
                                            )
                                        }
                                    >
                                        Désactivés
                                    </FilterButton>
                                </div>
                            </div>

                            {(search ||
                                statusFilter !== 'all') && (
                                <div className="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                                    <p className="text-sm text-slate-500">
                                        <span className="font-semibold text-slate-700">
                                            {
                                                filteredTeachers.length
                                            }
                                        </span>{' '}
                                        résultat
                                        {filteredTeachers.length >
                                        1
                                            ? 's'
                                            : ''}
                                    </p>

                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSearch('');
                                            setStatusFilter(
                                                'all'
                                            );
                                        }}
                                        className="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700"
                                    >
                                        <X className="h-4 w-4" />
                                        Réinitialiser
                                    </button>
                                </div>
                            )}
                        </div>

                        {/* Tableau */}

                        <Table
                            rows={filteredTeachers}
                            emptyState={
                                search ||
                                statusFilter !== 'all' ? (
                                    <EmptyState
                                        icon={Search}
                                        title="Aucun résultat"
                                        description="Aucun enseignant ne correspond aux critères de recherche."
                                        action={!isCenseur && (
                                            <Button
                                                variant="secondary"
                                                onClick={() => {
                                                    setSearch('');
                                                    setStatusFilter(
                                                        'all'
                                                    );
                                                }}
                                            >
                                                Réinitialiser
                                            </Button>
                                        )}
                                    />
                                ) : (
                                    <EmptyState
                                        icon={Users}
                                        title="Aucun enseignant"
                                        description="Ajoutez votre premier enseignant."
                                        action={
                                            <Button
                                                onClick={
                                                    openCreate
                                                }
                                            >
                                                Ajouter un enseignant
                                            </Button>
                                        }
                                    />
                                )
                            }
                            columns={[
                                {
                                    key: 'name',
                                    header: 'Enseignant',
                                    render: (teacher) => !isCenseur && (
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-sm">
                                                {getInitials(
                                                    teacher.name
                                                )}
                                            </div>

                                            <div className="min-w-0">
                                                <div className="truncate font-semibold text-slate-800">
                                                    {
                                                        teacher.name
                                                    }
                                                </div>

                                                <div className="text-xs text-slate-400">
                                                    Enseignant
                                                </div>
                                            </div>
                                        </div>
                                    ),
                                },

                                {
                                    key: 'contact',
                                    header: 'Contact',
                                    render: (teacher) => (
                                        <div className="space-y-1">
                                            {teacher.email && (
                                                <div className="flex items-center gap-2 text-sm text-slate-600">
                                                    <Mail className="h-3.5 w-3.5 text-slate-400" />
                                                    <span className="max-w-[220px] truncate">
                                                        {
                                                            teacher.email
                                                        }
                                                    </span>
                                                </div>
                                            )}

                                            {teacher.phone && (
                                                <div className="flex items-center gap-2 text-sm text-slate-500">
                                                    <Phone className="h-3.5 w-3.5 text-slate-400" />
                                                    {
                                                        teacher.phone
                                                    }
                                                </div>
                                            )}

                                            {!teacher.email &&
                                                !teacher.phone && (
                                                    <span className="text-slate-400">
                                                        —
                                                    </span>
                                                )}
                                        </div>
                                    ),
                                },

                                {
                                    key: 'assignments',
                                    header: 'Affectations',
                                    render: (teacher) => {
                                        const assignments =
                                            teacher.assignments ??
                                            [];

                                        if (
                                            assignments.length ===
                                            0
                                        ) {
                                            return (
                                                <div className="flex items-center gap-2 text-sm text-slate-400">
                                                    <BookOpen className="h-4 w-4" />
                                                    Aucune affectation
                                                </div>
                                            );
                                        }

                                        return (
                                            <div className="min-w-[220px]">
                                                <div className="mb-2 flex items-center gap-2">
                                                    <Badge tone="blue">
                                                        {
                                                            assignments.length
                                                        }{' '}
                                                        affectation
                                                        {assignments.length >
                                                        1
                                                            ? 's'
                                                            : ''}
                                                    </Badge>
                                                </div>

                                                <div className="flex flex-wrap gap-1.5">
                                                    {assignments
                                                        .slice(
                                                            0,
                                                            3
                                                        )
                                                        .map(
                                                            (
                                                                assignment,
                                                                index
                                                            ) => (
                                                                <span
                                                                    key={
                                                                        index
                                                                    }
                                                                    className="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600"
                                                                >
                                                                    {
                                                                        assignment.subject
                                                                    }{' '}
                                                                    ·{' '}
                                                                    {
                                                                        assignment.class
                                                                    }
                                                                </span>
                                                            )
                                                        )}

                                                    {assignments.length >
                                                        3 && (
                                                        <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-500">
                                                            +
                                                            {assignments.length -
                                                                3}{' '}
                                                            autre
                                                            {assignments.length -
                                                                3 >
                                                            1
                                                                ? 's'
                                                                : ''}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    },
                                },

                                {
                                    key: 'is_active',
                                    header: 'Statut',
                                    render: (teacher) =>
                                        teacher.is_active ? (
                                            <Badge tone="green">
                                                <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-emerald-500" />
                                                Actif
                                            </Badge>
                                        ) : (
                                            <Badge tone="red">
                                                <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-red-500" />
                                                Désactivé
                                            </Badge>
                                        ),
                                },

                                {
                                    key: 'actions',
                                    header: '',
                                    render: (teacher) => (
                                        <div className="flex justify-end gap-1">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openEdit(
                                                        teacher
                                                    )
                                                }
                                                className="rounded-lg p-2 text-slate-500 transition hover:bg-blue-50 hover:text-blue-600"
                                                aria-label="Modifier"
                                                title="Modifier"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setToDelete(
                                                        teacher
                                                    )
                                                }
                                                className="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                aria-label="Supprimer"
                                                title="Supprimer"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    ),
                                },
                            ]}
                        />
                    </Card.Body>
                </Card>
            </div>

            {/* =========================================================
                MODAL ENSEIGNANT
            ========================================================== */}

            <Modal
                show={showForm}
                onClose={closeForm}
                title={
                    editing
                        ? "Modifier l'enseignant"
                        : 'Nouvel enseignant'
                }
                size="lg"
                footer={
                    <>
                        <Button
                            variant="secondary"
                            onClick={closeForm}
                        >
                            Annuler
                        </Button>

                        <Button
                            onClick={submit}
                            loading={processing}
                        >
                            {editing
                                ? 'Enregistrer les modifications'
                                : 'Créer l’enseignant'}
                        </Button>
                    </>
                }
            >
                <form
                    onSubmit={submit}
                    className="space-y-6"
                >
                    {/* Identité */}

                    <FormSection
                        icon={Users}
                        title="Informations personnelles"
                        description="Identité et coordonnées de l'enseignant."
                    >
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                                    placeholder="Nom et prénom"
                                />
                            </Field>

                            <Field
                                label="Téléphone"
                                error={errors.phone}
                            >
                                <Input
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData(
                                            'phone',
                                            e.target.value
                                        )
                                    }
                                    placeholder="+229 ..."
                                />
                            </Field>
                        </div>

                        <Field
                            label="Email"
                            required
                            error={errors.email}
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
                                placeholder="enseignant@ecole.com"
                            />
                        </Field>
                    </FormSection>

                    {/* Accès */}

                    <FormSection
                        icon={UserCheck}
                        title="Accès au compte"
                        description="Identifiants et statut de connexion."
                    >
                        <Field
                            label={
                                editing
                                    ? 'Nouveau mot de passe'
                                    : 'Mot de passe'
                            }
                            required={!editing}
                            error={errors.password}
                            hint={
                                editing
                                    ? 'Laisser vide pour conserver le mot de passe actuel.'
                                    : 'Minimum 8 caractères.'
                            }
                        >
                            <Input
                                type="password"
                                autoComplete="new-password"
                                value={data.password}
                                onChange={(e) =>
                                    setData(
                                        'password',
                                        e.target.value
                                    )
                                }
                                placeholder={
                                    editing
                                        ? '••••••••'
                                        : 'Mot de passe initial'
                                }
                            />
                        </Field>

                        {editing && (
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <Checkbox
                                    label="Compte actif"
                                    checked={
                                        data.is_active
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'is_active',
                                            e.target.checked
                                        )
                                    }
                                />

                                <p className="mt-1 pl-6 text-xs text-slate-500">
                                    Un compte désactivé ne peut
                                    pas se connecter à la
                                    plateforme.
                                </p>
                            </div>
                        )}
                    </FormSection>

                    {/* Affectations */}

                    <FormSection
                        icon={BookOpen}
                        title="Affectations pédagogiques"
                        description="Associez l'enseignant à ses classes et matières."
                    >
                        <Field
                            label="Classe / matière"
                            error={errors.assignments}
                            hint={
                                editing
                                    ? 'Redéfinir ici remplace toutes les affectations existantes de cet enseignant.'
                                    : 'Ajoutez une ou plusieurs affectations.'
                            }
                        >
                            <div className="space-y-3">
                                {data.assignments.length ===
                                    0 && (
                                    <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center">
                                        <BookOpen className="mx-auto h-8 w-8 text-slate-300" />

                                        <p className="mt-2 text-sm font-medium text-slate-600">
                                            Aucune affectation
                                        </p>

                                        <p className="mt-1 text-xs text-slate-400">
                                            Ajoutez une classe et
                                            une matière.
                                        </p>
                                    </div>
                                )}

                                {data.assignments.map(
                                    (assignment, index) => (
                                        <div
                                            key={index}
                                            className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"
                                        >
                                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                                <div className="flex-1">
                                                    <Select
                                                        value={
                                                            assignment.class_id
                                                        }
                                                        onChange={(
                                                            e
                                                        ) =>
                                                            updateAssignment(
                                                                index,
                                                                'class_id',
                                                                e
                                                                    .target
                                                                    .value
                                                            )
                                                        }
                                                    >
                                                        <option value="">
                                                            Classe…
                                                        </option>

                                                        {classes.map(
                                                            (
                                                                classroom
                                                            ) => (
                                                                <option
                                                                    key={
                                                                        classroom.id
                                                                    }
                                                                    value={
                                                                        classroom.id
                                                                    }
                                                                >
                                                                    {
                                                                        classroom.name
                                                                    }
                                                                </option>
                                                            )
                                                        )}
                                                    </Select>
                                                </div>

                                                <div className="flex-1">
                                                    <Select
                                                        value={
                                                            assignment.subject_id
                                                        }
                                                        onChange={(
                                                            e
                                                        ) =>
                                                            updateAssignment(
                                                                index,
                                                                'subject_id',
                                                                e
                                                                    .target
                                                                    .value
                                                            )
                                                        }
                                                    >
                                                        <option value="">
                                                            Matière…
                                                        </option>

                                                        {subjects.map(
                                                            (
                                                                subject
                                                            ) => (
                                                                <option
                                                                    key={
                                                                        subject.id
                                                                    }
                                                                    value={
                                                                        subject.id
                                                                    }
                                                                >
                                                                    {
                                                                        subject.name
                                                                    }
                                                                </option>
                                                            )
                                                        )}
                                                    </Select>
                                                </div>

                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        removeAssignment(
                                                            index
                                                        )
                                                    }
                                                    className="self-end rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600 sm:self-auto"
                                                    aria-label="Retirer"
                                                    title="Retirer cette affectation"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    )
                                )}

                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    onClick={
                                        addAssignment
                                    }
                                >
                                    <Plus className="h-4 w-4" />
                                    Ajouter une affectation
                                </Button>
                            </div>
                        </Field>
                    </FormSection>
                </form>
            </Modal>

            {/* =========================================================
                SUPPRESSION
            ========================================================== */}

            <ConfirmDialog
                show={!!toDelete}
                onClose={() =>
                    setToDelete(null)
                }
                onConfirm={confirmDelete}
                loading={deleting}
                title="Supprimer l'enseignant"
                description={
                    toDelete
                        ? `Supprimer « ${toDelete.name} » ? S'il a déjà des évaluations, il sera désactivé au lieu d'être supprimé.`
                        : ''
                }
                variant="danger"
                confirmLabel="Supprimer"
            />
        </AuthenticatedLayout>
    );
}

/*
|--------------------------------------------------------------------------
| Composants locaux
|--------------------------------------------------------------------------
*/

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

function FilterButton({
    active,
    onClick,
    children,
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={[
                'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                active
                    ? 'bg-blue-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100',
            ].join(' ')}
        >
            {children}
        </button>
    );
}

function FormSection({
    icon: Icon,
    title,
    description,
    children,
}) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 sm:p-5">
            <div className="mb-5 flex items-start gap-3">
                <div className="rounded-xl bg-blue-100 p-2.5 text-blue-600">
                    <Icon className="h-5 w-5" />
                </div>

                <div>
                    <h3 className="font-semibold text-slate-800">
                        {title}
                    </h3>

                    <p className="mt-1 text-sm text-slate-500">
                        {description}
                    </p>
                </div>
            </div>

            <div className="space-y-4">
                {children}
            </div>
        </section>
    );
}

function getInitials(name = '') {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}