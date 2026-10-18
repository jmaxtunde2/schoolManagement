import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FileText, User } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import { auditActionLabel } from '@/Utils/auditLabels';

export default function AuditLogShow({ log }) {
    const entityName = log.auditable_type
        ? log.auditable_type.split('\\').pop()
        : null;

    return (
        <AuthenticatedLayout title="Détail de l'activité">
            <Head title={`Activité : ${log.action}`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Link
                    href={route('admin.audit-logs.index')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-700"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Retour au journal
                </Link>

                <Card>
                    <Card.Header
                        title={auditActionLabel(log.action)}
                        description={log.created_at}
                        actions={
                            <span className="rounded-full bg-slate-100 px-3 py-1 font-mono text-xs text-slate-600">
                                {log.action}
                            </span>
                        }
                    />

                    <Card.Body className="space-y-6">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm text-slate-500">
                                    Utilisateur
                                </dt>
                                <dd className="mt-1">
                                    {log.user ? (
                                        <div className="flex items-center gap-2">
                                            <User className="h-4 w-4 text-slate-400" />
                                            <span className="font-medium text-slate-900">
                                                {log.user.name}
                                            </span>
                                            <span className="text-sm text-slate-500">
                                                ({log.user.email})
                                            </span>
                                        </div>
                                    ) : (
                                        <span className="italic text-slate-400">
                                            Système (automatisé)
                                        </span>
                                    )}
                                </dd>
                            </div>

                            <div>
                                <dt className="text-sm text-slate-500">
                                    Entité concernée
                                </dt>
                                <dd className="mt-1 font-mono text-sm text-slate-900">
                                    {entityName
                                        ? `${entityName} #${log.auditable_id}`
                                        : '—'}
                                </dd>
                            </div>

                            {log.ip_address && (
                                <div>
                                    <dt className="text-sm text-slate-500">
                                        Adresse IP
                                    </dt>
                                    <dd className="mt-1 font-mono text-sm text-slate-900">
                                        {log.ip_address}
                                    </dd>
                                </div>
                            )}
                        </dl>

                        <div>
                            <p className="mb-2 text-sm font-medium text-slate-500">
                                Métadonnées
                            </p>

                            {log.metadata &&
                            Object.keys(log.metadata).length > 0 ? (
                                <pre className="overflow-x-auto rounded-lg bg-slate-900 p-4 text-sm text-slate-100">
                                    {JSON.stringify(log.metadata, null, 2)}
                                </pre>
                            ) : (
                                <p className="italic text-slate-400">
                                    Aucune métadonnée
                                </p>
                            )}
                        </div>

                        <p className="flex items-center gap-2 text-xs text-slate-400">
                            <FileText className="h-3.5 w-3.5" />
                            Les journaux d'activité sont immuables : ils ne
                            peuvent être ni modifiés ni supprimés.
                        </p>
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
