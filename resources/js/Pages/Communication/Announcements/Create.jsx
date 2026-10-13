import { useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    Megaphone,
    ArrowLeft,
    MessageCircle,
    Mail,
    Users,
    Search,
    CalendarClock,
    Paperclip,
    Send,
    Info,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Button from '@/Components/UI/Button';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import Checkbox from '@/Components/UI/Checkbox';
import { cn } from '@/Utils/cn';

const audienceOptions = [
    { value: 'all', label: 'Tous les parents' },
    { value: 'classes', label: 'Par classe' },
    { value: 'levels', label: 'Par niveau' },
    { value: 'guardians', label: 'Parents sélectionnés' },
];

export default function AnnouncementCreate({
    classes = [],
    levels = [],
    guardians = [],
    providerChannel = 'sms',
}) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        title: '',
        body: '',
        channels: ['whatsapp'],
        audience_type: 'all',
        class_ids: [],
        levels: [],
        guardian_ids: [],
        scheduled_at: '',
        attachment: null,
    });

    const [search, setSearch] = useState('');

    function toggleArray(key, value) {
        const current = data[key] ?? [];
        const next = current.includes(value)
            ? current.filter((item) => item !== value)
            : [...current, value];

        setData(key, next);
    }

    function submit(e) {
        e.preventDefault();

        post(route('communication.announcements.store'), {
            forceFormData: true,
        });
    }

    const filteredGuardians = useMemo(() => {
        const term = search.trim().toLowerCase();

        if (!term) return guardians;

        return guardians.filter(
            (g) =>
                g.name.toLowerCase().includes(term) ||
                (g.phone ?? '').toLowerCase().includes(term) ||
                (g.email ?? '').toLowerCase().includes(term)
        );
    }, [guardians, search]);

    const recipientsPreview = {
        all: 'Tous les parents actifs de l\'établissement.',
        classes: `${data.class_ids.length} classe(s) sélectionnée(s).`,
        levels: `${data.levels.length} niveau(x) sélectionné(s).`,
        guardians: `${data.guardian_ids.length} parent(s) sélectionné(s).`,
    }[data.audience_type];

    return (
        <AuthenticatedLayout title="Nouvelle annonce">
            <Head title="Nouvelle annonce" />

            <div className="mb-6 flex items-center gap-3">
                <Link
                    href={route('communication.announcements.index')}
                    className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50"
                    aria-label="Retour"
                >
                    <ArrowLeft className="h-5 w-5" />
                </Link>

                <div>
                    <h1 className="text-xl font-bold text-slate-900">
                        Nouvelle annonce
                    </h1>
                    <p className="text-sm text-slate-500">
                        Rédigez un message et choisissez les parents à informer.
                    </p>
                </div>
            </div>

            <form onSubmit={submit} className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <Card.Header title="Message" />

                        <Card.Body className="space-y-5">
                            <Field label="Objet" required error={errors.title}>
                                <Input
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Ex : Réunion des parents d'élèves"
                                />
                            </Field>

                            <Field
                                label="Message"
                                required
                                error={errors.body}
                                hint="Espaces disponibles : {parent}, {eleve}, {ecole}"
                            >
                                <Textarea
                                    rows={7}
                                    value={data.body}
                                    onChange={(e) => setData('body', e.target.value)}
                                    placeholder="Bonjour {parent}, nous vous invitons à la réunion..."
                                />
                            </Field>
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header
                            title="Canaux d'envoi"
                            description="Choisissez au moins un canal."
                        />
                        <Card.Body className="space-y-4">
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <button
                                    type="button"
                                    onClick={() => toggleArray('channels', 'whatsapp')}
                                    className={cn(
                                        'flex items-center gap-3 rounded-xl border p-4 text-left transition',
                                        data.channels.includes('whatsapp')
                                            ? 'border-emerald-500 bg-emerald-50'
                                            : 'border-slate-200 hover:bg-slate-50'
                                    )}
                                >
                                    <MessageCircle className="h-5 w-5 text-emerald-600" />
                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">
                                            WhatsApp
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            Numéro du parent
                                        </p>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => toggleArray('channels', 'email')}
                                    className={cn(
                                        'flex items-center gap-3 rounded-xl border p-4 text-left transition',
                                        data.channels.includes('email')
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-slate-200 hover:bg-slate-50'
                                    )}
                                >
                                    <Mail className="h-5 w-5 text-blue-600" />
                                    <div>
                                        <p className="text-sm font-semibold text-slate-800">
                                            Email
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            Adresse email du parent
                                        </p>
                                    </div>
                                </button>
                            </div>

                            {errors.channels && (
                                <p className="text-sm text-red-600">{errors.channels}</p>
                            )}

                            {providerChannel !== 'whatsapp' && data.channels.includes('whatsapp') && (
                                <div className="flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-xs text-amber-700">
                                    <Info className="mt-0.5 h-4 w-4 shrink-0" />
                                    WhatsApp n'est pas encore activé : les messages
                                    téléphoniques partiront via le fournisseur SMS actif.
                                </div>
                            )}

                            <Field label="Pièce jointe" error={errors.attachment} hint="PDF, Word, Excel ou image — 5 Mo max.">
                                <div className="flex items-center gap-3">
                                    <label className="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <Paperclip className="h-4 w-4" />
                                        Choisir un fichier
                                        <input
                                            type="file"
                                            className="hidden"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                                            onChange={(e) =>
                                                setData('attachment', e.target.files?.[0] ?? null)
                                            }
                                        />
                                    </label>

                                    {data.attachment && (
                                        <span className="truncate text-sm text-slate-600">
                                            {data.attachment.name}
                                        </span>
                                    )}
                                </div>
                            </Field>
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header
                            title="Destinataires"
                            description="À qui l'annonce doit-elle être envoyée ?"
                        />
                        <Card.Body className="space-y-5">
                            <Field label="Ciblage" error={errors.audience_type}>
                                <Select
                                    value={data.audience_type}
                                    onChange={(e) => setData('audience_type', e.target.value)}
                                >
                                    {audienceOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </Select>
                            </Field>

                            {data.audience_type === 'classes' && (
                                <div>
                                    <p className="mb-2 text-sm font-medium text-slate-700">
                                        Classes
                                    </p>
                                    <div className="grid max-h-64 grid-cols-1 gap-1 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2">
                                        {classes.map((classRoom) => (
                                            <Checkbox
                                                key={classRoom.id}
                                                label={
                                                    classRoom.level
                                                        ? `${classRoom.name} (${classRoom.level})`
                                                        : classRoom.name
                                                }
                                                checked={data.class_ids.includes(classRoom.id)}
                                                onChange={() => toggleArray('class_ids', classRoom.id)}
                                            />
                                        ))}
                                    </div>
                                    {errors.class_ids && (
                                        <p className="mt-1 text-sm text-red-600">{errors.class_ids}</p>
                                    )}
                                </div>
                            )}

                            {data.audience_type === 'levels' && (
                                <div>
                                    <p className="mb-2 text-sm font-medium text-slate-700">
                                        Niveaux
                                    </p>
                                    <div className="grid max-h-64 grid-cols-1 gap-1 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2">
                                        {levels.map((level) => (
                                            <Checkbox
                                                key={level}
                                                label={level}
                                                checked={data.levels.includes(level)}
                                                onChange={() => toggleArray('levels', level)}
                                            />
                                        ))}
                                    </div>
                                    {errors.levels && (
                                        <p className="mt-1 text-sm text-red-600">{errors.levels}</p>
                                    )}
                                </div>
                            )}

                            {data.audience_type === 'guardians' && (
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <div className="relative flex-1">
                                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                            <Input
                                                className="pl-9"
                                                value={search}
                                                onChange={(e) => setSearch(e.target.value)}
                                                placeholder="Rechercher un parent (nom, téléphone, email)"
                                            />
                                        </div>
                                    </div>

                                    <div className="max-h-64 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                        {filteredGuardians.length === 0 ? (
                                            <p className="px-2 py-3 text-sm text-slate-500">
                                                Aucun parent trouvé.
                                            </p>
                                        ) : (
                                            filteredGuardians.map((guardian) => (
                                                <Checkbox
                                                    key={guardian.id}
                                                    label={
                                                        <span>
                                                            {guardian.name}
                                                            <span className="ml-2 text-xs text-slate-400">
                                                                {guardian.phone}
                                                                {guardian.email ? ` · ${guardian.email}` : ''}
                                                            </span>
                                                        </span>
                                                    }
                                                    checked={data.guardian_ids.includes(guardian.id)}
                                                    onChange={() =>
                                                        toggleArray('guardian_ids', guardian.id)
                                                    }
                                                />
                                            ))
                                        )}
                                    </div>
                                    {errors.guardian_ids && (
                                        <p className="mt-1 text-sm text-red-600">{errors.guardian_ids}</p>
                                    )}
                                </div>
                            )}
                        </Card.Body>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <Card.Header title="Programmation" />
                        <Card.Body className="space-y-4">
                            <Field
                                label="Envoi différé (optionnel)"
                                error={errors.scheduled_at}
                                hint="Laissez vide pour envoyer dès maintenant."
                            >
                                <Input
                                    type="datetime-local"
                                    value={data.scheduled_at}
                                    onChange={(e) => setData('scheduled_at', e.target.value)}
                                />
                            </Field>

                            <div className="flex items-start gap-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
                                <CalendarClock className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                                Sans date, l'envoi démarre immédiatement après validation.
                            </div>
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header title="Récapitulatif" />
                        <Card.Body className="space-y-3 text-sm text-slate-600">
                            <div className="flex items-center gap-2">
                                <Users className="h-4 w-4 text-slate-400" />
                                {recipientsPreview}
                            </div>
                            <div className="flex items-center gap-2">
                                <Send className="h-4 w-4 text-slate-400" />
                                {data.channels.length} canal(aux) sélectionné(s)
                            </div>

                            <Button
                                type="submit"
                                loading={processing}
                                disabled={data.channels.length === 0}
                                className="w-full"
                            >
                                <Megaphone className="h-4 w-4" />
                                {data.scheduled_at ? 'Programmer l\'annonce' : 'Envoyer maintenant'}
                            </Button>
                        </Card.Body>
                    </Card>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
