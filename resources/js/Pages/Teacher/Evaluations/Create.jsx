import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Button from '@/Components/UI/Button';
import EmptyState from '@/Components/UI/EmptyState';
import Alert from '@/Components/UI/Alert';

import {
    ArrowRight,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    GraduationCap,
    UserRound,
} from 'lucide-react';

export default function CreateEvaluation({
    assignments = [],
    types = [],
    isSecretary = false,
}) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        class_id: '',
        subject_id: '',
        title: '',
        type: 'devoir',
        evaluation_date: new Date().toISOString().slice(0, 10),
        max_score: 20,
        coefficient: 1,
        teacher_id: '',
    });

    /**
     * Affectation actuellement sélectionnée.
     */
    const selectedAssignment = assignments.find(
        (assignment) =>
            String(assignment.class_id) === String(data.class_id) &&
            String(assignment.subject_id) === String(data.subject_id) &&
            (
                !isSecretary ||
                String(assignment.teacher_id || '') ===
                    String(data.teacher_id || '')
            )
    );

    /**
     * Une seule valeur simple pour le select.
     *
     * On utilise les IDs de l'affectation plutôt que de reconstruire
     * une valeur différente selon le rôle.
     */
    const selectedValue =
        selectedAssignment
            ? `${selectedAssignment.class_id}|${selectedAssignment.subject_id}|${selectedAssignment.teacher_id || ''}`
            : '';

    function handleAssignmentChange(event) {
        const value = event.target.value;

        if (!value) {
            setData((current) => ({
                ...current,
                class_id: '',
                subject_id: '',
                teacher_id: '',
            }));

            return;
        }

        const [class_id, subject_id, teacher_id = ''] =
            value.split('|');

        setData((current) => ({
            ...current,
            class_id,
            subject_id,
            teacher_id,
        }));
    }

    function submit(event) {
        event.preventDefault();

        post(route('teacher.evaluations.store'));
    }

    if (assignments.length === 0) {
        return (
            <AuthenticatedLayout title="Nouvelle évaluation">
                <Head title="Nouvelle évaluation" />

                <div className="mx-auto max-w-3xl">
                    <Card className="overflow-hidden">
                        <div className="bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white sm:p-8">
                            <div className="flex items-center gap-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15">
                                    <ClipboardList className="h-6 w-6" />
                                </div>

                                <div>
                                    <h1 className="text-xl font-bold sm:text-2xl">
                                        Nouvelle évaluation
                                    </h1>

                                    <p className="mt-1 text-sm text-emerald-50">
                                        Préparez une nouvelle évaluation.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Card.Body>
                            <EmptyState
                                icon={BookOpen}
                                title="Aucune affectation disponible"
                                description="Contactez l'administration pour être affecté à une classe et une matière avant de créer une évaluation."
                            />
                        </Card.Body>
                    </Card>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout title="Nouvelle évaluation">
            <Head title="Nouvelle évaluation" />

            <div className="mx-auto max-w-4xl space-y-6">
                {/* Hero */}
                <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg sm:p-8">
                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10 blur-2xl" />
                    <div className="absolute -bottom-24 left-1/3 h-56 w-56 rounded-full bg-cyan-300/10 blur-3xl" />

                    <div className="relative flex items-start gap-4">
                        <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                            <ClipboardList className="h-6 w-6" />
                        </div>

                        <div>
                            <div className="flex items-center gap-2">
                                <span className="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">
                                    Évaluation
                                </span>
                            </div>

                            <h1 className="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">
                                Nouvelle évaluation
                            </h1>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-emerald-50 sm:text-base">
                                Configurez l'évaluation puis passez
                                directement à la saisie des notes.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Selection preview */}
                {selectedAssignment && (
                    <div className="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 p-4 sm:p-5">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                                    <CheckCircle2 className="h-5 w-5" />
                                </div>

                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                        Affectation sélectionnée
                                    </p>

                                    <div className="mt-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
                                        <span>
                                            {selectedAssignment.class_name}
                                        </span>

                                        <ArrowRight className="h-4 w-4 text-emerald-500" />

                                        <span>
                                            {selectedAssignment.subject_name}
                                        </span>
                                    </div>

                                    {isSecretary &&
                                        selectedAssignment.teacher_name && (
                                            <div className="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                                <UserRound className="h-3.5 w-3.5" />
                                                Enseignant :{' '}
                                                {selectedAssignment.teacher_name}
                                            </div>
                                        )}
                                </div>
                            </div>

                            <span className="inline-flex w-fit items-center rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm">
                                Prêt à configurer
                            </span>
                        </div>
                    </div>
                )}

                <Card className="overflow-hidden">
                    <Card.Header
                        title="Configuration de l'évaluation"
                        description="Les élèves correspondant à la classe sélectionnée seront automatiquement chargés."
                    />

                    <Card.Body>
                        {(errors.subject_id || errors.class_id) && (
                            <Alert type="warning" className="mb-5">
                                {errors.subject_id ||
                                    errors.class_id}
                            </Alert>
                        )}

                        {errors.teacher_id && (
                            <Alert type="warning" className="mb-5">
                                {errors.teacher_id}
                            </Alert>
                        )}

                        <form
                            onSubmit={submit}
                            className="space-y-6"
                        >
                            {/* Classe / matière */}
                            <div className="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50/70 to-indigo-50/50 p-4 sm:p-5">
                                <div className="mb-4 flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                        <GraduationCap className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">
                                            Classe et matière
                                        </p>

                                        <p className="text-xs text-slate-500">
                                            Sélectionnez l'affectation concernée.
                                        </p>
                                    </div>
                                </div>

                                <Field
                                    label="Affectation"
                                    required
                                    error={
                                        errors.class_id ||
                                        errors.subject_id ||
                                        errors.teacher_id
                                    }
                                >
                                    <Select
                                        value={selectedValue}
                                        onChange={
                                            handleAssignmentChange
                                        }
                                    >
                                        <option value="">
                                            Choisir une classe et une matière…
                                        </option>

                                        {assignments.map(
                                            (assignment, index) => (
                                                <option
                                                    key={`${assignment.class_id}-${assignment.subject_id}-${assignment.teacher_id || index}`}
                                                    value={`${assignment.class_id}|${assignment.subject_id}|${assignment.teacher_id || ''}`}
                                                >
                                                    {assignment.class_name}
                                                    {' — '}
                                                    {assignment.subject_name}

                                                    {isSecretary &&
                                                        assignment.teacher_name
                                                        ? ` — ${assignment.teacher_name}`
                                                        : ''}
                                                </option>
                                            )
                                        )}
                                    </Select>
                                </Field>
                            </div>

                            {/* Secretary information */}
                            {isSecretary && (
                                <div className="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-4">
                                    <div className="flex items-start gap-3">
                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                            <UserRound className="h-5 w-5" />
                                        </div>

                                        <div>
                                            <p className="text-sm font-semibold text-amber-900">
                                                Saisie pour le compte d'un enseignant
                                            </p>

                                            <p className="mt-1 text-sm leading-6 text-amber-800">
                                                L'enseignant sélectionné restera
                                                associé à l'évaluation. Votre
                                                identité sera conservée comme
                                                personne ayant effectué la saisie
                                                et la soumission.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* General information */}
                            <div>
                                <div className="mb-4 flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                                        <ClipboardList className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">
                                            Informations générales
                                        </p>

                                        <p className="text-xs text-slate-500">
                                            Définissez les caractéristiques de l'évaluation.
                                        </p>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <Field
                                        label="Titre"
                                        required
                                        error={errors.title}
                                        hint="Ex : Devoir 1, Interrogation 2, Composition du 1er trimestre…"
                                    >
                                        <Input
                                            value={data.title}
                                            onChange={(event) =>
                                                setData(
                                                    'title',
                                                    event.target.value
                                                )
                                            }
                                            placeholder="Ex : Devoir 1"
                                        />
                                    </Field>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Type"
                                            required
                                            error={errors.type}
                                        >
                                            <Select
                                                value={data.type}
                                                onChange={(event) =>
                                                    setData(
                                                        'type',
                                                        event.target.value
                                                    )
                                                }
                                            >
                                                {types.map((type) => (
                                                    <option
                                                        key={type.value}
                                                        value={type.value}
                                                    >
                                                        {type.label}
                                                    </option>
                                                ))}
                                            </Select>
                                        </Field>

                                        <Field
                                            label="Date"
                                            required
                                            error={
                                                errors.evaluation_date
                                            }
                                        >
                                            <div className="relative">
                                                <Input
                                                    type="date"
                                                    value={
                                                        data.evaluation_date
                                                    }
                                                    onChange={(event) =>
                                                        setData(
                                                            'evaluation_date',
                                                            event.target.value
                                                        )
                                                    }
                                                    className="pr-10"
                                                />

                                                <CalendarDays className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                            </div>
                                        </Field>
                                    </div>
                                </div>
                            </div>

                            {/* Scoring */}
                            <div className="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50/60 to-fuchsia-50/40 p-4 sm:p-5">
                                <div className="mb-4 flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                                        <BookOpen className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">
                                            Barème et coefficient
                                        </p>

                                        <p className="text-xs text-slate-500">
                                            Définissez les paramètres de calcul des notes.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field
                                        label="Barème"
                                        required
                                        error={errors.max_score}
                                        hint="Ex : 20"
                                    >
                                        <Input
                                            type="number"
                                            step="0.5"
                                            min="1"
                                            value={data.max_score}
                                            onChange={(event) =>
                                                setData(
                                                    'max_score',
                                                    event.target.value
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Coefficient"
                                        required
                                        error={errors.coefficient}
                                        hint="Ex : 1, 2 ou 0.5"
                                    >
                                        <Input
                                            type="number"
                                            step="0.5"
                                            min="0.5"
                                            value={data.coefficient}
                                            onChange={(event) =>
                                                setData(
                                                    'coefficient',
                                                    event.target.value
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                <div className="text-xs leading-5 text-slate-500">
                                    {selectedAssignment ? (
                                        <span className="flex items-center gap-1.5 text-emerald-600">
                                            <CheckCircle2 className="h-4 w-4" />
                                            Classe et matière sélectionnées
                                        </span>
                                    ) : (
                                        'Sélectionnez une classe et une matière pour continuer.'
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    className="w-full sm:w-auto"
                                    loading={processing}
                                    disabled={!selectedAssignment}
                                >
                                    <ClipboardList className="h-4 w-4" />
                                    Créer et saisir les notes
                                </Button>
                            </div>
                        </form>
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}