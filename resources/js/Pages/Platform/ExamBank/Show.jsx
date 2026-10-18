import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    CheckCircle2,
    Clock,
    Download,
    Eye,
    FileText,
    Lock,
    Pencil,
    Trash2,
    UploadCloud,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import EmptyState from '@/Components/UI/EmptyState';
import { difficultyTones, formatBytes, statusTones } from '@/Utils/examBank';

export default function ExamResourceShow({ resource, statuses }) {
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);

    const changeStatus = (status) => {
        router.patch(route('platform.exam-bank.status', resource.id), { status }, { preserveScroll: true });
    };

    const confirmDelete = () => {
        setProcessing(true);
        router.delete(route('platform.exam-bank.destroy', resource.id), {
            onFinish: () => setProcessing(false),
        });
    };

    const documents = resource.attachments.filter((a) => !a.is_answer_key);
    const answerKeys = resource.attachments.filter((a) => a.is_answer_key);

    const meta = [
        { label: 'Niveau', value: resource.level ?? '—' },
        { label: 'Matière', value: resource.subject ?? '—' },
        { label: 'Type', value: resource.type_label },
        { label: 'Durée', value: resource.duration_minutes ? `${resource.duration_minutes} min` : '—' },
        { label: 'Année', value: resource.academic_year ?? '—' },
        { label: 'Période', value: resource.period ?? '—' },
    ];

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title={resource.title} />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <Link href={route('platform.exam-bank.index')}>
                        <Button variant="ghost" size="sm" className="gap-1.5">
                            <ArrowLeft className="h-4 w-4" /> Retour
                        </Button>
                    </Link>
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-bold text-slate-900">{resource.title}</h1>
                            <Badge tone={statusTones[resource.status] ?? 'slate'}>{resource.status_label}</Badge>
                            <Badge tone={difficultyTones[resource.difficulty] ?? 'slate'}>{resource.difficulty_label}</Badge>
                        </div>
                        <p className="mt-1 text-sm text-slate-500">
                            {resource.is_platform_resource ? 'Ressource plateforme' : 'Ressource établissement'} · {resource.type_label}
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    {resource.status !== 'published' && (
                        <Button variant="primary" size="sm" className="gap-1.5" onClick={() => changeStatus('published')}>
                            <CheckCircle2 className="h-4 w-4" /> Publier
                        </Button>
                    )}
                    {resource.status !== 'draft' && (
                        <Button variant="secondary" size="sm" onClick={() => changeStatus('draft')}>
                            Repasser en brouillon
                        </Button>
                    )}
                    {resource.status !== 'archived' && (
                        <Button variant="secondary" size="sm" onClick={() => changeStatus('archived')}>
                            Archiver
                        </Button>
                    )}
                    <Link href={route('platform.exam-bank.edit', resource.id)}>
                        <Button variant="secondary" size="sm" className="gap-1.5">
                            <Pencil className="h-4 w-4" /> Modifier
                        </Button>
                    </Link>
                    <Button variant="danger" size="sm" className="gap-1.5" onClick={() => setConfirming(true)}>
                        <Trash2 className="h-4 w-4" /> Supprimer
                    </Button>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <Card.Header title="Présentation" />
                        <Card.Body className="space-y-4">
                            <p className="whitespace-pre-line text-sm text-slate-600">
                                {resource.description || 'Aucune description.'}
                            </p>

                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                {meta.map((item) => (
                                    <div key={item.label} className="rounded-lg bg-slate-50 p-3">
                                        <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{item.label}</p>
                                        <p className="mt-1 text-sm font-semibold text-slate-800">{item.value}</p>
                                    </div>
                                ))}
                            </div>
                        </Card.Body>
                    </Card>

                    {resource.content && (
                        <Card>
                            <Card.Header title="Contenu" />
                            <Card.Body>
                                <div className="whitespace-pre-line rounded-lg border border-slate-100 bg-slate-50/60 p-4 text-sm text-slate-700">
                                    {resource.content}
                                </div>
                            </Card.Body>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <Card.Header title="Fichiers joints" />
                        <Card.Body className="space-y-3">
                            {documents.length === 0 ? (
                                <EmptyState icon={UploadCloud} title="Aucun document" description="Ajoutez un fichier depuis la modification." />
                            ) : (
                                documents.map((attachment) => (
                                    <div key={attachment.id} className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                                        <span className="flex min-w-0 items-center gap-2">
                                            <FileText className="h-4 w-4 shrink-0 text-slate-400" />
                                            <span className="min-w-0">
                                                <span className="block truncate text-sm font-medium text-slate-700">{attachment.original_name}</span>
                                                <span className="text-xs text-slate-400">{formatBytes(attachment.size)}</span>
                                            </span>
                                        </span>
                                        <a href={route('platform.exam-bank.attachments.download', [resource.id, attachment.id])}>
                                            <Button variant="ghost" size="sm" aria-label="Télécharger">
                                                <Download className="h-4 w-4" />
                                            </Button>
                                        </a>
                                    </div>
                                ))
                            )}

                            {answerKeys.length > 0 && (
                                <div className="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3">
                                    <p className="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-700">
                                        <Lock className="h-3.5 w-3.5" /> Corrigé protégé
                                    </p>
                                    {answerKeys.map((attachment) => (
                                        <div key={attachment.id} className="flex items-center justify-between gap-3">
                                            <span className="min-w-0 truncate text-sm text-emerald-800">{attachment.original_name}</span>
                                            <a href={route('platform.exam-bank.attachments.download', [resource.id, attachment.id])}>
                                                <Button variant="ghost" size="sm" aria-label="Télécharger">
                                                    <Download className="h-4 w-4" />
                                                </Button>
                                            </a>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header title="Statistiques" />
                        <Card.Body className="grid grid-cols-2 gap-3">
                            <div className="rounded-lg bg-slate-50 p-3">
                                <p className="flex items-center gap-1.5 text-xs text-slate-500"><Eye className="h-3.5 w-3.5" /> Consultations</p>
                                <p className="mt-1 text-lg font-bold text-slate-800">{resource.views_count}</p>
                            </div>
                            <div className="rounded-lg bg-slate-50 p-3">
                                <p className="flex items-center gap-1.5 text-xs text-slate-500"><Download className="h-3.5 w-3.5" /> Téléchargements</p>
                                <p className="mt-1 text-lg font-bold text-slate-800">{resource.downloads_count}</p>
                            </div>
                            <div className="col-span-2 rounded-lg bg-slate-50 p-3">
                                <p className="flex items-center gap-1.5 text-xs text-slate-500"><Calendar className="h-3.5 w-3.5" /> Créée le</p>
                                <p className="mt-1 text-sm font-semibold text-slate-800">{resource.created_at}</p>
                            </div>
                        </Card.Body>
                    </Card>

                    <div className="flex items-center gap-2 text-xs text-slate-400">
                        <Clock className="h-3.5 w-3.5" />
                        {resource.published_at ? `Publiée le ${resource.published_at}` : 'Jamais publiée'}
                    </div>
                </div>
            </div>

            <ConfirmDialog
                show={confirming}
                onClose={() => setConfirming(false)}
                onConfirm={confirmDelete}
                title={`Supprimer « ${resource.title} » ?`}
                description="La ressource et ses fichiers seront définitivement supprimés."
                confirmLabel="Supprimer"
                variant="danger"
                loading={processing}
            />
        </AuthenticatedLayout>
    );
}
