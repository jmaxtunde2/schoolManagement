import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { DoorOpen, Pencil, Plus, Trash2, Users } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import EmptyState from '@/Components/UI/EmptyState';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Table from '@/Components/UI/Table';

const empty = {
    name: '',
    building: '',
    capacity: '',
};

export default function RoomsIndex({ rooms = [] }) {
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [toDelete, setToDelete] = useState(null);
    const [deleting, setDeleting] = useState(false);

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
    } = useForm(empty);

    function openCreate() {
        reset();
        setEditing(null);
        setShowForm(true);
    }

    function openEdit(row) {
        setData({
            name: row.name,
            building: row.building ?? '',
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
            onSuccess: closeForm,
        };

        if (editing) {
            put(route('admin.rooms.update', editing.id), options);
        } else {
            post(route('admin.rooms.store'), options);
        }
    }

    function confirmDelete() {
        if (!toDelete) {
            return;
        }

        setDeleting(true);

        router.delete(route('admin.rooms.destroy', toDelete.id), {
            onFinish: () => {
                setDeleting(false);
                setToDelete(null);
            },
        });
    }

    return (
        <AuthenticatedLayout title="Gestion des Salles">
            <Head title="Gestion des Salles" />

            <div className="space-y-6">
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-emerald-600 to-green-700 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-teal-100">
                                <DoorOpen className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Organisation pédagogique
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Gestion des Salles
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-teal-100 sm:text-base">
                                Référencez les salles de l'établissement pour
                                les affecter aux cours de l'emploi du temps.
                            </p>
                        </div>

                        <Button
                            onClick={openCreate}
                            className="shrink-0 bg-white text-white shadow-sm hover:bg-teal-50"
                        >
                            <Plus className="h-4 w-4" />
                            Nouvelle salle
                        </Button>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                <Card>
                    <Card.Header
                        title="Liste des salles"
                        actions={
                            <Button onClick={openCreate} size="sm">
                                <Plus className="h-4 w-4" />
                                Ajouter
                            </Button>
                        }
                    />

                    <Card.Body>
                        <Table
                            rows={rooms}
                            emptyState={
                                <EmptyState
                                    icon={DoorOpen}
                                    title="Aucune salle"
                                    description="Créez votre première salle, elle sera proposée lors de l'ajout d'un cours."
                                    action={
                                        <Button onClick={openCreate}>
                                            Créer une salle
                                        </Button>
                                    }
                                />
                            }
                            columns={[
                                {
                                    key: 'name',
                                    header: 'Salle',
                                    render: (room) => (
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-sm">
                                                <DoorOpen className="h-5 w-5" />
                                            </div>

                                            <div>
                                                <div className="font-semibold text-slate-800">
                                                    {room.name}
                                                </div>

                                                {room.building && (
                                                    <div className="text-xs text-slate-400">
                                                        Bâtiment :{' '}
                                                        {room.building}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ),
                                },
                                {
                                    key: 'capacity',
                                    header: 'Capacité',
                                    render: (room) =>
                                        room.capacity ? (
                                            <div className="flex items-center gap-2">
                                                <Users className="h-4 w-4 text-slate-400" />
                                                <span className="font-medium text-slate-700">
                                                    {room.capacity}
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
                                    key: 'timetables_count',
                                    header: 'Cours planifiés',
                                    render: (room) => (
                                        <Badge
                                            tone={
                                                Number(
                                                    room.timetables_count ?? 0
                                                ) > 0
                                                    ? 'green'
                                                    : 'gray'
                                            }
                                        >
                                            {room.timetables_count ?? 0}
                                        </Badge>
                                    ),
                                },
                                {
                                    key: 'actions',
                                    header: '',
                                    render: (room) => (
                                        <div className="flex justify-end gap-1">
                                            <button
                                                type="button"
                                                onClick={() => openEdit(room)}
                                                className="rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-600"
                                                aria-label="Modifier"
                                                title="Modifier"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setToDelete(room)
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

            <Modal
                show={showForm}
                onClose={closeForm}
                title={editing ? 'Modifier la salle' : 'Nouvelle salle'}
                footer={
                    <>
                        <Button variant="secondary" onClick={closeForm}>
                            Annuler
                        </Button>

                        <Button onClick={submit} loading={processing}>
                            {editing
                                ? 'Enregistrer les modifications'
                                : 'Créer la salle'}
                        </Button>
                    </>
                }
            >
                <form onSubmit={submit} className="space-y-5">
                    <Field
                        label="Nom de la salle"
                        required
                        error={errors.name}
                        hint="Ex : Salle 3, Laboratoire Physique"
                    >
                        <Input
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Ex : Salle 3"
                        />
                    </Field>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Field
                            label="Bâtiment"
                            error={errors.building}
                            hint="Ex : Bâtiment A"
                        >
                            <Input
                                value={data.building}
                                onChange={(e) =>
                                    setData('building', e.target.value)
                                }
                                placeholder="Facultatif"
                            />
                        </Field>

                        <Field
                            label="Capacité"
                            error={errors.capacity}
                            hint="Nombre de places"
                        >
                            <Input
                                type="number"
                                min="1"
                                value={data.capacity}
                                onChange={(e) =>
                                    setData('capacity', e.target.value)
                                }
                                placeholder="Ex : 40"
                            />
                        </Field>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={!!toDelete}
                onClose={() => setToDelete(null)}
                onConfirm={confirmDelete}
                loading={deleting}
                title="Supprimer la salle"
                description={
                    toDelete
                        ? `Supprimer « ${toDelete.name} » ? Les cours qui lui sont affectés ne seront pas supprimés mais n'auront plus de salle.`
                        : ''
                }
                variant="danger"
                confirmLabel="Supprimer"
            />
        </AuthenticatedLayout>
    );
}