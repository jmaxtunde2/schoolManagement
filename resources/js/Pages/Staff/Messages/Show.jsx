import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarClock, GraduationCap, Mail, Phone, UserRound } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';

export default function Show({ message }) {
    return (
        <AuthenticatedLayout title="Message parent">
            <Head title={message.subject} />

            <div className="mx-auto max-w-3xl space-y-6">
                <Link
                    href={route('messages.index')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-teal-700"
                >
                    <ArrowLeft className="h-4 w-4" /> Retour aux messages
                </Link>

                <Card>
                    <Card.Header
                        title={message.subject}
                        description={message.created_at}
                        actions={
                            <Badge tone={message.is_read ? 'green' : 'amber'}>
                                {message.is_read ? 'Lu' : 'Non lu'}
                            </Badge>
                        }
                    />
                    <Card.Body className="space-y-5">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <InfoRow icon={UserRound} label="Parent" value={message.parent} />
                            <InfoRow icon={GraduationCap} label="Élève concerné" value={message.student || '—'} />
                            <InfoRow icon={Phone} label="Téléphone" value={message.phone || '—'} />
                            <InfoRow icon={Mail} label="E-mail" value={message.email || '—'} />
                        </div>

                        <div className="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                            <p className="whitespace-pre-line text-sm text-slate-700">{message.body}</p>
                        </div>

                        {message.read_at && (
                            <p className="flex items-center gap-1.5 text-xs text-slate-500">
                                <CalendarClock className="h-3.5 w-3.5" />
                                Lu le {message.read_at}
                                {message.read_by ? ` par ${message.read_by}` : ''}
                            </p>
                        )}
                    </Card.Body>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

function InfoRow({ icon: Icon, label, value }) {
    return (
        <div className="flex items-center gap-3 rounded-lg border border-slate-100 p-3">
            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                <Icon className="h-4 w-4" />
            </span>
            <div className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                <p className="truncate text-sm font-medium text-slate-800">{value}</p>
            </div>
        </div>
    );
}
