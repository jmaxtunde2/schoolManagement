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
    classes = [],
    types = [],
    isAdmin = false,
    academicYear = null,
    academicPeriod = null,
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

    const selectedClass = classes.find(
        (classRoom) => String(classRoom.id) === String(data.class_id)
    );
    const availableSubjects = selectedClass?.subjects ?? [];
    const selectedSubject = availableSubjects.find(
        (subject) => String(subject.id) === String(data.subject_id)
    );
    const assignedTeachers = assignments.filter(
        (assignment) =>
            String(assignment.class_id) === String(data.class_id) &&
            String(assignment.subject_id) === String(data.subject_id)
    );

    function handleClassChange(event) {
        setData((current) => ({
            ...current,
            class_id: event.target.value,
            subject_id: '',
            teacher_id: '',
        }));
    }

    function handleSubjectChange(event) {
        const subjectId = event.target.value;
        const teachers = assignments.filter(
            (assignment) =>
                String(assignment.class_id) === String(data.class_id) &&
                String(assignment.subject_id) === String(subjectId)
        );

        setData((current) => ({
            ...current,
            subject_id: subjectId,
            teacher_id: teachers.length === 1 ? String(teachers[0].teacher_id) : '',
        }));
    }

    function handleTeacherChange(event) {
        setData('teacher_id', event.target.value);
    }

    function submit(event) {
        event.preventDefault();

        post(isAdmin ? '/admin/evaluations' : route('censeur.evaluations.store'));
    }

    if (classes.length === 0 || !classes.some((classRoom) => classRoom.subjects?.length)) {
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
                                title="Aucune classe ou matière disponible"
                                description="Ajoutez des classes et leurs matières avant de créer une évaluation. L'administration pourra saisir les notes même sans enseignant affecté."
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
                                Choisissez une classe et une matière, puis saisissez les notes avec ou sans enseignant affecté.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Selection preview */}
                {selectedClass && selectedSubject && (
                    <div className="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 p-4 sm:p-5">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                                    <CheckCircle2 className="h-5 w-5" />
                                </div>

                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                        Saisie sélectionnée
                                    </p>

                                    <div className="mt-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
                                        <span>
                                            {selectedClass.name}
                                        </span>

                                        <ArrowRight className="h-4 w-4 text-emerald-500" />

                                        <span>
                                            {selectedSubject.name}
                                        </span>
                                    </div>

                                    {assignedTeachers.length > 0 && data.teacher_id && (
                                        <div className="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                            <UserRound className="h-3.5 w-3.5" />
                                            Enseignant :{' '}
                                            {assignedTeachers.find((teacher) => String(teacher.teacher_id) === String(data.teacher_id))?.teacher_name}
                                        </div>
                                    )}
                                    {assignedTeachers.length > 0 && !data.teacher_id && (
                                        <div className="mt-1 text-xs text-slate-500">
                                            L'enseignant affecté sera associé automatiquement.
                                        </div>
                                    )}
                                    {assignedTeachers.length === 0 && (
                                        <div className="mt-1 text-xs font-medium text-amber-700">
                                            Aucun enseignant affecté : votre saisie sera enregistrée sous votre identité.
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
                            {/* Classe / matière / enseignant */}
                            <div className="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50/70 to-indigo-50/50 p-4 sm:p-5">
                                <div className="mb-4 flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                        <GraduationCap className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">
                                            Classe, matière et enseignant
                                        </p>

                                <p className="text-xs text-slate-500">
                                            Choisissez une classe et une matière. Si un enseignant y est affecté, sélectionnez-le.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field label="Classe" required error={errors.class_id}>
                                        <Select value={data.class_id} onChange={handleClassChange}>
                                            <option value="">Choisir une classe…</option>
                                            {classes.map((classRoom) => <option key={classRoom.id} value={classRoom.id}>{classRoom.name}</option>)}
                                        </Select>
                                    </Field>
                                    <Field label="Matière" required error={errors.subject_id}>
                                        <Select value={data.subject_id} onChange={handleSubjectChange} disabled={!data.class_id}>
                                            <option value="">Choisir une matière…</option>
                                            {availableSubjects.map((subject) => <option key={subject.id} value={subject.id}>{subject.name}</option>)}
                                        </Select>
                                    </Field>
                                </div>
                                {selectedSubject && assignedTeachers.length > 0 && (
                                    <div className="mt-4">
                                        <Field label="Enseignant affecté (facultatif)" error={errors.teacher_id}>
                                            <Select value={data.teacher_id} onChange={handleTeacherChange}>
                                                <option value="">Associer automatiquement l'enseignant affecté</option>
                                                {assignedTeachers.map((teacher) => <option key={teacher.teacher_id} value={teacher.teacher_id}>{teacher.teacher_name}</option>)}
                                            </Select>
                                        </Field>
                                    </div>
                                )}
                            </div>

                            {/* Administration information */}
                            <div className="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 p-4">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                        <UserRound className="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p className="text-sm font-semibold text-amber-900">
                                            {assignedTeachers.length ? 'Enseignant affecté' : 'Aucun enseignant affecté'}
                                        </p>

                                        <p className="mt-1 text-sm leading-6 text-amber-800">
                                            {assignedTeachers.length
                                                ? "L'enseignant sélectionné restera associé à l'évaluation. Votre identité sera enregistrée comme personne ayant saisi les notes."
                                                : "Vous pouvez saisir les notes sans affectation. Votre identité sera enregistrée comme personne ayant effectué la saisie."}
                                        </p>
                                    </div>
                                </div>
                            </div>

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
                                                    max={
                                                        new Date()
                                                            .toISOString()
                                                            .slice(0, 10)
                                                    }
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

                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div className="flex items-center gap-2 text-slate-500">
                                            <GraduationCap className="h-4 w-4" />

                                            <span className="text-xs font-bold uppercase tracking-wide">
                                                Année académique et période
                                            </span>
                                        </div>

                                        <p className="mt-1 text-xs text-slate-500">
                                            Déterminées automatiquement à partir de l'année et de la période en cours.
                                        </p>

                                        <div className="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <Field label="Année académique">
                                                <Input
                                                    value={
                                                        academicYear?.name ??
                                                        'Non définie'
                                                    }
                                                    disabled
                                                    readOnly
                                                />
                                            </Field>

                                            <Field
                                                label="Période"
                                                error={
                                                    academicPeriod
                                                        ? undefined
                                                        : "Aucune période en cours n'est ouverte."
                                                }
                                            >
                                                <Input
                                                    value={
                                                        academicPeriod?.name ??
                                                        'Aucune période en cours'
                                                    }
                                                    disabled
                                                    readOnly
                                                />
                                            </Field>
                                        </div>
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
                                    {data.class_id && data.subject_id ? (
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
                                    disabled={!(data.class_id && data.subject_id)}
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
