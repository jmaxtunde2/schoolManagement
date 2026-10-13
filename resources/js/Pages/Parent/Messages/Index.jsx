import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Mail, MessageSquarePlus, Send } from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';

export default function Messages({ messages = [], students = [] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        student_id: '',
        subject: '',
        body: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('parent.messages.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    return (
        <AuthenticatedLayout title="Mes messages">
            <Head title="Mes messages" />

            <div className="mx-auto max-w-5xl space-y-6">
                <Link
                    href={route('parent.dashboard')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-teal-700"
                >
                    <ArrowLeft className="h-4 w-4" /> Retour à mon espace
                </Link>

                <div>
                    <p className="text-xs font-bold uppercase tracking-wider text-teal-700">Espace parent</p>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950">Messages</h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Écrivez à l'établissement : votre message est transmis à l'administration,
                        la censure et le secrétariat.
                    </p>
                </div>

                {/* Compose */}
                <Card>
                    <Card.Header
                        title="Nouveau message"
                        description="Réponse visible dans cet espace."
                    />
                    <Card.Body>
                        <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                            <label className="block">
                                <span className="mb-1 block text-sm font-medium text-slate-700">Concernant</span>
                                <Select
                                    value={data.student_id}
                                    onChange={(e) => setData('student_id', e.target.value)}
                                    error={errors.student_id}
                                >
                                    <option value="">Général (sans élève)</option>
                                    {students.map((student) => (
                                        <option key={student.id} value={student.id}>{student.name}</option>
                                    ))}
                                </Select>
                            </label>

                            <label className="block">
                                <span className="mb-1 block text-sm font-medium text-slate-700">Objet</span>
                                <input
                                    type="text"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="Ex. Demande d'attestation de scolarité"
                                    maxLength={150}
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 shadow-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30"
                                />
                                {errors.subject && <p className="mt-1 text-xs text-red-600">{errors.subject}</p>}
                            </label>

                            <label className="block sm:col-span-2">
                                <span className="mb-1 block text-sm font-medium text-slate-700">Message</span>
                                <Textarea
                                    value={data.body}
                                    rows={5}
                                    maxLength={2000}
                                    placeholder="Décrivez votre demande…"
                                    onChange={(e) => setData('body', e.target.value)}
                                    error={errors.body}
                                />
                            </label>

                            <div className="sm:col-span-2 sm:flex sm:justify-end">
                                <Button type="submit" disabled={processing}>
                                    <Send className="h-4 w-4" /> Envoyer le message
                                </Button>
                            </div>
                        </form>
                    </Card.Body>
                </Card>

                {/* History */}
                <div className="space-y-4">
                    <h2 className="text-sm font-bold uppercase tracking-wider text-slate-500">
                        Mes messages envoyés
                    </h2>

                    {messages.length === 0 ? (
                        <Card className="p-10 text-center">
                            <MessageSquarePlus className="mx-auto h-9 w-9 text-slate-300" />
                            <p className="mt-3 font-semibold text-slate-800">Aucun message</p>
                            <p className="mt-1 text-sm text-slate-500">
                                Utilisez le formulaire pour contacter l'établissement.
                            </p>
                        </Card>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            {messages.map((message) => (
                                <Card key={message.id}>
                                    <Card.Body className="space-y-2">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="font-bold text-slate-900">{message.subject}</p>
                                                <p className="mt-0.5 text-xs text-slate-500">
                                                    {message.created_at}
                                                    {message.student ? ` · ${message.student}` : ''}
                                                </p>
                                            </div>
                                            <Badge tone={message.is_read ? 'green' : 'amber'}>
                                                {message.is_read ? 'Lu' : 'Envoyé'}
                                            </Badge>
                                        </div>
                                        <p className="whitespace-pre-line text-sm text-slate-600">{message.body}</p>
                                    </Card.Body>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
