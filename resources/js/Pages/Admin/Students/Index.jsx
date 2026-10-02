import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    GraduationCap,
    Search,
    Users,
    UserCheck,
    UserX,
    School,
    UserRound,
    CalendarDays,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Table from '@/Components/UI/Table';
import Pagination from '@/Components/UI/Pagination';
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
    first_name: '',
    last_name: '',
    matricule: '',
    class_id: '',
    birth_date: '',
    gender: '',
    is_active: true,
    guardian_ids: [],
};

export default function StudentsIndex({
    students,
    classes,
    guardians,
    filters,
}) {
    const isCenseur = usePage().props.auth.user.role === 'censeur';
    const routePrefix = isCenseur ? 'censeur' : 'admin';
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const [search, setSearch] = useState(
        filters.search ?? ''
    );

    const [classFilter, setClassFilter] = useState(
        filters.class_id ?? ''
    );

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
     * Statistiques de la page courante.
     *
     * Si le backend fournit plus tard des compteurs globaux,
     * on pourra les utiliser ici à la place.
     */
    const activeCount = students.data.filter(
        (student) => student.is_active
    ).length;

    const inactiveCount = students.data.filter(
        (student) => !student.is_active
    ).length;

    const withGuardianCount = students.data.filter(
        (student) => student.guardians?.length > 0
    ).length;

    function applyFilters(next = {}) {
        router.get(
            route(`${routePrefix}.students.index`),
            {
                search,
                class_id: classFilter,
                ...next,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    }

    function openCreate() {
        reset();
        setEditing(null);
        setShowForm(true);
    }

    function openEdit(row) {
        setData({
            first_name: row.first_name,
            last_name: row.last_name,
            matricule: row.matricule ?? '',
            class_id: row.class_room?.id ?? '',
            birth_date: row.birth_date ?? '',
            gender: row.gender ?? '',
            is_active: row.is_active,
            guardian_ids: row.guardians.map(
                (guardian) => guardian.id
            ),
        });

        setEditing(row);
        setShowForm(true);
    }

    function closeForm() {
        if (processing) {
            return;
        }

        setShowForm(false);
        setEditing(null);
        reset();
    }

    function toggleGuardian(id) {
        setData(
            'guardian_ids',
            data.guardian_ids.includes(id)
                ? data.guardian_ids.filter(
                      (guardianId) => guardianId !== id
                  )
                : [...data.guardian_ids, id]
        );
    }

    function submit(e) {
        e.preventDefault();

        const options = {
            onSuccess: () => {
                setShowForm(false);
                setEditing(null);
                reset();
            },
        };

        if (editing) {
            put(
                route(
                    'admin.students.update',
                    editing.id
                ),
                options
            );
        } else {
            post(
                route('admin.students.store'),
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
                'admin.students.destroy',
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
        <AuthenticatedLayout title="Élèves">
            <Head title="Élèves" />

            {/* Header */}
            <div className="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 text-white shadow-lg">
                <div className="relative p-5 sm:p-6">
                    <div className="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10" />
                    <div className="absolute -bottom-16 right-24 h-40 w-40 rounded-full bg-white/5" />

                    <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                                    <GraduationCap className="h-5 w-5" />
                                </div>

                                <span className="text-sm font-medium text-white/80">
                                    Gestion scolaire
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Élèves
                            </h1>

                            <p className="mt-1 max-w-xl text-sm text-white/75">
                                Gérez les dossiers des élèves,
                                leurs classes et leurs responsables.
                            </p>
                        </div>

                        {!isCenseur && <Button
                            onClick={openCreate}
                            className="bg-white text-blue-700 shadow-md hover:bg-blue-50"
                        >
                            <Plus className="h-4 w-4" />
                            Nouvel élève
                        </Button>}
                    </div>
                </div>
            </div>

            {/* Statistiques */}
            <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard
                    icon={GraduationCap}
                    label="Élèves"
                    value={students.total}
                    className="from-blue-500 to-indigo-600"
                />

                <StatCard
                    icon={UserCheck}
                    label="Actifs"
                    value={activeCount}
                    className="from-emerald-500 to-teal-600"
                />

                <StatCard
                    icon={UserX}
                    label="Inactifs"
                    value={inactiveCount}
                    className="from-slate-500 to-slate-700"
                />

                <StatCard
                    icon={Users}
                    label="Avec parent"
                    value={withGuardianCount}
                    className="from-violet-500 to-purple-600"
                />
            </div>

            <Card className="overflow-hidden">
                <Card.Header
                    title="Liste des élèves"
                    description={`${students.total} élève(s) enregistré(s)`}
                />

                <Card.Body>
                    {/* Filtres */}
                    <div className="mb-5 rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                        <div className="mb-3 flex items-center gap-2">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                <Search className="h-4 w-4" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-slate-800">
                                    Rechercher un élève
                                </p>

                                <p className="text-xs text-slate-500">
                                    Nom, prénom ou matricule
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-col gap-3 lg:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                <Input
                                    className="h-11 pl-9"
                                    placeholder="Rechercher par nom ou matricule…"
                                    value={search}
                                    onChange={(e) =>
                                        setSearch(
                                            e.target.value
                                        )
                                    }
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' &&
                                        applyFilters()
                                    }
                                />
                            </div>

                            <Select
                                className="h-11 lg:w-56"
                                value={classFilter}
                                onChange={(e) => {
                                    const value =
                                        e.target.value;

                                    setClassFilter(value);

                                    applyFilters({
                                        class_id: value,
                                    });
                                }}
                            >
                                <option value="">
                                    Toutes les classes
                                </option>

                                {classes.map((classRoom) => (
                                    <option
                                        key={classRoom.id}
                                        value={classRoom.id}
                                    >
                                        {classRoom.name}
                                    </option>
                                ))}
                            </Select>

                            <Button
                                variant="secondary"
                                onClick={() =>
                                    applyFilters()
                                }
                            >
                                <Search className="h-4 w-4" />
                                Rechercher
                            </Button>
                        </div>
                    </div>

                    {/* Tableau */}
                    <Table
                        rows={students.data}
                        emptyState={
                            <EmptyState
                                icon={GraduationCap}
                                title="Aucun élève trouvé"
                                description="Essayez d'autres critères ou ajoutez un nouvel élève."
                                action={!isCenseur && (
                                    <Button
                                        onClick={openCreate}
                                    >
                                        <Plus className="h-4 w-4" />
                                        Ajouter un élève
                                    </Button>
                                )}
                            />
                        }
                        columns={[
                            {
                                key: 'name',
                                header: 'Élève',
                                render: (row) => !isCenseur && (
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-blue-600">
                                            {row.first_name
                                                ?.charAt(0)
                                                ?.toUpperCase()}
                                            {row.last_name
                                                ?.charAt(0)
                                                ?.toUpperCase()}
                                        </div>

                                        <div className="min-w-0">
                                            <p className="truncate font-semibold text-slate-800">
                                                {
                                                    row.first_name
                                                }{' '}
                                                {
                                                    row.last_name
                                                }
                                            </p>

                                            <p className="text-xs text-slate-400">
                                                {row.gender ===
                                                'M'
                                                    ? 'Masculin'
                                                    : row.gender ===
                                                        'F'
                                                      ? 'Féminin'
                                                      : '—'}
                                            </p>
                                        </div>
                                    </div>
                                ),
                            },

                            {
                                key: 'matricule',
                                header: 'Matricule',
                                render: (row) =>
                                    row.matricule ? (
                                        <span className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            {row.matricule}
                                        </span>
                                    ) : (
                                        <span className="text-slate-300">
                                            —
                                        </span>
                                    ),
                            },

                            {
                                key: 'class_room',
                                header: 'Classe',
                                render: (row) => (
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                            <School className="h-4 w-4" />
                                        </div>

                                        <span className="font-medium text-slate-700">
                                            {row.class_room
                                                ?.name ??
                                                'Non affecté'}
                                        </span>
                                    </div>
                                ),
                            },

                            {
                                key: 'guardians',
                                header: 'Parent(s)',
                                render: (row) =>
                                    row.guardians?.length ? (
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cyan-50 text-cyan-600">
                                                <Users className="h-4 w-4" />
                                            </div>

                                            <div className="min-w-0">
                                                <p className="max-w-[180px] truncate text-sm font-medium text-slate-700">
                                                    {row.guardians
                                                        .map(
                                                            (
                                                                guardian
                                                            ) =>
                                                                guardian.name
                                                        )
                                                        .join(
                                                            ', '
                                                        )}
                                                </p>

                                                <p className="text-xs text-slate-400">
                                                    {row.guardians
                                                        .length}{' '}
                                                    responsable
                                                    {row
                                                        .guardians
                                                        .length >
                                                    1
                                                        ? 's'
                                                        : ''}
                                                </p>
                                            </div>
                                        </div>
                                    ) : (
                                        <span className="text-sm text-slate-400">
                                            Aucun parent
                                        </span>
                                    ),
                            },

                            {
                                key: 'birth_date',
                                header: 'Naissance',
                                render: (row) => (
                                    <div className="flex items-center gap-2 text-sm text-slate-600">
                                        <CalendarDays className="h-4 w-4 text-amber-500" />

                                        {row.birth_date ??
                                            '—'}
                                    </div>
                                ),
                            },

                            {
                                key: 'is_active',
                                header: 'Statut',
                                render: (row) =>
                                    row.is_active ? (
                                        <Badge tone="green">
                                            Actif
                                        </Badge>
                                    ) : (
                                        <Badge tone="red">
                                            Inactif
                                        </Badge>
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
                    />

                    <div className="mt-4 border-t border-slate-100 pt-4">
                        <Pagination meta={students} />
                    </div>
                </Card.Body>
            </Card>

            {/* Modal création / modification */}
            <Modal
                show={showForm}
                onClose={closeForm}
                title={
                    editing
                        ? "Modifier l'élève"
                        : 'Nouvel élève'
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
                                : "Créer l'élève"}
                        </Button>
                    </>
                }
            >
                <form
                    onSubmit={submit}
                    className="space-y-6"
                >
                    {/* Identité */}
                    <div className="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                <UserRound className="h-4 w-4" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-slate-800">
                                    Identité de l'élève
                                </p>

                                <p className="text-xs text-slate-500">
                                    Informations personnelles
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Field
                                label="Prénom"
                                required
                                error={errors.first_name}
                            >
                                <Input
                                    value={
                                        data.first_name
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'first_name',
                                            e.target.value
                                        )
                                    }
                                    placeholder="Ex. : Jean"
                                />
                            </Field>

                            <Field
                                label="Nom"
                                required
                                error={errors.last_name}
                            >
                                <Input
                                    value={
                                        data.last_name
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'last_name',
                                            e.target.value
                                        )
                                    }
                                    placeholder="Ex. : Boni"
                                />
                            </Field>
                        </div>
                    </div>

                    {/* Scolarité */}
                    <div className="rounded-2xl border border-violet-100 bg-violet-50/40 p-4">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                                <GraduationCap className="h-4 w-4" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-slate-800">
                                    Informations scolaires
                                </p>

                                <p className="text-xs text-slate-500">
                                    Classe et identification
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <Field
                                label="Matricule"
                                error={errors.matricule}
                            >
                                <Input
                                    value={
                                        data.matricule
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'matricule',
                                            e.target.value
                                        )
                                    }
                                    placeholder="Ex. : 2024-001"
                                />
                            </Field>

                            <Field
                                label="Classe"
                                error={errors.class_id}
                            >
                                <Select
                                    value={
                                        data.class_id
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'class_id',
                                            e.target.value
                                        )
                                    }
                                >
                                    <option value="">
                                        —
                                    </option>

                                    {classes.map(
                                        (classRoom) => (
                                            <option
                                                key={
                                                    classRoom.id
                                                }
                                                value={
                                                    classRoom.id
                                                }
                                            >
                                                {
                                                    classRoom.name
                                                }
                                            </option>
                                        )
                                    )}
                                </Select>
                            </Field>

                            <Field
                                label="Sexe"
                                error={errors.gender}
                            >
                                <Select
                                    value={
                                        data.gender
                                    }
                                    onChange={(e) =>
                                        setData(
                                            'gender',
                                            e.target.value
                                        )
                                    }
                                >
                                    <option value="">
                                        —
                                    </option>

                                    <option value="M">
                                        Masculin
                                    </option>

                                    <option value="F">
                                        Féminin
                                    </option>
                                </Select>
                            </Field>
                        </div>

                    <div className="mt-4">
                        <Field
                            label="Date de naissance"
                            error={errors.birth_date}
                        >
                            <Input
                                type="date"
                                className="max-w-[220px]"
                                max={(() => {
                                    const date = new Date();
                                    date.setFullYear(date.getFullYear() - 4);
                                    return date.toISOString().split('T')[0];
                                })()}
                                value={data.birth_date ?? ''}
                                onChange={(e) =>
                                    setData('birth_date', e.target.value)
                                }
                            />
                        </Field>
                    </div>
                        {editing && (
                            <div className="mt-4 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 to-teal-50 p-4">
                                <div className="flex items-center justify-between gap-4">
                                    <div className="flex items-center gap-3">
                                        <div
                                            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                                                data.is_active
                                                    ? 'bg-emerald-100 text-emerald-600'
                                                    : 'bg-slate-100 text-slate-500'
                                            }`}
                                        >
                                            <span
                                                className={`h-3 w-3 rounded-full ${
                                                    data.is_active
                                                        ? 'bg-emerald-500'
                                                        : 'bg-slate-400'
                                                }`}
                                            />
                                        </div>

                                        <div>
                                            <p className="text-sm font-semibold text-slate-800">
                                                Statut de l'élève
                                            </p>

                                            <p className="mt-0.5 text-xs text-slate-500">
                                                {data.is_active
                                                    ? 'L’élève est actuellement actif dans l’établissement.'
                                                    : 'L’élève est actuellement désactivé.'}
                                            </p>
                                        </div>
                                    </div>

                                    <Checkbox
                                        label=""
                                        checked={data.is_active}
                                        onChange={(e) =>
                                            setData(
                                                'is_active',
                                                e.target.checked
                                            )
                                        }
                                    />
                                </div>

                                <div className="mt-3 border-t border-emerald-100 pt-3">
                                    <span
                                        className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${
                                            data.is_active
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-slate-100 text-slate-600'
                                        }`}
                                    >
                                        {data.is_active ? '● Actif' : '● Inactif'}
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Parent / tuteur */}
                    <div className="rounded-2xl border border-cyan-100 bg-cyan-50/40 p-4">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600">
                                <Users className="h-4 w-4" />
                            </div>

                            <div>
                                <p className="text-sm font-semibold text-slate-800">
                                    Parent / tuteur
                                </p>

                                <p className="text-xs text-slate-500">
                                    Un seul responsable peut être associé à l'élève
                                    pour les notifications.
                                </p>
                            </div>
                        </div>

                        <Field
                            label="Responsable de l'élève"
                            error={errors.guardian_ids}
                        >
                            <div className="max-h-48 space-y-2 overflow-y-auto rounded-xl border border-cyan-100 bg-white p-2">
                                {guardians.length === 0 ? (
                                    <div className="p-4 text-center">
                                        <Users className="mx-auto mb-2 h-6 w-6 text-slate-300" />

                                        <p className="text-sm text-slate-400">
                                            Aucun parent enregistré pour l'instant.
                                        </p>
                                    </div>
                                ) : (
                                    guardians.map((guardian) => {
                                        const selected =
                                            data.guardian_ids[0] === guardian.id;

                                        return (
                                            <label
                                                key={guardian.id}
                                                className={`flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition ${
                                                    selected
                                                        ? 'border-cyan-300 bg-cyan-50 shadow-sm'
                                                        : 'border-transparent hover:border-slate-200 hover:bg-slate-50'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="guardian_id"
                                                    value={guardian.id}
                                                    checked={selected}
                                                    onChange={() =>
                                                        setData(
                                                            'guardian_ids',
                                                            [guardian.id]
                                                        )
                                                    }
                                                    className="h-4 w-4 border-slate-300 text-cyan-600 focus:ring-cyan-500"
                                                />

                                                <div className="min-w-0 flex-1">
                                                    <p className="text-sm font-semibold text-slate-800">
                                                        {guardian.name}
                                                    </p>

                                                    <p className="text-xs text-slate-500">
                                                        {guardian.phone || 'Téléphone non renseigné'}
                                                    </p>
                                                </div>

                                                {selected && (
                                                    <span className="rounded-full bg-cyan-100 px-2 py-1 text-[11px] font-semibold text-cyan-700">
                                                        Responsable
                                                    </span>
                                                )}
                                            </label>
                                        );
                                    })
                                )}
                            </div>
                        </Field>

                        <div className="mt-3 flex items-start gap-2 rounded-xl border border-cyan-100 bg-white/70 p-3">
                            <span className="mt-0.5 text-cyan-600">ⓘ</span>

                            <p className="text-xs leading-5 text-slate-600">
                                Le responsable sélectionné recevra les notifications
                                SMS liées à cet élève. Cela permet de maîtriser le
                                budget SMS de l'établissement.
                            </p>
                        </div>
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
                title="Supprimer l'élève"
                description={
                    toDelete
                        ? `Supprimer « ${toDelete.first_name} ${toDelete.last_name} » ? Cette action ne pourra pas être annulée.`
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