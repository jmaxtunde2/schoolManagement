import { Head, Link } from '@inertiajs/react';
import { Inbox, Mail, MailOpen, Phone, UserRound } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Card from '@/Components/UI/Card';
import Pagination from '@/Components/UI/Pagination';

export default function Index({ messages, unread = 0 }) {
    const items = messages?.data ?? [];

    return (
        <AuthenticatedLayout title="Messages parents">
            <Head title="Messages parents" />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-950">Messages parents</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Demandes adressées à l'établissement par les parents.
                        </p>
                    </div>
                    {unread > 0 && (
                        <Badge tone="amber">{unread} non lu{unread > 1 ? 's' : ''}</Badge>
                    )}
                </div>

                {items.length === 0 ? (
                    <Card className="p-12 text-center">
                        <Inbox className="mx-auto h-10 w-10 text-slate-300" />
                        <p className="mt-4 font-semibold text-slate-800">Aucun message</p>
                        <p className="mt-1 text-sm text-slate-500">
                            Les messages envoyés par les parents apparaîtront ici.
                        </p>
                    </Card>
                ) : (
                    <Card className="divide-y divide-slate-100">
                        {items.map((message) => (
                            <Link
                                key={message.id}
                                href={route('messages.show', message.id)}
                                className="flex items-start gap-4 p-4 transition hover:bg-slate-50"
                            >
                                <span
                                    className={`mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                                        message.is_read ? 'bg-slate-100 text-slate-400' : 'bg-emerald-100 text-emerald-600'
                                    }`}
                                >
                                    {message.is_read ? <MailOpen className="h-5 w-5" /> : <Mail className="h-5 w-5" />}
                                </span>

                                <div className="min-w-0 flex-1">
                                    <div className="flex items-start justify-between gap-3">
                                        <p className={`truncate ${message.is_read ? 'font-medium text-slate-700' : 'font-bold text-slate-900'}`}>
                                            {message.subject}
                                        </p>
                                        {!message.is_read && (
                                            <span className="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500" />
                                        )}
                                    </div>
                                    <p className="mt-1 truncate text-sm text-slate-500">{message.excerpt}</p>
                                    <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                        <span className="inline-flex items-center gap-1">
                                            <UserRound className="h-3.5 w-3.5" /> {message.parent || 'Parent'}
                                        </span>
                                        {message.student && (
                                            <span className="inline-flex items-center gap-1">
                                                <Inbox className="h-3.5 w-3.5" /> {message.student}
                                            </span>
                                        )}
                                        {message.phone && (
                                            <span className="inline-flex items-center gap-1">
                                                <Phone className="h-3.5 w-3.5" /> {message.phone}
                                            </span>
                                        )}
                                        <span className="ml-auto">{message.created_at}</span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                        <div className="px-4">
                            <Pagination meta={messages} />
                        </div>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
