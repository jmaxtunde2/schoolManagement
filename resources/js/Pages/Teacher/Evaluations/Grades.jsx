import { useRef, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    BookOpen,
    CalendarDays,
    CheckCircle2,
    ClipboardCheck,
    FileText,
    GraduationCap,
    Hash,
    Lock,
    RotateCcw,
    Save,
    Users,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import Alert from '@/Components/UI/Alert';
import Textarea from '@/Components/UI/Textarea';

const statusTones = {
    draft: 'slate',
    in_progress: 'amber',
    validated: 'green',
    returned: 'red',
};

export default function Grades({ evaluation, results }) {
    const { data, setData, put, processing } = useForm({
        results: results.map((r) => ({
            student_id: r.student_id,
            score: r.score,
            is_absent: r.is_absent,
        })),
    });

    const [showValidate, setShowValidate] = useState(false);
    const [showReturn, setShowReturn] = useState(false);
    const [validating, setValidating] = useState(false);
    const [scoreErrors, setScoreErrors] = useState({});
    const {
        data: returnData,
        setData: setReturnData,
        post: postReturn,
        processing: returning,
        errors: returnErrors,
        reset: resetReturn,
    } = useForm({ reason: '' });

    const inputRefs = useRef([]);

    const total = data.results.length;

    const gradedCount = data.results.filter(
        (r) =>
            r.is_absent ||
            (r.score !== null && r.score !== '')
    ).length;

    const allGraded =
        total > 0 &&
        gradedCount === total;

    const hasScoreErrors =
        Object.keys(scoreErrors).length > 0;

    const canSubmit =
        allGraded &&
        !hasScoreErrors &&
        !processing &&
        !validating;

    const progress =
        total > 0
            ? Math.round((gradedCount / total) * 100)
            : 0;

    /**
     * Met à jour une ligne de résultat.
     */
    function updateRow(index, patch) {
        const next = [...data.results];

        next[index] = {
            ...next[index],
            ...patch,
        };

        setData('results', next);
    }

    /**
     * Valide et met à jour une note.
     *
     * Le contrôle est volontairement fait côté UI
     * pour empêcher immédiatement les valeurs hors barème.
     *
     * Le backend devra refaire exactement les mêmes
     * contrôles pour garantir la sécurité.
     */
    function updateScore(index, value) {
        const normalized = String(value ?? '')
            .replace(',', '.')
            .trim();

        const maxScore = Number(evaluation.max_score);

        // Champ vide
        if (normalized === '') {
            setScoreErrors((current) => {
                const next = { ...current };
                delete next[index];
                return next;
            });

            updateRow(index, {
                score: null,
            });

            return;
        }

        const score = Number(normalized);

        // On conserve la saisie dans le formulaire,
        // même si elle est invalide, afin que l'utilisateur
        // voie exactement ce qu'il a saisi.
        updateRow(index, {
            score: normalized,
            is_absent: false,
        });

        if (!Number.isFinite(score)) {
            setScoreErrors((current) => ({
                ...current,
                [index]: 'Veuillez saisir une note valide.',
            }));

            return;
        }

        if (score < 0) {
            setScoreErrors((current) => ({
                ...current,
                [index]: 'La note ne peut pas être négative.',
            }));

            return;
        }

        if (score > maxScore) {
            setScoreErrors((current) => ({
                ...current,
                [index]: `La note ne peut pas dépasser ${maxScore}.`,
            }));

            return;
        }

        setScoreErrors((current) => {
            const next = { ...current };
            delete next[index];
            return next;
        });
    }

    /**
     * Navigation rapide entre les champs.
     */
    function handleKeyDown(e, index) {
        if (e.key === 'Enter') {
            e.preventDefault();

            inputRefs.current[index + 1]?.focus();
        }
    }

    /**
     * Enregistre les notes comme brouillon.
     */
    function saveDraft() {
        if (hasScoreErrors) {
            return;
        }

        put(
            route(
                'teacher.evaluations.grades.update',
                evaluation.id
            ),
            {
                preserveScroll: true,
            }
        );
    }

    /**
     * Sauvegarde les notes puis soumet l'évaluation
     * pour validation.
     */
    function submitForValidation() {
        if (!canSubmit) {
            return;
        }

        setValidating(true);

        // Étape 1 :
        // enregistrer les notes.
        put(
            route(
                'teacher.evaluations.grades.update',
                evaluation.id
            ),
            {
                preserveScroll: true,

                onSuccess: () => {
                    // Étape 2 :
                    // soumettre l'évaluation.
                    router.post(
                        route(
                            'teacher.evaluations.submit',
                            evaluation.id
                        ),
                        {},
                        {
                            preserveScroll: true,

                            onSuccess: () => {
                                setShowValidate(false);
                            },

                            onError: () => {
                                setValidating(false);
                            },

                            onFinish: () => {
                                setValidating(false);
                            },
                        }
                    );
                },

                onError: () => {
                    setValidating(false);
                },
            }
        );
    }

    function submitReturn() {
        if (!evaluation.can_return || !returnData.reason.trim() || returning) {
            return;
        }

        postReturn(
            route(evaluation.return_route, evaluation.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowReturn(false);
                    resetReturn();
                },
            }
        );
    }

    /**
     * Une évaluation ne peut plus être modifiée
     * lorsqu'elle est en attente de validation
     * ou déjà validée.
     */
    const disabled =
        !evaluation.can_edit ||
        evaluation.is_validated ||
        evaluation.status === 'in_progress';

    return (
        <AuthenticatedLayout title={evaluation.title}>
            <Head title={evaluation.title} />

            {/* =========================================================
                HEADER
            ========================================================= */}

            <div className="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 text-white shadow-lg">
                <div className="relative p-5 sm:p-6">
                    <div className="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10" />

                    <div className="absolute -bottom-16 right-20 h-40 w-40 rounded-full bg-white/5" />

                    <div className="relative">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="min-w-0">
                                <div className="mb-2 flex items-center gap-2">
                                    <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20">
                                        <ClipboardCheck className="h-5 w-5" />
                                    </div>

                                    <span className="text-sm font-medium text-white/80">
                                        Saisie des résultats
                                    </span>
                                </div>

                                <h1 className="truncate text-xl font-bold sm:text-2xl">
                                    {evaluation.title}
                                </h1>

                                <p className="mt-1 text-sm text-white/75">
                                    {evaluation.class_name}
                                    {' · '}
                                    {evaluation.subject_name}
                                </p>
                            </div>

                            <div className="flex shrink-0 items-center gap-2">
                                <Badge
                                    tone={
                                        statusTones[
                                            evaluation.status
                                        ]
                                    }
                                >
                                    {evaluation.status_label}
                                </Badge>
                            </div>
                        </div>

                        {/* Progression */}
                        <div className="mt-6 max-w-xl">
                            <div className="mb-2 flex items-center justify-between text-xs">
                                <span className="font-medium text-white/80">
                                    Progression de la saisie
                                </span>

                                <span className="font-bold">
                                    {gradedCount} / {total}
                                </span>
                            </div>

                            <div className="h-2 overflow-hidden rounded-full bg-white/20">
                                <div
                                    className="h-full rounded-full bg-white transition-all duration-500"
                                    style={{
                                        width: `${progress}%`,
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* =========================================================
                INFORMATIONS ÉVALUATION
            ========================================================= */}

            <Card className="mb-5 overflow-hidden">
                {evaluation.status === 'returned' && evaluation.return_reason && (
                    <div className="border-b border-red-200 bg-red-50 p-4 sm:p-5">
                        <p className="text-sm font-semibold text-red-900">
                            Corrections demandées
                        </p>
                        <p className="mt-1 whitespace-pre-wrap text-sm text-red-800">
                            {evaluation.return_reason}
                        </p>
                    </div>
                )}
                <Card.Body className="p-0">
                    <div className="grid grid-cols-2 divide-x divide-y divide-slate-100 sm:grid-cols-4 sm:divide-y-0">
                        <Info
                            icon={GraduationCap}
                            iconClass="bg-blue-50 text-blue-600"
                            label="Classe"
                            value={evaluation.class_name}
                        />

                        <Info
                            icon={BookOpen}
                            iconClass="bg-violet-50 text-violet-600"
                            label="Matière"
                            value={evaluation.subject_name}
                        />

                        <Info
                            icon={FileText}
                            iconClass="bg-amber-50 text-amber-600"
                            label="Type"
                            value={evaluation.type}
                        />

                        <Info
                            icon={CalendarDays}
                            iconClass="bg-cyan-50 text-cyan-600"
                            label="Date"
                            value={evaluation.evaluation_date}
                        />
                    </div>

                    <div className="grid grid-cols-2 divide-x divide-slate-100 border-t border-slate-100 sm:grid-cols-4">
                        <Info
                            icon={Hash}
                            iconClass="bg-emerald-50 text-emerald-600"
                            label="Barème"
                            value={`/${evaluation.max_score}`}
                        />

                        <Info
                            icon={ClipboardCheck}
                            iconClass="bg-rose-50 text-rose-600"
                            label="Coefficient"
                            value={evaluation.coefficient}
                        />

                        <Info
                            icon={CheckCircle2}
                            iconClass="bg-green-50 text-green-600"
                            label="Notes saisies"
                            value={`${gradedCount} / ${total}`}
                        />

                        <Info
                            icon={Users}
                            iconClass="bg-indigo-50 text-indigo-600"
                            label="Élèves"
                            value={total}
                        />
                    </div>
                </Card.Body>
            </Card>

            {/* =========================================================
                VERROUILLAGE
            ========================================================= */}

            {disabled && (
                <Alert
                    type={
                        evaluation.can_validate &&
                        evaluation.status === 'in_progress'
                            ? 'warning'
                            : 'success'
                    }
                    className="mb-5"
                >
                    <span className="flex items-center gap-2">
                        <Lock className="h-4 w-4 shrink-0" />

                        <span>
                            {evaluation.can_validate &&
                            evaluation.status === 'in_progress'
                                ? 'Cette évaluation a été soumise par l’enseignant. Elle est maintenant en attente de votre validation.'
                                : evaluation.is_validated
                                ? 'Cette évaluation a été validée et ne peut plus être modifiée.'
                                : 'Cette évaluation est verrouillée car elle est en attente de validation.'}
                        </span>
                    </span>
                </Alert>
            )}

            {/* =========================================================
                SAISIE DES NOTES
            ========================================================= */}

            <Card className="overflow-hidden">
                <Card.Header
                    title="Saisie des notes"
                    description={`Barème : /${evaluation.max_score} · Utilisez Entrée ou Tab pour passer rapidement à l'élève suivant.`}
                />

                <Card.Body className="p-0">
                    {/* Header desktop */}
                    <div className="hidden border-y border-slate-100 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:grid sm:grid-cols-[1fr_120px_100px] sm:items-center">
                        <span>Élève</span>

                        <span className="text-center">
                            Présence
                        </span>

                        <span className="text-center">
                            Note / {evaluation.max_score}
                        </span>
                    </div>

                    <ul className="divide-y divide-slate-100">
                        {results.map((student, index) => {
                            const row = data.results[index];

                            const hasScore =
                                row.is_absent ||
                                (
                                    row.score !== null &&
                                    row.score !== ''
                                );

                            const hasError =
                                Boolean(scoreErrors[index]);

                            return (
                                <li
                                    key={student.student_id}
                                    className={`group px-4 py-3 transition-colors sm:px-5 ${
                                        hasError
                                            ? 'bg-rose-50/40'
                                            : hasScore
                                              ? 'bg-emerald-50/30 hover:bg-emerald-50/60'
                                              : 'hover:bg-slate-50'
                                    }`}
                                >
                                    <div className="flex items-start gap-3 sm:grid sm:grid-cols-[1fr_120px_120px] sm:items-center">
                                        {/* Élève */}
                                        <div className="flex min-w-0 items-center gap-3">
                                            <div
                                                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                                                    row.is_absent
                                                        ? 'bg-slate-100 text-slate-400'
                                                        : hasError
                                                          ? 'bg-rose-100 text-rose-600'
                                                          : hasScore
                                                            ? 'bg-emerald-100 text-emerald-700'
                                                            : 'bg-blue-50 text-blue-600'
                                                }`}
                                            >
                                                {index + 1}
                                            </div>

                                            <span
                                                className={`min-w-0 truncate text-sm font-medium ${
                                                    row.is_absent
                                                        ? 'text-slate-400'
                                                        : 'text-slate-800'
                                                }`}
                                            >
                                                {student.student_name}
                                            </span>
                                        </div>

                                        {/* Présence */}
                                        <label className="flex shrink-0 items-center justify-center gap-2 pt-2 text-xs text-slate-500 sm:pt-0">
                                            <input
                                                type="checkbox"
                                                disabled={disabled}
                                                checked={Boolean(
                                                    row.is_absent
                                                )}
                                                onChange={(e) => {
                                                    const isAbsent =
                                                        e.target.checked;

                                                    updateRow(index, {
                                                        is_absent:
                                                            isAbsent,
                                                        score: isAbsent
                                                            ? null
                                                            : row.score,
                                                    });

                                                    if (isAbsent) {
                                                        setScoreErrors(
                                                            (current) => {
                                                                const next = {
                                                                    ...current,
                                                                };

                                                                delete next[
                                                                    index
                                                                ];

                                                                return next;
                                                            }
                                                        );
                                                    }
                                                }}
                                                className="h-4 w-4 rounded border-slate-300"
                                                style={{
                                                    accentColor:
                                                        'var(--color-primary)',
                                                }}
                                            />

                                            <span className="hidden sm:inline">
                                                Absent
                                            </span>
                                        </label>

                                        {/* Note */}
                                        <div className="shrink-0">
                                            <input
                                                ref={(el) => {
                                                    inputRefs.current[index] =
                                                        el;
                                                }}
                                                type="number"
                                                inputMode="decimal"
                                                min="0"
                                                max={evaluation.max_score}
                                                step="0.5"
                                                disabled={
                                                    disabled ||
                                                    row.is_absent
                                                }
                                                value={
                                                    row.is_absent
                                                        ? ''
                                                        : (row.score ?? '')
                                                }
                                                onChange={(e) =>
                                                    updateScore(
                                                        index,
                                                        e.target.value
                                                    )
                                                }
                                                onKeyDown={(e) =>
                                                    handleKeyDown(
                                                        e,
                                                        index
                                                    )
                                                }
                                                placeholder="—"
                                                aria-invalid={hasError}
                                                aria-describedby={
                                                    hasError
                                                        ? `score-error-${index}`
                                                        : undefined
                                                }
                                                className={`h-11 w-20 rounded-xl border-2 px-2 text-center text-sm font-semibold outline-none transition-all ${
                                                    row.is_absent
                                                        ? 'border-slate-200 bg-slate-100 text-slate-400'
                                                        : hasError
                                                          ? 'border-rose-400 bg-rose-50 text-rose-700 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10'
                                                          : hasScore
                                                            ? 'border-emerald-200 bg-white text-emerald-700 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10'
                                                            : 'border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10'
                                                }`}
                                            />

                                            {hasError && (
                                                <p
                                                    id={`score-error-${index}`}
                                                    className="mt-1 max-w-28 text-center text-[10px] font-medium leading-tight text-rose-600"
                                                >
                                                    {scoreErrors[index]}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                </Card.Body>

                {/* =====================================================
                    ACTIONS ENSEIGNANT
                ===================================================== */}

                {evaluation.can_edit && !disabled && (
                    <div className="border-t border-slate-100 bg-slate-50/70 p-4 sm:flex sm:items-center sm:justify-between sm:p-5">
                        <div className="mb-3 flex items-center gap-2 text-sm sm:mb-0">
                            <div
                                className={`flex h-8 w-8 items-center justify-center rounded-lg ${
                                    hasScoreErrors
                                        ? 'bg-rose-100 text-rose-600'
                                        : 'bg-emerald-100 text-emerald-600'
                                }`}
                            >
                                <CheckCircle2 className="h-4 w-4" />
                            </div>

                            <span
                                className={
                                    hasScoreErrors
                                        ? 'font-medium text-rose-600'
                                        : 'text-slate-500'
                                }
                            >
                                {hasScoreErrors
                                    ? `${Object.keys(scoreErrors).length} note(s) invalide(s).`
                                    : allGraded
                                      ? 'Toutes les notes sont renseignées.'
                                      : `${total - gradedCount} note(s) restante(s).`}
                            </span>
                        </div>

                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Button
                                variant="secondary"
                                onClick={saveDraft}
                                loading={processing}
                                disabled={hasScoreErrors}
                            >
                                <Save className="h-4 w-4" />
                                Enregistrer le brouillon
                            </Button>

                            <Button
                                onClick={() =>
                                    setShowValidate(true)
                                }
                                disabled={!canSubmit}
                            >
                                <CheckCircle2 className="h-4 w-4" />
                                Soumettre pour validation
                            </Button>
                        </div>
                    </div>
                )}

                {/* =====================================================
                    VALIDATION ADMIN / CENSEUR
                ===================================================== */}

                {evaluation.can_validate &&
                    evaluation.status === 'in_progress' && (
                        <div className="border-t border-amber-200 bg-amber-50 p-4 sm:flex sm:items-center sm:justify-between sm:p-5">
                            <div className="mb-3 sm:mb-0">
                                <p className="text-sm font-semibold text-amber-900">
                                    Évaluation en attente de validation
                                </p>

                                <p className="mt-1 text-xs text-amber-700">
                                    Vérifiez les notes avant de
                                    procéder à la validation.
                                </p>
                            </div>

                            <div className="flex flex-col gap-2 sm:flex-row">
                                <Button
                                    onClick={() =>
                                        router.post(
                                            route(
                                                evaluation.validation_route,
                                                evaluation.id
                                            )
                                        )
                                    }
                                >
                                    <CheckCircle2 className="h-4 w-4" />
                                    Valider l'évaluation
                                </Button>

                                {evaluation.can_return && (
                                    <Button
                                        variant="danger"
                                        onClick={() => {
                                            resetReturn();
                                            setShowReturn(true);
                                        }}
                                    >
                                        <RotateCcw className="h-4 w-4" />
                                        Retourner pour correction
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
            </Card>

            {/* =========================================================
                CONFIRMATION SOUMISSION
            ========================================================= */}

            <ConfirmDialog
                show={showValidate}
                onClose={() => {
                    if (!validating) {
                        setShowValidate(false);
                    }
                }}
                onConfirm={submitForValidation}
                loading={validating}
                title="Soumettre cette évaluation ?"
                confirmLabel="Soumettre"
                variant="primary"
            >
                <div className="space-y-4 text-sm text-slate-600">
                    <div className="rounded-xl bg-emerald-50 p-4">
                        <p className="font-semibold text-emerald-900">
                            Vérification avant soumission
                        </p>

                        <p className="mt-1 text-emerald-700">
                            Toutes les notes doivent être
                            renseignées avant de continuer.
                        </p>
                    </div>

                    <ul className="space-y-2">
                        <li className="flex justify-between gap-4 border-b border-slate-100 pb-2">
                            <span>Classe</span>

                            <strong className="text-right text-slate-800">
                                {evaluation.class_name}
                            </strong>
                        </li>

                        <li className="flex justify-between gap-4 border-b border-slate-100 pb-2">
                            <span>Matière</span>

                            <strong className="text-right text-slate-800">
                                {evaluation.subject_name}
                            </strong>
                        </li>

                        <li className="flex justify-between gap-4 border-b border-slate-100 pb-2">
                            <span>Évaluation</span>

                            <strong className="text-right text-slate-800">
                                {evaluation.title}
                            </strong>
                        </li>

                        <li className="flex justify-between gap-4 border-b border-slate-100 pb-2">
                            <span>Barème</span>

                            <strong className="text-emerald-600">
                                /{evaluation.max_score}
                            </strong>
                        </li>

                        <li className="flex justify-between gap-4">
                            <span>Notes saisies</span>

                            <strong className="text-emerald-600">
                                {gradedCount} / {total}
                            </strong>
                        </li>
                    </ul>

                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p className="font-medium text-amber-900">
                            Après soumission
                        </p>

                        <p className="mt-1 text-xs leading-5 text-amber-800">
                            Les notes seront enregistrées puis
                            l'évaluation sera transmise à
                            l'administration pour validation.
                            Après soumission, la saisie sera
                            verrouillée. Les parents ne seront
                            notifiés qu'après validation.
                        </p>
                    </div>
                </div>
            </ConfirmDialog>

            <ConfirmDialog
                show={showReturn}
                onClose={() => {
                    if (!returning) {
                        setShowReturn(false);
                        resetReturn();
                    }
                }}
                onConfirm={submitReturn}
                loading={returning}
                title="Retourner l’évaluation"
                description={`Indiquez à l’enseignant les corrections attendues pour « ${evaluation.title} ».`}
                confirmLabel="Retourner pour correction"
                variant="danger"
            >
                <div className="mt-4">
                    <label
                        htmlFor="return-reason"
                        className="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Motif du retour
                    </label>
                    <Textarea
                        id="return-reason"
                        rows={4}
                        value={returnData.reason}
                        onChange={(event) =>
                            setReturnData('reason', event.target.value)
                        }
                        error={returnErrors.reason}
                        placeholder="Précisez les corrections à effectuer..."
                        maxLength={2000}
                    />
                    {returnErrors.reason && (
                        <p className="mt-1 text-sm text-red-600">
                            {returnErrors.reason}
                        </p>
                    )}
                </div>
            </ConfirmDialog>
        </AuthenticatedLayout>
    );
}

function Info({
    icon: Icon,
    iconClass,
    label,
    value,
}) {
    return (
        <div className="flex items-center gap-3 p-4 sm:p-5">
            <div
                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${iconClass}`}
            >
                <Icon
                    className="h-5 w-5"
                    strokeWidth={2}
                />
            </div>

            <div className="min-w-0">
                <p className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                    {label}
                </p>

                <p className="truncate text-sm font-semibold text-slate-800">
                    {value}
                </p>
            </div>
        </div>
    );
}