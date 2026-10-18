import { useMemo } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    FileText,
    GraduationCap,
    Info,
    Phone,
    UserPlus,
    Users,
} from 'lucide-react';

import Alert from '@/Components/UI/Alert';
import Button from '@/Components/UI/Button';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import { cn } from '@/Utils/cn';

/**
 * Formulaire public de préinscription en ligne (phase 7).
 *
 * Déposé depuis le mini-site de l'établissement. Aucun élève n'est créé à
 * cette étape : l'administration examine la demande puis valide la création.
 */
export default function Preinscription({ school, levels = [], submitUrl }) {
    const { flash } = usePage().props;

    const primary = school.primary_color || '#059669';
    const secondary = school.secondary_color || '#2563EB';

    const { data, setData, post, processing, errors, reset, progress } = useForm({
        first_name: '',
        last_name: '',
        birth_date: '',
        gender: '',
        requested_level: '',
        parent_name: '',
        parent_phone: '',
        parent_email: '',
        parent_relationship: 'Père',
        notes: '',
        documents: [],
    });

    const errorList = useMemo(() => Object.entries(errors ?? {}), [errors]);
    const submitted = Boolean(flash?.success);

    function submit(event) {
        event.preventDefault();

        post(submitUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <Head title={`Préinscription — ${school.name}`} />

            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <Link href={school.public_url} className="flex min-w-0 items-center gap-2.5">
                        {school.logo_url ? (
                            <img src={school.logo_url} alt={`Logo ${school.name}`} className="h-9 w-9 shrink-0 rounded-lg object-contain" />
                        ) : (
                            <span
                                className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white"
                                style={{ backgroundColor: primary }}
                            >
                                <GraduationCap className="h-5 w-5" />
                            </span>
                        )}
                        <span className="truncate text-base font-bold text-slate-950">{school.name}</span>
                    </Link>

                    <Link
                        href={school.public_url}
                        className="inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Retour au site
                    </Link>
                </div>
                <div className="h-1 w-full" style={{ background: `linear-gradient(90deg, ${primary}, ${secondary})` }} />
            </header>

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <div className="max-w-3xl">
                    <p className="text-xs font-bold uppercase tracking-wider" style={{ color: secondary }}>
                        Préinscription en ligne
                    </p>
                    <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                        Inscrire mon enfant
                    </h1>
                    <p className="mt-2 text-sm leading-6 text-slate-600 sm:text-base">
                        Remplissez ce formulaire pour déposer une demande d’inscription. L’administration de
                        l’établissement examinera votre demande et vous contactera pour finaliser l’inscription.
                    </p>
                </div>

                {submitted && (
                    <div className="mt-6 max-w-3xl">
                        <Alert type="success" title="Demande envoyée">
                            {flash.success}
                        </Alert>
                        <div className="mt-4">
                            <Button as={Link} href={school.public_url} variant="secondary" size="lg">
                                Retour au site
                            </Button>
                        </div>
                    </div>
                )}

                {errorList.length > 0 && !submitted && (
                    <div className="mt-6 max-w-3xl">
                        <Alert type="warning" title="Veuillez corriger les champs suivants">
                            <ul className="mt-1 list-disc space-y-1 pl-5">
                                {errorList.map(([field, message]) => (
                                    <li key={field}>{message}</li>
                                ))}
                            </ul>
                        </Alert>
                    </div>
                )}

                {!submitted && (
                    <form onSubmit={submit} className="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
                        <div className="space-y-6">
                            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                                <SectionHeading icon={GraduationCap} color={primary} title="L’élève" subtitle="Informations sur l’enfant à inscrire." />
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field label="Prénom" required error={errors.first_name} htmlFor="first_name">
                                        <Input
                                            id="first_name"
                                            value={data.first_name}
                                            onChange={(e) => setData('first_name', e.target.value)}
                                            required
                                            autoFocus
                                        />
                                    </Field>
                                    <Field label="Nom" required error={errors.last_name} htmlFor="last_name">
                                        <Input
                                            id="last_name"
                                            value={data.last_name}
                                            onChange={(e) => setData('last_name', e.target.value)}
                                            required
                                        />
                                    </Field>
                                    <Field label="Date de naissance" error={errors.birth_date} htmlFor="birth_date">
                                        <Input
                                            id="birth_date"
                                            type="date"
                                            value={data.birth_date}
                                            onChange={(e) => setData('birth_date', e.target.value)}
                                            max={new Date().toISOString().slice(0, 10)}
                                        />
                                    </Field>
                                    <Field label="Sexe" error={errors.gender} htmlFor="gender">
                                        <Select id="gender" value={data.gender} onChange={(e) => setData('gender', e.target.value)}>
                                            <option value="">— Sélectionner —</option>
                                            <option value="M">Masculin</option>
                                            <option value="F">Féminin</option>
                                        </Select>
                                    </Field>
                                    <div className="sm:col-span-2">
                                        <Field label="Niveau souhaité" error={errors.requested_level} htmlFor="requested_level">
                                            <Select
                                                id="requested_level"
                                                value={data.requested_level}
                                                onChange={(e) => setData('requested_level', e.target.value)}
                                            >
                                                <option value="">— Sélectionner —</option>
                                                {levels.map((level) => (
                                                    <option key={level} value={level}>
                                                        {level}
                                                    </option>
                                                ))}
                                            </Select>
                                        </Field>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                                <SectionHeading icon={Users} color={secondary} title="Le parent / responsable" subtitle="Pour vous recontacter au sujet de la demande." />
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <div className="sm:col-span-2">
                                        <Field label="Nom complet" required error={errors.parent_name} htmlFor="parent_name">
                                            <Input
                                                id="parent_name"
                                                value={data.parent_name}
                                                onChange={(e) => setData('parent_name', e.target.value)}
                                                required
                                            />
                                        </Field>
                                    </div>
                                    <Field label="Téléphone" required error={errors.parent_phone} htmlFor="parent_phone">
                                        <Input
                                            id="parent_phone"
                                            type="tel"
                                            value={data.parent_phone}
                                            onChange={(e) => setData('parent_phone', e.target.value)}
                                            placeholder="+229 01 97 00 00 00"
                                            required
                                        />
                                    </Field>
                                    <Field label="Adresse e-mail" error={errors.parent_email} htmlFor="parent_email">
                                        <Input
                                            id="parent_email"
                                            type="email"
                                            value={data.parent_email}
                                            onChange={(e) => setData('parent_email', e.target.value)}
                                        />
                                    </Field>
                                    <Field label="Lien de parenté" error={errors.parent_relationship} htmlFor="parent_relationship">
                                        <Select
                                            id="parent_relationship"
                                            value={data.parent_relationship}
                                            onChange={(e) => setData('parent_relationship', e.target.value)}
                                        >
                                            <option value="Père">Père</option>
                                            <option value="Mère">Mère</option>
                                            <option value="Tuteur">Tuteur</option>
                                            <option value="Autre">Autre</option>
                                        </Select>
                                    </Field>
                                </div>
                            </section>

                            <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                                <SectionHeading icon={FileText} color={primary} title="Documents & remarques" subtitle="Facultatif : joignez les pièces demandées par l’établissement." />
                                <div className="space-y-5">
                                    <Field label="Documents (PDF, JPG, PNG — 5 max, 5 Mo chacun)" error={errors.documents} htmlFor="documents">
                                        <input
                                            id="documents"
                                            type="file"
                                            multiple
                                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                                            onChange={(e) => setData('documents', Array.from(e.target.files ?? []))}
                                            className="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-2.5 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                                        />
                                    </Field>
                                    {progress && (
                                        <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                            <div className="h-full rounded-full" style={{ width: `${progress.percentage}%`, backgroundColor: primary }} />
                                        </div>
                                    )}
                                    <Field label="Remarques" error={errors.notes} htmlFor="notes">
                                        <Textarea
                                            id="notes"
                                            rows={3}
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            placeholder="Informations complémentaires (facultatif)"
                                        />
                                    </Field>
                                </div>
                            </section>

                            <div className="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-xs leading-5 text-slate-500">
                                    Aucun compte n’est créé à cette étape.
                                </p>
                                <Button type="submit" size="lg" loading={processing} className="shrink-0" style={{ backgroundColor: primary }}>
                                    <UserPlus className="h-4 w-4" />
                                    Envoyer ma demande
                                </Button>
                            </div>
                        </div>

                        <aside className="space-y-4 lg:sticky lg:top-6 lg:self-start">
                            <div className="rounded-2xl border p-5" style={{ borderColor: `${secondary}33`, backgroundColor: `${secondary}0d` }}>
                                <div className="flex items-center gap-2 text-sm font-semibold" style={{ color: secondary }}>
                                    <Info className="h-4 w-4" />
                                    Comment ça se passe
                                </div>
                                <ol className="mt-4 space-y-4">
                                    {[
                                        'Vous déposez la demande en ligne',
                                        'L’administration étudie le dossier',
                                        'Vous êtes contacté pour finaliser',
                                        'Votre enfant rejoint sa classe',
                                    ].map((step, index) => (
                                        <li key={step} className="flex items-start gap-3">
                                            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-slate-600 shadow-sm">
                                                {index + 1}
                                            </span>
                                            <span className="text-sm leading-6 text-slate-700">{step}</span>
                                        </li>
                                    ))}
                                </ol>
                            </div>

                            {(school.phone || school.email || school.address) && (
                                <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <h2 className="text-sm font-semibold text-slate-950">Besoin d’aide ?</h2>
                                    <ul className="mt-3 space-y-2 text-sm text-slate-600">
                                        {school.phone && (
                                            <li className="flex items-center gap-2">
                                                <Phone className="h-4 w-4 text-slate-400" />
                                                {school.phone}
                                            </li>
                                        )}
                                        {school.email && (
                                            <li className="flex items-center gap-2">
                                                <CalendarDays className="h-4 w-4 text-slate-400" />
                                                {school.email}
                                            </li>
                                        )}
                                    </ul>
                                </div>
                            )}
                        </aside>
                    </form>
                )}
            </main>
        </div>
    );
}

function SectionHeading({ icon: Icon, color, title, subtitle }) {
    return (
        <div className="mb-5 flex items-start gap-3">
            <span
                className={cn('flex h-10 w-10 shrink-0 items-center justify-center rounded-lg')}
                style={{ backgroundColor: `${color}1a`, color }}
            >
                <Icon className="h-5 w-5" />
            </span>
            <div>
                <h2 className="font-semibold text-slate-950">{title}</h2>
                <p className="mt-0.5 text-sm leading-6 text-slate-500">{subtitle}</p>
            </div>
        </div>
    );
}
