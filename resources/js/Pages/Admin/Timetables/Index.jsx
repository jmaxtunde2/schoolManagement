import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    Clock3,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Modal from '@/Components/UI/Modal';
import Select from '@/Components/UI/Select';
import WeekGrid, {
    AcademicYearSelect,
    SlotCard,
} from '@/Components/Timetable/WeekGrid';

const emptySlot = {
    academic_year_id: '',
    class_id: '',
    subject_id: '',
    teacher_id: '',
    day_of_week: 1,
    starts_at: '08:00',
    ends_at: '09:00',
    room: '',
};

export default function Index({
    slots = [],
    days = [],
    academicYears = [],
    selectedAcademicYearId,
    classes = [],
    subjects = [],
    teachers = [],
    filters = {},
    canManage = false,
}) {
    const [creating, setCreating] = useState(false);
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [deletingProcessing, setDeletingProcessing] = useState(false);

    const form = useForm(emptySlot);

    const classId = filters.class_id ?? '';

    function applyFilters(next) {
        router.get(
            route('admin.timetables.index'),
            {
                academic_year_id:
                    next.academic_year_id ?? selectedAcademicYearId,
                class_id: next.class_id || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['slots', 'selectedAcademicYearId', 'filters'],
            }
        );
    }

        function openCreate() {
        form.clearErrors();
        form.setData({
            ...emptySlot,
            academic_year_id: selectedAcademicYearId ?? '',
        });
        setCreating(true);
    }

    function submitCreate(event) {
        event.preventDefault();

        form.post(route('admin.timetables.store'), {
            onSuccess: () => setCreating(false),
        });
    }

    function openEdit(slot) {
        form.clearErrors();
        form.setData({
            academic_year_id: slot.academic_year_id,
            class_id: slot.class_id ?? '',
            subject_id: slot.subject_id ?? '',
            teacher_id: slot.teacher_id ?? '',
            day_of_week: slot.day_of_week,
            starts_at: slot.starts_at,
            ends_at: slot.ends_at,
            room: slot.room ?? '',
        });
        setEditing(slot);
    }

    function submitEdit(event) {
        event.preventDefault();

        form.put(route('admin.timetables.update', editing.id), {
            onSuccess: () => setEditing(null),
        });
    }

    function destroy() {
        router.delete(route('admin.timetables.destroy', deleting.id), {
            onStart: () => setDeletingProcessing(true),
            onFinish: () => {
                setDeletingProcessing(false);
                setDeleting(null);
            },
        });
    }

    const slotForm = (
        <form
            id="timetable-form"
            onSubmit={creating ? submitCreate : submitEdit}
            className="grid gap-4 sm:grid-cols-2"
        >
            <Field
                label="Année scolaire"
                required
                error={form.errors.academic_year_id}
            >
                <Select
                    value={form.data.academic_year_id}
                    onChange={(e) =>
                        form.setData('academic_year_id', e.target.value)
                    }
                    required
                >
                    <option value="">Sélectionnez une année</option>

                    {academicYears.map((year) => (
                        <option key={year.id} value={year.id}>
                            {year.name}
                            {year.is_current ? ' (en cours)' : ''}
                        </option>
                    ))}
                </Select>
            </Field>

            <Field
                label="Classe"
                required
                error={form.errors.class_id}
            >
                <Select
                    value={form.data.class_id}
                    onChange={(e) =>
                        form.setData('class_id', e.target.value)
                    }
                    required
                >
                    <option value="">Sélectionnez une classe</option>

                    {classes.map((item) => (
                        <option key={item.id} value={item.id}>
                            {item.name}
                        </option>
                    ))}
                </Select>
            </Field>

            <Field
                label="Matière"
                required
                error={form.errors.subject_id}
            >
                <Select
                    value={form.data.subject_id}
                    onChange={(e) =>
                        form.setData('subject_id', e.target.value)
                    }
                    required
                >
                    <option value="">Sélectionnez une matière</option>

                    {subjects.map((item) => (
                        <option key={item.id} value={item.id}>
                            {item.name}
                        </option>
                    ))}
                </Select>
            </Field>

            <Field
                label="Enseignant"
                error={form.errors.teacher_id}
                hint="Facultatif. Un enseignant ne peut pas dispenser deux cours sur le même créneau."
            >
                <Select
                    value={form.data.teacher_id}
                    onChange={(e) =>
                        form.setData('teacher_id', e.target.value)
                    }
                >
                    <option value="">Non assigné</option>

                    {teachers.map((teacher) => (
                        <option key={teacher.id} value={teacher.id}>
                            {teacher.name}
                            {teacher.is_active ? '' : ' (inactif)'}
                        </option>
                    ))}
                </Select>
            </Field>

            <Field
                label="Jour"
                required
                error={form.errors.day_of_week}
            >
                <Select
                    value={form.data.day_of_week}
                    onChange={(e) =>
                        form.setData('day_of_week', e.target.value)
                    }
                    required
                >
                    {days.map((day) => (
                        <option key={day.value} value={day.value}>
                            {day.label}
                        </option>
                    ))}
                </Select>
            </Field>

            <div className="grid grid-cols-2 gap-4">
                <Field
                    label="Début"
                    required
                    error={form.errors.starts_at}
                >
                    <Input
                        type="time"
                        value={form.data.starts_at}
                        onChange={(e) =>
                            form.setData('starts_at', e.target.value)
                        }
                        required
                    />
                </Field>

                <Field
                    label="Fin"
                    required
                    error={form.errors.ends_at}
                >
                    <Input
                        type="time"
                        value={form.data.ends_at}
                        onChange={(e) =>
                            form.setData('ends_at', e.target.value)
                        }
                        required
                    />
                </Field>
            </div>

            <div className="sm:col-span-2">
                <Field label="Salle" error={form.errors.room}>
                    <Input
                        value={form.data.room}
                        onChange={(e) =>
                            form.setData('room', e.target.value)
                        }
                        placeholder="Bâtiment B, salle 12"
                    />
                </Field>
            </div>
        </form>
    );

    return (
        <AuthenticatedLayout title="Emploi du temps">
            <Head title="Emploi du temps" />

            <div className="space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-700 p-6 text-white shadow-lg">
                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2 text-indigo-100">
                                <CalendarDays className="h-5 w-5" />

                                <span className="text-sm font-medium">
                                    Administration & planification
                                </span>
                            </div>

                            <h1 className="text-2xl font-bold sm:text-3xl">
                                Emploi du temps
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-indigo-50/90 sm:text-base">
                                Planifiez les cours de la semaine : les
                                chevauchements sont refusés côté serveur pour
                                une classe, un enseignant ou une salle.
                            </p>
                        </div>

                        {canManage && (
                            <Button
                                onClick={openCreate}
                                className="shrink-0 hover:opacity-90"
                                style={{
                                    backgroundColor: '#ffffff',
                                    color: '#4338ca',
                                }}
                            >
                                <Plus className="h-4 w-4" />
                                Ajouter un créneau
                            </Button>
                        )}
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                {/* Filtres */}
                <Card>
                    <Card.Body>
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <AcademicYearSelect
                                    academicYears={academicYears}
                                    selectedId={selectedAcademicYearId}
                                    onSelect={(value) =>
                                        applyFilters({
                                            academic_year_id: value,
                                            class_id: classId,
                                        })
                                    }
                                />

                                <div>
                                    <label
                                        htmlFor="timetable-class"
                                        className="mb-1.5 block text-sm font-medium text-slate-700"
                                    >
                                        Classe
                                    </label>

                                    <Select
                                        id="timetable-class"
                                        value={classId}
                                        onChange={(e) =>
                                            applyFilters({
                                                academic_year_id:
                                                    selectedAcademicYearId,
                                                class_id: e.target.value,
                                            })
                                        }
                                    >
                                        <option value="">
                                            Toutes les classes
                                        </option>

                                        {classes.map((item) => (
                                            <option
                                                key={item.id}
                                                value={item.id}
                                            >
                                                {item.name}
                                            </option>
                                        ))}
                                    </Select>
                                </div>
                            </div>

                            <p className="text-sm text-slate-500">
                                <span className="font-semibold text-slate-700">
                                    {slots.length}
                                </span>{' '}
                                créneau{slots.length > 1 ? 'x' : ''} sur la
                                semaine
                            </p>
                        </div>
                    </Card.Body>
                </Card>

                {/* Grille */}
                <Card>
                    <Card.Header
                        title="Vue hebdomadaire"
                        description={
                            classId
                                ? `Filtrée sur ${
                                      classes.find(
                                          (item) =>
                                              String(item.id) ===
                                              String(classId)
                                      )?.name ?? 'la classe sélectionnée'
                                  }.`
                                : 'Lundi → samedi, pour l’année scolaire sélectionnée.'
                        }
                    />

                    <Card.Body>
                        <WeekGrid
                            slots={slots}
                            days={days}
                            emptyTitle="Aucun créneau"
                            emptyDescription="Ajoutez un premier cours pour commencer à construire la semaine."
                            renderSlot={
                                canManage
                                    ? (slot) => (
                                          <div className="group relative">
                                              <SlotCard slot={slot} />

                                              <div className="absolute right-1.5 top-1.5 flex gap-0.5 transition lg:opacity-0 lg:group-hover:opacity-100 lg:focus-within:opacity-100">
                                                  <button
                                                      type="button"
                                                      onClick={() =>
                                                          openEdit(slot)
                                                      }
                                                      className="rounded-md bg-white/90 p-1.5 text-slate-500 shadow-sm transition hover:text-indigo-600"
                                                      title="Modifier"
                                                      aria-label={`Modifier le cours de ${slot.subject_name}`}
                                                  >
                                                      <Pencil className="h-3.5 w-3.5" />
                                                  </button>

                                                  <button
                                                      type="button"
                                                      onClick={() =>
                                                          setDeleting(slot)
                                                      }
                                                      className="rounded-md bg-white/90 p-1.5 text-slate-500 shadow-sm transition hover:text-red-600"
                                                      title="Supprimer"
                                                      aria-label={`Supprimer le cours de ${slot.subject_name}`}
                                                  >
                                                      <Trash2 className="h-3.5 w-3.5" />
                                                  </button>
                                              </div>
                                          </div>
                                      )
                                    : undefined
                            }
                        />
                    </Card.Body>
                </Card>
            </div>

            {/* Création / modification */}
            <Modal
                show={creating || Boolean(editing)}
                onClose={() => {
                    setCreating(false);
                    setEditing(null);
                }}
                title={creating ? 'Ajouter un créneau' : 'Modifier le créneau'}
                size="lg"
                footer={
                    <>
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setCreating(false);
                                setEditing(null);
                            }}
                        >
                            Annuler
                        </Button>

                        <Button
                            type="submit"
                            form="timetable-form"
                            loading={form.processing}
                        >
                            <CheckCircle2 className="h-4 w-4" />
                            Enregistrer
                        </Button>
                    </>
                }
            >
                {slotForm}
            </Modal>

            {/* Suppression */}
            <ConfirmDialog
                show={Boolean(deleting)}
                onClose={() => setDeleting(null)}
                onConfirm={destroy}
                loading={deletingProcessing}
                variant="danger"
                title="Supprimer le créneau"
                confirmLabel="Supprimer"
                description="Ce cours sera retiré de l'emploi du temps de la semaine."
            >
                {deleting && (
                    <div className="mt-4 flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                        <Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />

                        <div className="min-w-0 text-sm">
                            <p className="font-semibold text-slate-800">
                                {deleting.subject_name ?? 'Matière'} —{' '}
                                {deleting.class_name ?? 'classe'}
                            </p>

                            <p className="text-xs text-slate-500">
                                {deleting.day_label} · {deleting.time_range}
                                {deleting.teacher_name
                                    ? ` · ${deleting.teacher_name}`
                                    : ''}
                            </p>
                        </div>
                    </div>
                )}
            </ConfirmDialog>
        </AuthenticatedLayout>
    );
}
