import { useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    BookOpen,
    Search,
    School,
    Tags,
    X,
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
import Checkbox from '@/Components/UI/Checkbox';

const empty = {
    name: '',
    code: '',
    class_ids: [],
};

export default function SubjectsIndex({
    subjects,
    classes,
}) {
    const isCenseur = usePage().props.auth.user.role === 'censeur';
    const routePrefix = isCenseur ? 'censeur' : 'admin';
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const [search, setSearch] = useState('');
    const [classFilter, setClassFilter] = useState('all');

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

    const subjectsWithCode = useMemo(
        () =>
            subjects.filter(
                (subject) =>
                    subject.code &&
                    String(subject.code).trim() !== ''
            ),
        [subjects]
    );

    const subjectsWithClasses = useMemo(
        () =>
            subjects.filter(
                (subject) =>
                    Array.isArray(subject.classes) &&
                    subject.classes.length > 0
            ),
        [subjects]
    );

    const totalClassLinks = useMemo(
        () =>
            subjects.reduce(
                (total, subject) =>
                    total +
                    (subject.classes?.length ?? 0),
                0
            ),
        [subjects]
    );

    const coveredClassIds = useMemo(() => {
        const ids = new Set();

        subjects.forEach((subject) => {
            (subject.classes ?? []).forEach((classroom) => {
                ids.add(classroom.id);
            });
        });

        return ids;
    }, [subjects]);

    /*
    |--------------------------------------------------------------------------
    | Recherche + filtre
    |--------------------------------------------------------------------------
    */

    const filteredSubjects = useMemo(() => {
        const term = search.trim().toLowerCase();

        return subjects.filter((subject) => {
            if (classFilter !== 'all') {
                const hasClass = (subject.classes ?? []).some(
                    (classroom) =>
                        String(classroom.id) ===
                        String(classFilter)
                );

                if (!hasClass) {
                    return false;
                }
            }

            if (!term) {
                return true;
            }

            const classesText = (subject.classes ?? [])
                .map((classroom) => classroom.name)
                .join(' ');

            return [
                subject.name,
                subject.code,
                classesText,
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value)
                        .toLowerCase()
                        .includes(term)
                );
        });
    }, [subjects, search, classFilter]);

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
            code: row.code ?? '',
            class_ids: (row.classes ?? []).map(
                (classroom) => classroom.id
            ),
        });

        setEditing(row);
        setShowForm(true);
    }

    function closeForm() {
        setShowForm(false);
        setEditing(null);
        reset();
    }

    function toggleClass(id) {
        setData(
            'class_ids',
            data.class_ids.includes(id)
                ? data.class_ids.filter(
                      (classId) => classId !== id
                  )
                : [...data.class_ids, id]
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
                    `${routePrefix}.subjects.update`,
                    editing.id
                ),
                options
            );
        } else {
            post(
                route(`${routePrefix}.subjects.store`),
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
                `${routePrefix}.subjects.destroy`,
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
        <AuthenticatedLayout title="Matières">
            <Head title="Matières" />

            <div className="space-y-6">
                {/* =====================================================
                    HEADER
                ====================================================== */}

                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-emerald-100">
                                <BookOpen className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Organisation pédagogique
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Matières
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-emerald-100 sm:text-base">
                                Gérez les matières enseignées et leur
                                association avec les différentes
                                classes de l'établissement.
                            </p>
                        </div>

                        <Button
                            onClick={openCreate}
                            className="bg-white text-emerald-700 shadow-sm hover:bg-emerald-50"
                        >
                            <Plus className="h-4 w-4" />
                            Nouvelle matière
                        </Button>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                {/* =====================================================
                    STATISTIQUES
                ====================================================== */}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={BookOpen}
                        label="Total matières"
                        value={subjects.length}
                        description="Matières enregistrées"
                        className="from-emerald-500 to-teal-600"
                    />

                    <StatCard
                        icon={Tags}
                        label="Avec code"
                        value={subjectsWithCode.length}
                        description="Matières identifiées par un code"
                        className="from-blue-500 to-indigo-600"
                    />

                    <StatCard
                        icon={School}
                        label="Matières affectées"
                        value={subjectsWithClasses.length}
                        description="Avec au moins une classe"
                        className="from-violet-500 to-purple-600"
                    />

                    <StatCard
                        icon={School}
                        label="Classes couvertes"
                        value={coveredClassIds.size}
                        description={`${totalClassLinks} affectation${totalClassLinks > 1 ? 's' : ''} matière / classe`}
                        className="from-amber-500 to-orange-600"
                    />
                </div>

                {/* =====================================================
                    TABLEAU
                ====================================================== */}

                <Card>
                    <Card.Header
                        title="Liste des matières"
                        actions={
                            <Button
                                onClick={openCreate}
                                size="sm"
                            >
                                <Plus className="h-4 w-4" />
                                Ajouter
                            </Button>
                        }
                    />

                    <Card.Body>
                        {/* Recherche + filtre */}

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
                                        placeholder="Rechercher une matière, un code ou une classe..."
                                        className="pl-10"
                                    />
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <div className="flex items-center gap-2 text-sm text-slate-500">
                                        <SlidersHorizontal className="h-4 w-4" />

                                        <span>
                                            Classe :
                                        </span>
                                    </div>

                                    <select
                                        value={classFilter}
                                        onChange={(e) =>
                                            setClassFilter(
                                                e.target.value
                                            )
                                        }
                                        className="rounded-lg border-0 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-emerald-500"
                                    >
                                        <option value="all">
                                            Toutes les classes
                                        </option>

                                        {classes.map(
                                            (classroom) => (
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
                                    </select>
                                </div>
                            </div>

                            {(search ||
                                classFilter !== 'all') && (
                                <div className="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                                    <p className="text-sm text-slate-500">
                                        <span className="font-semibold text-slate-700">
                                            {
                                                filteredSubjects.length
                                            }
                                        </span>{' '}
                                        résultat
                                        {filteredSubjects.length >
                                        1
                                            ? 's'
                                            : ''}
                                    </p>

                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSearch('');
                                            setClassFilter(
                                                'all'
                                            );
                                        }}
                                        className="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700"
                                    >
                                        <X className="h-4 w-4" />
                                        Réinitialiser
                                    </button>
                                </div>
                            )}
                        </div>

                        <Table
                            rows={filteredSubjects}
                            emptyState={
                                search ||
                                classFilter !== 'all' ? (
                                    <EmptyState
                                        icon={Search}
                                        title="Aucun résultat"
                                        description="Aucune matière ne correspond aux critères sélectionnés."
                                        action={
                                            <Button
                                                variant="secondary"
                                                onClick={() => {
                                                    setSearch('');
                                                    setClassFilter(
                                                        'all'
                                                    );
                                                }}
                                            >
                                                Réinitialiser
                                            </Button>
                                        }
                                    />
                                ) : (
                                    <EmptyState
                                        icon={BookOpen}
                                        title="Aucune matière"
                                        description="Créez votre première matière."
                                        action={
                                            <Button
                                                onClick={
                                                    openCreate
                                                }
                                            >
                                                Créer une matière
                                            </Button>
                                        }
                                    />
                                )
                            }
                            columns={[
                                {
                                    key: 'name',
                                    header: 'Matière',
                                    render: (subject) => !isCenseur && (
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                                                <BookOpen className="h-5 w-5" />
                                            </div>

                                            <div>
                                                <div className="font-semibold text-slate-800">
                                                    {
                                                        subject.name
                                                    }
                                                </div>

                                                <div className="text-xs text-slate-400">
                                                    Matière pédagogique
                                                </div>
                                            </div>
                                        </div>
                                    ),
                                },

                                {
                                    key: 'code',
                                    header: 'Code',
                                    render: (subject) =>
                                        subject.code ? (
                                            <Badge tone="blue">
                                                {
                                                    subject.code
                                                }
                                            </Badge>
                                        ) : (
                                            <span className="text-slate-400">
                                                Non défini
                                            </span>
                                        ),
                                },

                                {
                                    key: 'classes',
                                    header: 'Classes',
                                    render: (subject) => {
                                        const subjectClasses =
                                            subject.classes ??
                                            [];

                                        if (
                                            subjectClasses.length ===
                                            0
                                        ) {
                                            return (
                                                <div className="flex items-center gap-2 text-sm text-slate-400">
                                                    <School className="h-4 w-4" />
                                                    Aucune classe
                                                </div>
                                            );
                                        }

                                        return (
                                            <div className="min-w-[220px]">
                                                <div className="mb-2">
                                                    <Badge tone="green">
                                                        {
                                                            subjectClasses.length
                                                        }{' '}
                                                        classe
                                                        {subjectClasses.length >
                                                        1
                                                            ? 's'
                                                            : ''}
                                                    </Badge>
                                                </div>

                                                <div className="flex flex-wrap gap-1.5">
                                                    {subjectClasses
                                                        .slice(
                                                            0,
                                                            4
                                                        )
                                                        .map(
                                                            (
                                                                classroom
                                                            ) => (
                                                                <span
                                                                    key={
                                                                        classroom.id
                                                                    }
                                                                    className="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600"
                                                                >
                                                                    {
                                                                        classroom.name
                                                                    }
                                                                </span>
                                                            )
                                                        )}

                                                    {subjectClasses.length >
                                                        4 && (
                                                        <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-500">
                                                            +
                                                            {subjectClasses.length -
                                                                4}{' '}
                                                            autre
                                                            {subjectClasses.length -
                                                                4 >
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
                                    key: 'actions',
                                    header: '',
                                    render: (subject) => (
                                        <div className="flex justify-end gap-1">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openEdit(
                                                        subject
                                                    )
                                                }
                                                className="rounded-lg p-2 text-slate-500 transition hover:bg-emerald-50 hover:text-emerald-600"
                                                aria-label="Modifier"
                                                title="Modifier"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setToDelete(
                                                        subject
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
                MODAL
            ========================================================== */}

            <Modal
                show={showForm}
                onClose={closeForm}
                title={
                    editing
                        ? 'Modifier la matière'
                        : 'Nouvelle matière'
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
                                : 'Créer la matière'}
                        </Button>
                    </>
                }
            >
                <form
                    onSubmit={submit}
                    className="space-y-6"
                >
                    {/* Informations */}

                    <div className="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5">
                        <div className="mb-5 flex items-start gap-3">
                            <div className="rounded-xl bg-emerald-100 p-2.5 text-emerald-600">
                                <BookOpen className="h-5 w-5" />
                            </div>

                            <div>
                                <h3 className="font-semibold text-slate-800">
                                    Informations de la matière
                                </h3>

                                <p className="mt-1 text-sm text-slate-500">
                                    Définissez le nom et
                                    l'identifiant de la matière.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <Field
                                label="Nom de la matière"
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
                                    placeholder="Ex : Mathématiques"
                                />
                            </Field>

                            <Field
                                label="Code"
                                error={errors.code}
                                hint="Optionnel — exemple : MATH"
                            >
                                <Input
                                    value={data.code}
                                    onChange={(e) =>
                                        setData(
                                            'code',
                                            e.target.value
                                        )
                                    }
                                    placeholder="Ex : MATH"
                                />
                            </Field>
                        </div>
                    </div>

                    {/* Classes */}

                    <div className="rounded-2xl border border-blue-100 bg-blue-50/50 p-5">
                        <div className="mb-5 flex items-start justify-between gap-3">
                            <div className="flex items-start gap-3">
                                <div className="rounded-xl bg-blue-100 p-2.5 text-blue-600">
                                    <School className="h-5 w-5" />
                                </div>

                                <div>
                                    <h3 className="font-semibold text-slate-800">
                                        Classes concernées
                                    </h3>

                                    <p className="mt-1 text-sm text-slate-500">
                                        Sélectionnez les classes dans
                                        lesquelles cette matière est
                                        enseignée.
                                    </p>
                                </div>
                            </div>

                            <Badge tone="blue">
                                {data.class_ids.length}{' '}
                                sélectionnée
                                {data.class_ids.length >
                                1
                                    ? 's'
                                    : ''}
                            </Badge>
                        </div>

                        <Field
                            label="Classes"
                            error={errors.class_ids}
                        >
                            {classes.length === 0 ? (
                                <div className="rounded-xl border border-dashed border-slate-300 bg-white p-5 text-center">
                                    <School className="mx-auto h-8 w-8 text-slate-300" />

                                    <p className="mt-2 text-sm font-medium text-slate-600">
                                        Aucune classe disponible
                                    </p>

                                    <p className="mt-1 text-xs text-slate-400">
                                        Créez d'abord des classes
                                        avant d'associer cette
                                        matière.
                                    </p>
                                </div>
                            ) : (
                                <div className="grid max-h-64 grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {classes.map(
                                        (classroom) => (
                                            <label
                                                key={
                                                    classroom.id
                                                }
                                                className={[
                                                    'flex cursor-pointer items-center rounded-lg border p-3 transition',
                                                    data.class_ids.includes(
                                                        classroom.id
                                                    )
                                                        ? 'border-blue-200 bg-blue-50'
                                                        : 'border-transparent hover:bg-slate-50',
                                                ].join(
                                                    ' '
                                                )}
                                            >
                                                <Checkbox
                                                    label={
                                                        classroom.name
                                                    }
                                                    checked={data.class_ids.includes(
                                                        classroom.id
                                                    )}
                                                    onChange={() =>
                                                        toggleClass(
                                                            classroom.id
                                                        )
                                                    }
                                                />
                                            </label>
                                        )
                                    )}
                                </div>
                            )}
                        </Field>

                        {data.class_ids.length > 0 && (
                            <div className="mt-3 flex items-center justify-between">
                                <p className="text-xs text-slate-500">
                                    Cette matière sera disponible
                                    dans{' '}
                                    <strong>
                                        {
                                            data
                                                .class_ids
                                                .length
                                        }
                                    </strong>{' '}
                                    classe
                                    {data.class_ids.length >
                                    1
                                        ? 's'
                                        : ''}.
                                </p>

                                <button
                                    type="button"
                                    onClick={() =>
                                        setData(
                                            'class_ids',
                                            []
                                        )
                                    }
                                    className="text-xs font-medium text-slate-500 hover:text-red-600"
                                >
                                    Tout désélectionner
                                </button>
                            </div>
                        )}
                    </div>
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
                title="Supprimer la matière"
                description={
                    toDelete
                        ? `Supprimer « ${toDelete.name} » ? Cette action peut affecter les associations avec les classes.`
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
| Composant statistique
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