import { useMemo, useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Trash2,
    School as SchoolIcon,
    Search,
    Users,
    UserCheck,
    UserX,
    GraduationCap,
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

const empty = {
    name: '',
    level: '',
    capacity: '',
};

export default function ClassesIndex({ classes }) {
    const isCenseur = usePage().props.auth.user.role === 'censeur';
    const routePrefix = isCenseur ? 'censeur' : 'admin';
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const [search, setSearch] = useState('');
    const [capacityFilter, setCapacityFilter] = useState('all');

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

    const totalStudents = useMemo(
        () =>
            classes.reduce(
                (total, classroom) =>
                    total +
                    Number(classroom.students_count ?? 0),
                0
            ),
        [classes]
    );

    const classesWithStudents = useMemo(
        () =>
            classes.filter(
                (classroom) =>
                    Number(
                        classroom.students_count ?? 0
                    ) > 0
            ),
        [classes]
    );

    const fullClasses = useMemo(
        () =>
            classes.filter((classroom) => {
                if (
                    classroom.capacity === null ||
                    classroom.capacity === undefined ||
                    classroom.capacity === ''
                ) {
                    return false;
                }

                return (
                    Number(classroom.students_count ?? 0) >=
                    Number(classroom.capacity)
                );
            }),
        [classes]
    );

    const availablePlaces = useMemo(
        () =>
            classes.reduce((total, classroom) => {
                if (
                    classroom.capacity === null ||
                    classroom.capacity === undefined ||
                    classroom.capacity === ''
                ) {
                    return total;
                }

                return (
                    total +
                    Math.max(
                        Number(classroom.capacity) -
                            Number(
                                classroom.students_count ?? 0
                            ),
                        0
                    )
                );
            }, 0),
        [classes]
    );

    /*
    |--------------------------------------------------------------------------
    | Recherche / filtres
    |--------------------------------------------------------------------------
    */

    const filteredClasses = useMemo(() => {
        const term = search.trim().toLowerCase();

        return classes.filter((classroom) => {
            const studentsCount = Number(
                classroom.students_count ?? 0
            );

            const capacity =
                classroom.capacity !== null &&
                classroom.capacity !== undefined &&
                classroom.capacity !== ''
                    ? Number(classroom.capacity)
                    : null;

            if (
                capacityFilter === 'available' &&
                capacity !== null &&
                studentsCount >= capacity
            ) {
                return false;
            }

            if (
                capacityFilter === 'full' &&
                (capacity === null ||
                    studentsCount < capacity)
            ) {
                return false;
            }

            if (!term) {
                return true;
            }

            return [
                classroom.name,
                classroom.level,
                classroom.capacity,
                classroom.students_count,
            ]
                .filter(
                    (value) =>
                        value !== null &&
                        value !== undefined
                )
                .some((value) =>
                    String(value)
                        .toLowerCase()
                        .includes(term)
                );
        });
    }, [classes, search, capacityFilter]);

    /*
    |--------------------------------------------------------------------------
    | Actions
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
            level: row.level ?? '',
            capacity: row.capacity ?? '',
        });

        setEditing(row);
        setShowForm(true);
    }

    function closeForm() {
        setShowForm(false);
        setEditing(null);
        reset();
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
                    `${routePrefix}.classes.update`,
                    editing.id
                ),
                options
            );
        } else {
            post(
                route(`${routePrefix}.classes.store`),
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
                `${routePrefix}.classes.destroy`,
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
        <AuthenticatedLayout title="Classes">
            <Head title="Classes" />

            <div className="space-y-6">
                {/* =====================================================
                    HEADER
                ====================================================== */}

                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-purple-100">
                                <SchoolIcon className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Organisation pédagogique
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Classes
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-purple-100 sm:text-base">
                                Organisez les classes de
                                l'établissement, leur capacité et
                                leurs effectifs.
                            </p>
                        </div>

                        <Button
                            onClick={openCreate}
                            className="bg-white text-white shadow-sm hover:bg-purple-50"
                        >
                            <Plus className="h-4 w-4" />
                            Nouvelle classe
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
                        icon={SchoolIcon}
                        label="Total classes"
                        value={classes.length}
                        description="Classes enregistrées"
                        className="from-violet-500 to-purple-600"
                    />

                    <StatCard
                        icon={Users}
                        label="Élèves"
                        value={totalStudents}
                        description="Élèves répartis dans les classes"
                        className="from-blue-500 to-indigo-600"
                    />

                    <StatCard
                        icon={UserCheck}
                        label="Classes occupées"
                        value={classesWithStudents.length}
                        description="Avec au moins un élève"
                        className="from-emerald-500 to-teal-600"
                    />

                    <StatCard
                        icon={UserX}
                        label="Places disponibles"
                        value={availablePlaces}
                        description="Capacité restante"
                        className="from-amber-500 to-orange-600"
                    />
                </div>

                {/* =====================================================
                    TABLEAU
                ====================================================== */}

                <Card>
                    <Card.Header
                        title="Liste des classes"
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
                        {/* Recherche */}

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
                                        placeholder="Rechercher une classe ou un niveau..."
                                        className="pl-10"
                                    />
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <div className="flex items-center gap-2 text-sm text-slate-500">
                                        <SlidersHorizontal className="h-4 w-4" />

                                        <span>
                                            Occupation :
                                        </span>
                                    </div>

                                    <FilterButton
                                        active={
                                            capacityFilter ===
                                            'all'
                                        }
                                        onClick={() =>
                                            setCapacityFilter(
                                                'all'
                                            )
                                        }
                                    >
                                        Toutes
                                    </FilterButton>

                                    <FilterButton
                                        active={
                                            capacityFilter ===
                                            'available'
                                        }
                                        onClick={() =>
                                            setCapacityFilter(
                                                'available'
                                            )
                                        }
                                    >
                                        Places disponibles
                                    </FilterButton>

                                    <FilterButton
                                        active={
                                            capacityFilter ===
                                            'full'
                                        }
                                        onClick={() =>
                                            setCapacityFilter(
                                                'full'
                                            )
                                        }
                                    >
                                        Complètes
                                    </FilterButton>
                                </div>
                            </div>

                            {(search ||
                                capacityFilter !== 'all') && (
                                <div className="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                                    <p className="text-sm text-slate-500">
                                        <span className="font-semibold text-slate-700">
                                            {
                                                filteredClasses.length
                                            }
                                        </span>{' '}
                                        résultat
                                        {filteredClasses.length >
                                        1
                                            ? 's'
                                            : ''}
                                    </p>

                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSearch('');
                                            setCapacityFilter(
                                                'all'
                                            );
                                        }}
                                        className="inline-flex items-center gap-1 text-sm font-medium text-purple-600 hover:text-purple-700"
                                    >
                                        <X className="h-4 w-4" />
                                        Réinitialiser
                                    </button>
                                </div>
                            )}
                        </div>

                        <Table
                            rows={filteredClasses}
                            emptyState={
                                search ||
                                capacityFilter !== 'all' ? (
                                    <EmptyState
                                        icon={Search}
                                        title="Aucun résultat"
                                        description="Aucune classe ne correspond aux critères sélectionnés."
                                        action={
                                            <Button
                                                variant="secondary"
                                                onClick={() => {
                                                    setSearch('');
                                                    setCapacityFilter(
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
                                        icon={SchoolIcon}
                                        title="Aucune classe"
                                        description="Créez votre première classe."
                                        action={
                                            <Button
                                                onClick={
                                                    openCreate
                                                }
                                            >
                                                Créer une classe
                                            </Button>
                                        }
                                    />
                                )
                            }
                            columns={[
                                {
                                    key: 'name',
                                    header: 'Classe',
                                    render: (classroom) => !isCenseur && (
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-sm">
                                                <SchoolIcon className="h-5 w-5" />
                                            </div>

                                            <div>
                                                <div className="font-semibold text-slate-800">
                                                    {
                                                        classroom.name
                                                    }
                                                </div>

                                                {classroom.level && (
                                                    <div className="text-xs text-slate-400">
                                                        Niveau :{' '}
                                                        {
                                                            classroom.level
                                                        }
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ),
                                },

                                {
                                    key: 'level',
                                    header: 'Niveau',
                                    render: (classroom) =>
                                        classroom.level ? (
                                            <Badge tone="purple">
                                                {
                                                    classroom.level
                                                }
                                            </Badge>
                                        ) : (
                                            <span className="text-slate-400">
                                                —
                                            </span>
                                        ),
                                },

                                {
                                    key: 'capacity',
                                    header: 'Capacité',
                                    render: (classroom) =>
                                        classroom.capacity !==
                                            null &&
                                        classroom.capacity !==
                                            undefined &&
                                        classroom.capacity !==
                                            '' ? (
                                            <div className="flex items-center gap-2">
                                                <Users className="h-4 w-4 text-slate-400" />

                                                <span className="font-medium text-slate-700">
                                                    {
                                                        classroom.capacity
                                                    }
                                                </span>

                                                <span className="text-xs text-slate-400">
                                                    places
                                                </span>
                                            </div>
                                        ) : (
                                            <span className="text-slate-400">
                                                Non définie
                                            </span>
                                        ),
                                },

                                {
                                    key: 'students_count',
                                    header: 'Effectif',
                                    render: (classroom) => {
                                        const students =
                                            Number(
                                                classroom.students_count ??
                                                    0
                                            );

                                        const capacity =
                                            classroom.capacity !==
                                                null &&
                                            classroom.capacity !==
                                                undefined &&
                                            classroom.capacity !==
                                                ''
                                                ? Number(
                                                      classroom.capacity
                                                  )
                                                : null;

                                        const percentage =
                                            capacity &&
                                            capacity > 0
                                                ? Math.min(
                                                      Math.round(
                                                          (students /
                                                              capacity) *
                                                              100
                                                      ),
                                                      100
                                                  )
                                                : null;

                                        return (
                                            <div className="min-w-[130px]">
                                                <div className="mb-1.5 flex items-center justify-between">
                                                    <div className="flex items-center gap-1.5">
                                                        <GraduationCap className="h-4 w-4 text-slate-400" />

                                                        <span className="font-semibold text-slate-700">
                                                            {
                                                                students
                                                            }
                                                        </span>

                                                        <span className="text-xs text-slate-400">
                                                            élève
                                                            {students >
                                                            1
                                                                ? 's'
                                                                : ''}
                                                        </span>
                                                    </div>

                                                    {percentage !==
                                                        null && (
                                                        <span className="text-xs font-medium text-slate-500">
                                                            {
                                                                percentage
                                                            }
                                                            %
                                                        </span>
                                                    )}
                                                </div>

                                                {percentage !==
                                                    null && (
                                                    <div className="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                        <div
                                                            className={`h-full rounded-full transition-all ${
                                                                percentage >=
                                                                100
                                                                    ? 'bg-red-500'
                                                                    : percentage >=
                                                                        80
                                                                      ? 'bg-amber-500'
                                                                      : 'bg-emerald-500'
                                                            }`}
                                                            style={{
                                                                width: `${percentage}%`,
                                                            }}
                                                        />
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    },
                                },

                                {
                                    key: 'status',
                                    header: 'Occupation',
                                    render: (classroom) => {
                                        const students =
                                            Number(
                                                classroom.students_count ??
                                                    0
                                            );

                                        const capacity =
                                            classroom.capacity !==
                                                null &&
                                            classroom.capacity !==
                                                undefined &&
                                            classroom.capacity !==
                                                ''
                                                ? Number(
                                                      classroom.capacity
                                                  )
                                                : null;

                                        if (
                                            capacity === null ||
                                            capacity <= 0
                                        ) {
                                            return (
                                                <Badge tone="gray">
                                                    Non définie
                                                </Badge>
                                            );
                                        }

                                        if (
                                            students >=
                                            capacity
                                        ) {
                                            return (
                                                <Badge tone="red">
                                                    Complète
                                                </Badge>
                                            );
                                        }

                                        return (
                                            <Badge tone="green">
                                                {
                                                    capacity -
                                                        students
                                                }{' '}
                                                place
                                                {capacity -
                                                    students >
                                                1
                                                    ? 's'
                                                    : ''}{' '}
                                                disponible
                                                {capacity -
                                                    students >
                                                1
                                                    ? 's'
                                                    : ''}
                                            </Badge>
                                        );
                                    },
                                },

                                {
                                    key: 'actions',
                                    header: '',
                                    render: (classroom) => (
                                        <div className="flex justify-end gap-1">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openEdit(
                                                        classroom
                                                    )
                                                }
                                                className="rounded-lg p-2 text-slate-500 transition hover:bg-purple-50 hover:text-purple-600"
                                                aria-label="Modifier"
                                                title="Modifier"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setToDelete(
                                                        classroom
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
                        ? 'Modifier la classe'
                        : 'Nouvelle classe'
                }
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
                                : 'Créer la classe'}
                        </Button>
                    </>
                }
            >
                <form
                    onSubmit={submit}
                    className="space-y-5"
                >
                    <div className="rounded-2xl border border-purple-100 bg-purple-50/60 p-5">
                        <div className="mb-5 flex items-start gap-3">
                            <div className="rounded-xl bg-purple-100 p-2.5 text-purple-600">
                                <SchoolIcon className="h-5 w-5" />
                            </div>

                            <div>
                                <h3 className="font-semibold text-slate-800">
                                    Informations de la classe
                                </h3>

                                <p className="mt-1 text-sm text-slate-500">
                                    Définissez l'identité et le niveau
                                    de la classe.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <Field
                                label="Nom de la classe"
                                required
                                error={errors.name}
                                hint="Ex : 3ème A"
                            >
                                <Input
                                    value={data.name}
                                    onChange={(e) =>
                                        setData(
                                            'name',
                                            e.target.value
                                        )
                                    }
                                    placeholder="Ex : 3ème A"
                                />
                            </Field>

                            <Field
                                label="Niveau"
                                error={errors.level}
                                hint="Ex : 3ème"
                            >
                                <Input
                                    value={data.level}
                                    onChange={(e) =>
                                        setData(
                                            'level',
                                            e.target.value
                                        )
                                    }
                                    placeholder="Ex : 3ème"
                                />
                            </Field>

                            <Field
                                label="Capacité"
                                error={errors.capacity}
                                hint="Nombre maximal d'élèves autorisés dans cette classe."
                            >
                                <div className="relative">
                                    <Input
                                        type="number"
                                        min="1"
                                        value={
                                            data.capacity
                                        }
                                        onChange={(e) =>
                                            setData(
                                                'capacity',
                                                e.target.value
                                            )
                                        }
                                        placeholder="Ex : 40"
                                    />

                                    <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                                        élèves
                                    </span>
                                </div>
                            </Field>
                        </div>
                    </div>

                    {editing && (
                        <div className="rounded-xl border border-blue-100 bg-blue-50 p-4">
                            <div className="flex items-start gap-3">
                                <Users className="mt-0.5 h-5 w-5 shrink-0 text-blue-600" />

                                <div>
                                    <p className="text-sm font-semibold text-blue-900">
                                        Effectif actuel
                                    </p>

                                    <p className="mt-1 text-sm text-blue-700">
                                        Cette classe compte actuellement{' '}
                                        <strong>
                                            {
                                                editing.students_count
                                            }
                                        </strong>{' '}
                                        élève
                                        {Number(
                                            editing.students_count ??
                                                0
                                        ) > 1
                                            ? 's'
                                            : ''}
                                        .
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
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
                title="Supprimer la classe"
                description={
                    toDelete
                        ? `Supprimer « ${toDelete.name} » ? Les élèves associés ne seront pas supprimés.`
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
                    ? 'bg-purple-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-100',
            ].join(' ')}
        >
            {children}
        </button>
    );
}