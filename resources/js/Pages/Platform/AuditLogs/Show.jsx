import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Copy, FileText, User } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';

export default function AuditLogShow({ log }) {
    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
    };

    return (
        <AuthenticatedLayout title="Coriyase — Platform">
            <Head title={`Audit : ${log.action}`} />

            <div className="mb-6 flex items-center gap-4">
                <Link href={route('platform.audit-logs.index')} className="flex items-center gap-2 text-slate-500 hover:text-slate-700">
                    <ArrowLeft className="h-5 w-5" strokeWidth={2} />
                    Retour aux journaux
                </Link>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="lg:col-span-2 space-y-6">
                    <Card>
                        <Card.Header title="Détails de l'action" icon={FileText} />
                        <Card.Body>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <dt className="text-sm text-slate-500">Action</dt>
                                    <dd className="mt-1 font-mono text-lg text-slate-900">{log.action}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Date</dt>
                                    <dd className="mt-1 text-slate-900">{log.created_at}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Utilisateur</dt>
                                    <dd className="mt-1">
                                        {log.user ? (
                                            <div className="flex items-center gap-2">
                                                <User className="h-4 w-4 text-slate-400" strokeWidth={2} />
                                                <span className="font-medium text-slate-900">{log.user.name}</span>
                                                <span className="text-sm text-slate-500">({log.user.email})</span>
                                            </div>
                                        ) : (
                                            <span className="text-slate-400 italic">Système (automatisé)</span>
                                        )}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-slate-500">Entité concernée</dt>
                                    <dd className="mt-1 font-mono text-sm text-slate-900">{log.auditable_type} #{log.auditable_id}</dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-sm text-slate-500">ID de l'entrée</dt>
                                    <dd className="mt-1 flex items-center gap-2">
                                        <code className="font-mono text-sm bg-slate-100 px-2 py-1 rounded">{log.id}</code>
                                        <Button variant="ghost" size="sm" onClick={() => copyToClipboard(String(log.id))} className="gap-1 h-8">
                                            <Copy className="h-3 w-3" strokeWidth={2} />
                                            Copier
                                        </Button>
                                    </dd>
                                </div>
                            </dl>
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header title="Métadonnées" icon={FileText} />
                        <Card.Body>
                            {log.metadata && Object.keys(log.metadata).length > 0 ? (
                                <pre className="bg-slate-900 text-slate-100 p-4 rounded-lg overflow-x-auto text-sm">
                                    {JSON.stringify(log.metadata, null, 2)}
                                </pre>
                            ) : (
                                <p className="text-slate-500 italic">Aucune métadonnée</p>
                            )}
                        </Card.Body>
                    </Card>
                </div>

                <div className="space-y-4">
                    <Card>
                        <Card.Header title="Résumé" icon={FileText} />
                        <Card.Body className="space-y-3">
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">Action</p>
                                <p className="font-mono text-sm text-slate-900">{log.action}</p>
                            </div>
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">Entité</p>
                                <p className="font-mono text-sm text-slate-900">{log.auditable_type} #{log.auditable_id}</p>
                            </div>
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">Utilisateur</p>
                                <p className="font-medium text-slate-900">{log.user?.name || 'Système'}</p>
                            </div>
                            <div className="p-3 rounded-lg bg-slate-50">
                                <p className="text-xs text-slate-500">Date</p>
                                <p className="font-medium text-slate-900">{log.created_at}</p>
                            </div>
                        </Card.Body>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}