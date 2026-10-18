import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    Download,
    FileText,
    Lock,
    ShieldCheck,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Badge from '@/Components/UI/Badge';
import EmptyState from '@/Components/UI/EmptyState';
import { difficultyTones, formatBytes, fileLabel } from '@/Utils/examBank';

export default function ExamBankShow({ resource, canAnswerKey, routePrefix }) {
    const r = (name, params) => route(`${routePrefix}.exam-bank.${name}`, params);

    const meta = [
        { label: 'Niveau', value: resource.level ?? '—' },
        { label: 'Matière', value: resource.subject ?? '—' },
        { label: 'Type', value: resource.type_label },
        { label: 'Durée', value: resource.duration_minutes ? `${resource.duration_minutes} min` : '—' },
        { label: 'Année', value: resource.academic_year ?? '—' },
        { label: 'Période', value: resource.period ?? '—' },
    ];

    return (
        <AuthenticatedLayout title="Banque d'épreuves">
            <Head title={resource.title} />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-3">
                    <Link href={r('index')}>
                        <Button variant="ghost" size="sm" className="gap-1.5">
                            <ArrowLeft className="h-4 w-4" /> Banque
                        </Button>
                    </Link>
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-xl font-bold text-slate-900">{resource.title}</h1>
                            <Badge tone={difficultyTones[resource.difficulty] ?? 'slate'}>{resource.difficulty_label}</Badge>
                            {resource.is_free ? <Badge tone="blue">Gratuit</Badge> : <Badge tone="amber">Payant</Badge>}
                        </div>
                        <p className="mt-1 text-sm text-slate-500">{resource.type_label}</p>
                    </div>
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
                        <Card.Header title="Documents" />
                        <Card.Body className="space-y-3">
                            {resource.documents.length === 0 ? (
                                <EmptyState icon={FileText} title="Aucun fichier" description="Cette ressource ne propose pas de fichier téléchargeable." />
                            ) : (
                                resource.documents.map((document) => (
                                    <div key={document.id} className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                                        <span className="flex min-w-0 items-center gap-2">
                                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-[10px] font-bold text-indigo-600">
                                                {fileLabel(document.original_name)}
                                            </span>
                                            <span className="min-w-0">
                                                <span className="block truncate text-sm font-medium text-slate-700">{document.original_name}</span>
                                                <span className="text-xs text-slate-400">{formatBytes(document.size)}</span>
                                            </span>
                                        </span>
                                        {document.exists ? (
                                            <Button as="a" href={r('download', [resource.id, document.id])} size="sm" className="gap-1.5">
                                                <Download className="h-4 w-4" />
                                            </Button>
                                        ) : (
                                            <Badge tone="red">Indisponible</Badge>
                                        )}
                                    </div>
                                ))
                            )}
                        </Card.Body>
                    </Card>

                    {resource.answer_keys.length > 0 && (
                        <Card className="border-emerald-200">
                            <Card.Header title="Corrigé protégé" />
                            <Card.Body className="space-y-3">
                                <p className="flex items-center gap-2 text-xs text-emerald-700">
                                    <ShieldCheck className="h-4 w-4" /> Réservé au personnel autorisé.
                                </p>
                                {resource.answer_keys.map((attachment) => (
                                    <div key={attachment.id} className="flex items-center justify-between gap-3 rounded-lg bg-emerald-50/60 p-3">
                                        <span className="flex min-w-0 items-center gap-2 text-sm text-emerald-800">
                                            <Lock className="h-4 w-4 shrink-0" />
                                            <span className="truncate">{attachment.original_name}</span>
                                        </span>
                                        {attachment.exists ? (
                                            <Button as="a" href={r('answer-key', [resource.id, attachment.id])} variant="secondary" size="sm" className="gap-1.5">
                                                <Download className="h-4 w-4" />
                                            </Button>
                                        ) : (
                                            <Badge tone="red">Indisponible</Badge>
                                        )}
                                    </div>
                                ))}
                            </Card.Body>
                        </Card>
                    )}

                    {resource.has_answer_key && !canAnswerKey && (
                        <div className="flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">
                            <Lock className="h-4 w-4" /> Le corrigé de cette ressource est protégé.
                        </div>
                    )}

                    <div className="flex items-center gap-2 text-xs text-slate-400">
                        <Clock className="h-3.5 w-3.5" />
                        {resource.is_platform_resource ? 'Banque centrale CoriSchool' : 'Ressource de l\'établissement'}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
