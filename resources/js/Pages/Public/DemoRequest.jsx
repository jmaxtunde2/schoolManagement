import { useMemo } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Clock,
    GraduationCap,
    LogIn,
    Mail,
    MapPin,
    Phone,
    School,
    ShieldCheck,
} from 'lucide-react';

import Alert from '@/Components/UI/Alert';
import Button from '@/Components/UI/Button';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';

/**
 * Formulaire public « Demander une démonstration ».
 *
 * Volontairement court : 6 champs obligatoires, rien d'autre. Les informations
 * administratives détaillées (type d'école, effectifs, besoin…) sont saisies
 * ensuite par le Super Admin Cori lors du traitement de la demande.
 *
 * Soumettre ce formulaire ne crée NI école, NI compte, NI licence :
 * uniquement une `demo_requests` au statut `pending`.
 */
export default function DemoRequest() {
    const { flash } = usePage().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        school_name: '',
        address: '',
        phone: '',
        email: '',
        preferred_demo_date: '',
        preferred_demo_time: '',
    });

    const errorList = useMemo(() => {
        const entries = Object.entries(errors ?? {});

        return entries.length > 0 ? entries : [];
    }, [errors]);

    const submitted = Boolean(flash?.success);

    function submit(event) {
        event.preventDefault();

        post(route('demo.request.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <Head title="Demander une démonstration — CoriSchool" />

            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex min-w-0 items-center gap-2.5">
                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-700 text-white">
                            <GraduationCap className="h-5 w-5" />
                        </span>
                        <span className="truncate text-base font-bold text-slate-950">
                            CoriSchool
                        </span>
                    </Link>

                    <Link
                        href={route('login')}
                        className="inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        <LogIn className="h-4 w-4" />
                        Déjà un compte
                    </Link>
                </div>
            </header>

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <Link
                    href="/"
                    className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-900"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Retour à l'accueil
                </Link>

                <div className="mt-4">
                    <h1 className="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                        Demander une démonstration
                    </h1>

                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                        Découvrez CoriSchool en conditions réelles. Indiquez
                        vos coordonnées et le créneau qui vous convient : notre
                        équipe vous recontacte pour confirmer la démonstration.
                    </p>
                </div>

                {submitted && (
                    <div className="mt-6 max-w-3xl">
                        <Alert type="success" title="Demande enregistrée">
                            {flash.success}
                        </Alert>

                        <div className="mt-4 flex flex-wrap gap-3">
                            <Button
                                as={Link}
                                href="/"
                                variant="secondary"
                                size="lg"
                            >
                                Retour à l'accueil
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
                    <form
                        onSubmit={submit}
                        className="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]"
                    >
                        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div className="mb-5 flex items-start gap-3">
                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                    <School className="h-5 w-5" />
                                </span>

                                <div>
                                    <h2 className="font-semibold text-slate-950">
                                        Votre établissement
                                    </h2>
                                    <p className="mt-0.5 text-sm leading-6 text-slate-500">
                                        Six informations suffisent pour démarrer.
                                    </p>
                                </div>
                            </div>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <Field
                                        label="Nom de l'établissement"
                                        required
                                        error={errors.school_name}
                                        htmlFor="school_name"
                                    >
                                        <Input
                                            id="school_name"
                                            name="school_name"
                                            value={data.school_name}
                                            onChange={(e) =>
                                                setData(
                                                    'school_name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Lycée Moderne de Cotonou"
                                            required
                                            autoFocus
                                        />
                                    </Field>
                                </div>

                                <div className="sm:col-span-2">
                                    <Field
                                        label="Adresse"
                                        required
                                        error={errors.address}
                                        htmlFor="address"
                                    >
                                        <Input
                                            id="address"
                                            name="address"
                                            value={data.address}
                                            onChange={(e) =>
                                                setData('address', e.target.value)
                                            }
                                            placeholder="Quartier, rue, ville"
                                            required
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Téléphone"
                                    required
                                    error={errors.phone}
                                    htmlFor="phone"
                                >
                                    <Input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) =>
                                            setData('phone', e.target.value)
                                        }
                                        placeholder="+229 01 97 00 00 00"
                                        required
                                    />
                                </Field>

                                <Field
                                    label="Adresse e-mail"
                                    required
                                    error={errors.email}
                                    htmlFor="email"
                                >
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                        placeholder="direction@ecole.bj"
                                        required
                                    />
                                </Field>
                            </div>

                            <div className="mt-6 border-t border-slate-100 pt-5">
                                <div className="mb-5 flex items-start gap-3">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                        <CalendarDays className="h-5 w-5" />
                                    </span>

                                    <div>
                                        <h2 className="font-semibold text-slate-950">
                                            Créneau souhaité
                                        </h2>
                                        <p className="mt-0.5 text-sm leading-6 text-slate-500">
                                            Nous confirmons ensemble avant la
                                            démonstration.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field
                                        label="Date souhaitée"
                                        required
                                        error={errors.preferred_demo_date}
                                        htmlFor="preferred_demo_date"
                                    >
                                        <Input
                                            id="preferred_demo_date"
                                            name="preferred_demo_date"
                                            type="date"
                                            value={data.preferred_demo_date}
                                            onChange={(e) =>
                                                setData(
                                                    'preferred_demo_date',
                                                    e.target.value,
                                                )
                                            }
                                            min={
                                                new Date()
                                                    .toISOString()
                                                    .slice(0, 10)
                                            }
                                            required
                                        />
                                    </Field>

                                    <Field
                                        label="Heure souhaitée"
                                        required
                                        error={errors.preferred_demo_time}
                                        htmlFor="preferred_demo_time"
                                    >
                                        <Input
                                            id="preferred_demo_time"
                                            name="preferred_demo_time"
                                            type="time"
                                            value={data.preferred_demo_time}
                                            onChange={(e) =>
                                                setData(
                                                    'preferred_demo_time',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                        />
                                    </Field>
                                </div>
                            </div>

                            <div className="mt-6 flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-xs leading-5 text-slate-500">
                                    Aucune création de compte à cette étape.
                                </p>

                                <Button
                                    type="submit"
                                    size="lg"
                                    loading={processing}
                                    className="shrink-0"
                                    style={{ backgroundColor: '#047857' }}
                                >
                                    Demander une démonstration
                                </Button>
                            </div>
                        </section>

                        <aside className="space-y-4 lg:sticky lg:top-6 lg:self-start">
                            <div className="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5">
                                <div className="flex items-center gap-2 text-sm font-semibold text-emerald-900">
                                    <ShieldCheck className="h-4 w-4" />
                                    Ce que vous obtenez
                                </div>

                                <ul className="mt-3 space-y-2 text-sm text-emerald-900/80">
                                    {[
                                        'Une démonstration guidée de CoriSchool',
                                        'Les réponses à vos questions',
                                        'Un accompagnement adapté à votre école',
                                    ].map((item) => (
                                        <li
                                            key={item}
                                            className="flex items-start gap-2"
                                        >
                                            <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" />
                                            <span>{item}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <h2 className="text-sm font-semibold text-slate-950">
                                    Comment ça se passe
                                </h2>

                                <ol className="mt-4 space-y-4">
                                    {[
                                        {
                                            icon: Mail,
                                            title: 'Nous vous recontactons',
                                            hint: 'Sous 48 heures ouvrées.',
                                        },
                                        {
                                            icon: Clock,
                                            title: 'Nous calons la démo',
                                            hint: 'À la date et à l’heure choisies.',
                                        },
                                        {
                                            icon: MapPin,
                                            title: 'Démonstration en ligne',
                                            hint: 'Présentation complète de la plateforme.',
                                        },
                                    ].map((step, index) => (
                                        <li
                                            key={step.title}
                                            className="flex items-start gap-3"
                                        >
                                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                                {index + 1}
                                            </span>

                                            <div>
                                                <p className="text-sm font-medium text-slate-900">
                                                    {step.title}
                                                </p>
                                                <p className="mt-0.5 text-xs leading-5 text-slate-500">
                                                    {step.hint}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                            </div>

                            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div className="flex items-start gap-3">
                                    <Phone className="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" />
                                    <p className="text-sm leading-6 text-slate-600">
                                        Une question avant de réserver ?{' '}
                                        <Link
                                            href="/"
                                            className="font-semibold text-emerald-700 hover:underline"
                                        >
                                            Écrivez-nous
                                        </Link>
                                    </p>
                                </div>
                            </div>
                        </aside>
                    </form>
                )}
            </main>
        </div>
    );
}
