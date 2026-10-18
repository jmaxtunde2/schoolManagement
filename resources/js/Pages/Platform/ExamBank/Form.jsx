import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Download,
    FileText,
    Info,
    Lock,
    Save,
    Trash2,
    UploadCloud,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Checkbox from '@/Components/UI/Checkbox';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import Alert from '@/Components/UI/Alert';
import Badge from '@/Components/UI/Badge';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import { formatBytes } from '@/Utils/examBank';

function DocumentInput({ label, hint, accept, file, error, onChange, children }) {
    return (
        <Field label={label} hint={hint} error={error}>
            {children}
            <label className="mt-2 flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm font-medium text-slate-600 hover:border-indigo-400 hover:text-indigo-600">
                <UploadCloud className="h-4 w-4" />
                {file ? file.name : 'Choisir un fichier'}
                <input
                    type="file"
                    accept={accept}
                    className="hidden"
                    onChange={(event) => onChange(event.target.files?.[0] ?? null)}
                />
            </label>
        </Field>
    );
}

export default function ExamBankForm({ resource, levels, subjects, types, difficulties, statuses }) {
    const isEdit = Boolean(resource);
    const [confirmingAttachment, setConfirmingAttachment] = useState(null);

    const form = useForm({
        title: resource?.title ?? '',
        description: resource?.description ?? '',
        platform_level_id: resource?.platform_level_id ?? '',
        platform_subject_id: resource?.platform_subject_id ?? '',
        type: resource?.type ?? types[0]?.value ?? 'epreuve',
        difficulty: resource?.difficulty ?? 'medium',
        duration_minutes: resource?.duration_minutes ?? '',
        academic_year: resource?.academic_year ?? '',
        period: resource?.period ?? '',
        content: resource?.content ?? '',
        is_free: resource?.is_free ?? true,
        status: resource?.status ?? 'draft',
        document_file: null,
        answer_key_file: null,
    });

    const submit = (event) => {
        event.preventDefault();

        if (isEdit) {
            form.transform((data) => ({ ...data, _method: 'put' })).post(route('platform.exam-bank.update', resource.id));
        } else {
            form.post(route('platform.exam-bank.store'));
        }
    };

    const removeAttachment = () => {
        if (!confirmingAttachment) return;
        router.delete(route('platform.exam-bank.attachments.destroy', [resource.id, confirmingAttachment.id]), {
            onFinish: () => setConfirmingAttachment(null),
        });
    };

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title={isEdit ? 'Modifier une ressource' : 'Nouvelle ressource'} />

            <div className="mb-6 flex items-center gap-3">
                <Link href={route('platform.exam-bank.index')}>
                    <Button variant="ghost" size="sm" className="gap-1.5">
                        <ArrowLeft className="h-4 w-4" /> Retour
                    </Button>
                </Link>
                <div>
                    <h1 className="text-xl font-bold text-slate-900">
                        {isEdit ? 'Modifier la ressource' : 'Nouvelle ressource'}
                    </h1>
                    <p className="text-sm text-slate-500">
                        Ressource de la banque centrale CoriSchool.
                    </p>
                </div>
            </div>

            {Object.keys(form.errors).length > 0 && (
                <Alert type="warning" title="Veuillez corriger les champs signalés." className="mb-4" />
            )}

            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <Card.Header title="Informations générales" />
                        <Card.Body className="space-y-4">
                            <Field label="Titre" required error={form.errors.title}>
                                <Input
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    placeholder="Épreuve de mathématiques — équations"
                                    error={form.errors.title}
                                />
                            </Field>

                            <Field label="Description" error={form.errors.description}>
                                <Textarea
                                    rows={3}
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder="Objectifs, consignes générales..."
                                />
                            </Field>

                            <Field label="Contenu" hint="Énoncé ou notes internes (texte)." error={form.errors.content}>
                                <Textarea
                                    rows={6}
                                    value={form.data.content}
                                    onChange={(e) => form.setData('content', e.target.value)}
                                    placeholder="Saisissez le contenu de la ressource..."
                                />
                            </Field>
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header title="Fichiers" description="Le corrigé reste protégé et n'est jamais exposé aux élèves." />
                        <Card.Body className="space-y-6">
                            <DocumentInput
                                label="Document de l'épreuve"
                                hint="PDF, Word, image — 20 Mo max."
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                                file={form.data.document_file}
                                error={form.errors.document_file}
                                onChange={(file) => form.setData('document_file', file)}
                            >
                                {isEdit && resource.attachments.filter((a) => !a.is_answer_key).length > 0 && (
                                    <div className="space-y-2">
                                        {resource.attachments.filter((a) => !a.is_answer_key).map((attachment) => (
                                            <div key={attachment.id} className="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                                                <span className="flex items-center gap-2 truncate text-slate-700">
                                                    <FileText className="h-4 w-4 text-slate-400" />
                                                    <span className="truncate">{attachment.original_name}</span>
                                                    <span className="text-xs text-slate-400">{formatBytes(attachment.size)}</span>
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <a href={route('platform.exam-bank.attachments.download', [resource.id, attachment.id])}>
                                                        <Button variant="ghost" size="sm" aria-label="Télécharger">
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                    </a>
                                                    <Button variant="ghost" size="sm" className="text-red-600 hover:bg-red-50" onClick={() => setConfirmingAttachment(attachment)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </DocumentInput>

                            <DocumentInput
                                label="Corrigé (protégé)"
                                hint="Réservé au personnel autorisé. Jamais visible des élèves."
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                                file={form.data.answer_key_file}
                                error={form.errors.answer_key_file}
                                onChange={(file) => form.setData('answer_key_file', file)}
                            >
                                {isEdit && resource.attachments.filter((a) => a.is_answer_key).length > 0 && (
                                    <div className="space-y-2">
                                        {resource.attachments.filter((a) => a.is_answer_key).map((attachment) => (
                                            <div key={attachment.id} className="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50/60 px-3 py-2 text-sm">
                                                <span className="flex items-center gap-2 truncate text-emerald-800">
                                                    <Lock className="h-4 w-4" />
                                                    <span className="truncate">{attachment.original_name}</span>
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <a href={route('platform.exam-bank.attachments.download', [resource.id, attachment.id])}>
                                                        <Button variant="ghost" size="sm" aria-label="Télécharger">
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                    </a>
                                                    <Button variant="ghost" size="sm" className="text-red-600 hover:bg-red-50" onClick={() => setConfirmingAttachment(attachment)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </DocumentInput>
                        </Card.Body>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <Card.Header title="Classification" />
                        <Card.Body className="space-y-4">
                            <Field label="Niveau" error={form.errors.platform_level_id}>
                                <Select value={form.data.platform_level_id} onChange={(e) => form.setData('platform_level_id', e.target.value)}>
                                    <option value="">Non classé</option>
                                    {levels.map((level) => (
                                        <option key={level.id} value={level.id}>{level.name}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Field label="Matière" error={form.errors.platform_subject_id}>
                                <Select value={form.data.platform_subject_id} onChange={(e) => form.setData('platform_subject_id', e.target.value)}>
                                    <option value="">Non classée</option>
                                    {subjects.map((subject) => (
                                        <option key={subject.id} value={subject.id}>{subject.name}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Field label="Type" required error={form.errors.type}>
                                <Select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                                    {types.map((type) => (
                                        <option key={type.value} value={type.value}>{type.label}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Field label="Difficulté" required error={form.errors.difficulty}>
                                <Select value={form.data.difficulty} onChange={(e) => form.setData('difficulty', e.target.value)}>
                                    {difficulties.map((difficulty) => (
                                        <option key={difficulty.value} value={difficulty.value}>{difficulty.label}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Field label="Durée indicative (min)" error={form.errors.duration_minutes}>
                                <Input
                                    type="number"
                                    min="1"
                                    max="600"
                                    value={form.data.duration_minutes}
                                    onChange={(e) => form.setData('duration_minutes', e.target.value)}
                                />
                            </Field>

                            <Field label="Année scolaire" error={form.errors.academic_year}>
                                <Input
                                    value={form.data.academic_year}
                                    onChange={(e) => form.setData('academic_year', e.target.value)}
                                    placeholder="2025-2026"
                                />
                            </Field>

                            <Field label="Période" error={form.errors.period}>
                                <Input
                                    value={form.data.period}
                                    onChange={(e) => form.setData('period', e.target.value)}
                                    placeholder="Trimestre 1"
                                />
                            </Field>
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header title="Publication" />
                        <Card.Body className="space-y-4">
                            <Field label="Statut" required error={form.errors.status}>
                                <Select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                                    {statuses.map((status) => (
                                        <option key={status.value} value={status.value}>{status.label}</option>
                                    ))}
                                </Select>
                            </Field>

                            <Checkbox
                                checked={form.data.is_free}
                                onChange={(e) => form.setData('is_free', e.target.checked)}
                                label="Ressource gratuite"
                            />

                            <div className="flex items-start gap-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">
                                <Info className="mt-0.5 h-4 w-4 shrink-0" />
                                Une ressource en brouillon n'est pas visible des établissements.
                            </div>
                        </Card.Body>
                    </Card>

                    <div className="flex flex-col gap-2">
                        <Button type="submit" loading={form.processing} className="gap-2">
                            <Save className="h-4 w-4" />
                            {isEdit ? 'Enregistrer' : 'Créer la ressource'}
                        </Button>
                        <Link href={route('platform.exam-bank.index')}>
                            <Button variant="secondary" className="w-full">Annuler</Button>
                        </Link>
                    </div>
                </div>
            </form>

            <ConfirmDialog
                show={confirmingAttachment !== null}
                onClose={() => setConfirmingAttachment(null)}
                onConfirm={removeAttachment}
                title="Supprimer ce fichier ?"
                description="Le fichier sera supprimé du stockage."
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
