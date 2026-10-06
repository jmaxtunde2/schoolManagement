import { useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    CalendarDays,
    Check,
    Eye,
    EyeOff,
    GraduationCap,
    LogIn,
    MapPin,
    ShieldCheck,
    UserCog,
} from 'lucide-react';

import Alert from '@/Components/UI/Alert';
import Button from '@/Components/UI/Button';
import Field from '@/Components/UI/Field';
import FileUpload from '@/Components/UI/FileUpload';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';

/**
 * Onboarding public : « Créer mon école ».
 *
 * Le contrôleur déduit le `school_id` du serveur (RegisterSchool) : aucun
 * identifiant d'établissement n'est envoyé par le formulaire. Les listes
 * `schoolTypes` et `departments` viennent du serveur, l'année scolaire est
 * pré-remplie avec la suggestion béninoise (septembre -> juillet).
 */
export default function SchoolRegistration({
    schoolTypes = [],
    departments = [],
    defaultAcademicYear = {},
}) {
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors } = useForm(
        {
            // Établissement
            school_name: '',
            short_name: '',
            school_type: '',
            founded_year: '',
            // Coordonnées
            address: '',
            city: '',
            department: '',
            country: 'Bénin',
            phone: '',
            phone_secondary: '',
            email: '',
            website: '',
            contact_name: '',
            // Année scolaire
            academic_year_name: defaultAcademicYear.name ?? '',
            academic_year_starts_on: defaultAcademicYear.starts_on ?? '',
            academic_year_ends_on: defaultAcademicYear.ends_on ?? '',
            // Logo
            logo: null,
            // Administrateur
            admin_name: '',
            admin_email: '',
            admin_phone: '',
            admin_password: '',
            admin_password_confirmation: '',
        },
        { forceFormData: true },
    );

    const errorList = useMemo(() => {
        const entries = Object.entries(errors ?? {});

        return entries.length > 0 ? entries : [];
    }, [errors]);

    function submit(event) {
        event.preventDefault();

        post(route('school.register.store'));
    }

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900">
            <Head title="Créer mon école — CoriSchool" />

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
                        Créer mon école
                    </h1>

                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                        L'établissement, son année scolaire et le compte de
                        l'administrateur sont créés en une seule étape. Vous
                        serez connecté automatiquement à la fin de l'inscription.
                    </p>
                </div>

                {errorList.length > 0 && (
                    <Alert
                        type="warning"
                        title="Le formulaire contient des erreurs"
                        className="mt-6"
                    >
                        <ul className="list-inside list-disc space-y-0.5">
                            {errorList.map(([field, message]) => (
                                <li key={field}>{message}</li>
                            ))}
                        </ul>
                    </Alert>
                )}

                <form
                    onSubmit={submit}
                    className="mt-8 grid gap-6 lg:grid-cols-[1.6fr_1fr] lg:items-start"
                >
                    <div className="space-y-6">
                        <Section
                            icon={Building2}
                            title="Identité de l'établissement"
                            description="Ces informations apparaissent sur le site public de votre école."
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <Field
                                        label="Nom de l'école"
                                        required
                                        error={errors.school_name}
                                    >
                                        <Input
                                            value={data.school_name}
                                            onChange={(e) =>
                                                setData(
                                                    'school_name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Lycée moderne de Cotonou"
                                            autoFocus
                                            required
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Sigle"
                                    error={errors.short_name}
                                    hint="Abréviation utilisée dans les listes et les bulletins."
                                >
                                    <Input
                                        value={data.short_name}
                                        onChange={(e) =>
                                            setData(
                                                'short_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="LMC"
                                    />
                                </Field>

                                <Field
                                    label="Type d'établissement"
                                    error={errors.school_type}
                                >
                                    <Select
                                        value={data.school_type}
                                        onChange={(e) =>
                                            setData(
                                                'school_type',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        <option value="">
                                            Non précisé
                                        </option>
                                        {schoolTypes.map((type) => (
                                            <option
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>

                                <Field
                                    label="Année de création"
                                    error={errors.founded_year}
                                >
                                    <Input
                                        type="number"
                                        min="1900"
                                        max={new Date().getFullYear()}
                                        value={data.founded_year}
                                        onChange={(e) =>
                                            setData(
                                                'founded_year',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="1998"
                                    />
                                </Field>

                                <div className="sm:col-span-2">
                                    <FileUpload
                                        label="Logo de l'école"
                                        hint="JPG, PNG ou WebP, entre 64×64 et 2000×2000 pixels, 2 Mo maximum."
                                        error={errors.logo}
                                        onChange={(file) =>
                                            setData('logo', file)
                                        }
                                        onRemove={() =>
                                            setData('logo', null)
                                        }
                                    />
                                </div>
                            </div>
                        </Section>

                        <Section
                            icon={MapPin}
                            title="Coordonnées"
                            description="Utilisées pour le site public, les messages aux familles et la facturation."
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <Field
                                        label="Adresse"
                                        required
                                        error={errors.address}
                                    >
                                        <Input
                                            value={data.address}
                                            onChange={(e) =>
                                                setData(
                                                    'address',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Rue des Écoles, Carrefour Godomey"
                                            required
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Ville"
                                    required
                                    error={errors.city}
                                >
                                    <Input
                                        value={data.city}
                                        onChange={(e) =>
                                            setData('city', e.target.value)
                                        }
                                        placeholder="Cotonou"
                                        required
                                    />
                                </Field>

                                <Field
                                    label="Département"
                                    required
                                    error={errors.department}
                                >
                                    <Select
                                        value={data.department}
                                        onChange={(e) =>
                                            setData(
                                                'department',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    >
                                        <option value="">
                                            Sélectionnez un département
                                        </option>
                                        {departments.map((department) => (
                                            <option
                                                key={department.value}
                                                value={department.value}
                                            >
                                                {department.label}
                                            </option>
                                        ))}
                                    </Select>
                                </Field>

                                <Field
                                    label="Téléphone principal"
                                    required
                                    error={errors.phone}
                                >
                                    <Input
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
                                    label="Téléphone secondaire"
                                    error={errors.phone_secondary}
                                >
                                    <Input
                                        type="tel"
                                        value={data.phone_secondary}
                                        onChange={(e) =>
                                            setData(
                                                'phone_secondary',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="+229 21 30 00 00 00"
                                    />
                                </Field>

                                <Field
                                    label="Email"
                                    error={errors.email}
                                >
                                    <Input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                        placeholder="contact@ecole.bj"
                                    />
                                </Field>

                                <Field
                                    label="Site web"
                                    error={errors.website}
                                >
                                    <Input
                                        type="url"
                                        value={data.website}
                                        onChange={(e) =>
                                            setData('website', e.target.value)
                                        }
                                        placeholder="https://ecole.bj"
                                    />
                                </Field>

                                <div className="sm:col-span-2">
                                    <Field
                                        label="Contact administratif"
                                        error={errors.contact_name}
                                        hint="Personne à joindre en priorité (directeur, secrétaire…)."
                                    >
                                        <Input
                                            value={data.contact_name}
                                            onChange={(e) =>
                                                setData(
                                                    'contact_name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Mme Aïdou Adjovi"
                                        />
                                    </Field>
                                </div>
                            </div>
                        </Section>

                        <Section
                            icon={CalendarDays}
                            title="Année scolaire de départ"
                            description="Vous pourrez en créer d'autres et clôturer celle-ci plus tard."
                        >
                            <div className="grid gap-4 sm:grid-cols-3">
                                <Field
                                    label="Année scolaire"
                                    required
                                    error={errors.academic_year_name}
                                >
                                    <Input
                                        value={data.academic_year_name}
                                        onChange={(e) =>
                                            setData(
                                                'academic_year_name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="2025-2026"
                                        required
                                    />
                                </Field>

                                <Field
                                    label="Début"
                                    required
                                    error={
                                        errors.academic_year_starts_on
                                    }
                                >
                                    <Input
                                        type="date"
                                        value={data.academic_year_starts_on}
                                        onChange={(e) =>
                                            setData(
                                                'academic_year_starts_on',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </Field>

                                <Field
                                    label="Fin"
                                    required
                                    error={errors.academic_year_ends_on}
                                >
                                    <Input
                                        type="date"
                                        value={data.academic_year_ends_on}
                                        onChange={(e) =>
                                            setData(
                                                'academic_year_ends_on',
                                                e.target.value,
                                            )
                                        }
                                        required
                                    />
                                </Field>
                            </div>
                        </Section>

                        <Section
                            icon={UserCog}
                            title="Compte administrateur"
                            description="Ce compte disposera des droits de direction sur l'établissement."
                        >
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <Field
                                        label="Nom complet"
                                        required
                                        error={errors.admin_name}
                                    >
                                        <Input
                                            value={data.admin_name}
                                            onChange={(e) =>
                                                setData(
                                                    'admin_name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Jean Kossi"
                                            autoComplete="name"
                                            required
                                        />
                                    </Field>
                                </div>

                                <Field
                                    label="Email"
                                    required
                                    error={errors.admin_email}
                                >
                                    <Input
                                        type="email"
                                        value={data.admin_email}
                                        onChange={(e) =>
                                            setData(
                                                'admin_email',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="direction@ecole.bj"
                                        autoComplete="username"
                                        required
                                    />
                                </Field>

                                <Field
                                    label="Téléphone"
                                    error={errors.admin_phone}
                                >
                                    <Input
                                        type="tel"
                                        value={data.admin_phone}
                                        onChange={(e) =>
                                            setData(
                                                'admin_phone',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="+229 01 97 00 00 00"
                                        autoComplete="tel"
                                    />
                                </Field>

                                <Field
                                    label="Mot de passe"
                                    required
                                    error={errors.admin_password}
                                    hint="8 caractères minimum."
                                >
                                    <PasswordInput
                                        value={data.admin_password}
                                        onChange={(value) =>
                                            setData(
                                                'admin_password',
                                                value,
                                            )
                                        }
                                        visible={showPassword}
                                        onToggle={() =>
                                            setShowPassword(
                                                (value) => !value,
                                            )
                                        }
                                        autoComplete="new-password"
                                        placeholder="••••••••"
                                    />
                                </Field>

                                <Field
                                    label="Confirmation"
                                    required
                                    error={
                                        errors.admin_password_confirmation
                                    }
                                >
                                    <PasswordInput
                                        value={
                                            data.admin_password_confirmation
                                        }
                                        onChange={(value) =>
                                            setData(
                                                'admin_password_confirmation',
                                                value,
                                            )
                                        }
                                        visible={showPassword}
                                        onToggle={() =>
                                            setShowPassword(
                                                (value) => !value,
                                            )
                                        }
                                        autoComplete="new-password"
                                        placeholder="••••••••"
                                    />
                                </Field>
                            </div>
                        </Section>

                        <div className="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-xs leading-5 text-slate-500">
                                En créant votre école, vous obtenez un accès
                                direction complet sur les données de
                                l'établissement.
                            </p>

                            <Button
                                type="submit"
                                size="lg"
                                loading={processing}
                                className="shrink-0"
                                // Page publique de la plateforme : on garde la
                                // teinte emerald du parcours au lieu du bleu
                                // « guest » appliqué par la variante primary.
                                style={{ backgroundColor: '#047857' }}
                            >
                                Créer mon école
                            </Button>
                        </div>
                    </div>

                    <Summary
                        data={data}
                        schoolTypes={schoolTypes}
                        processing={processing}
                    />
                </form>
            </main>
        </div>
    );
}

/* ==========================================================================
 | COMPOSANTS LOCAUX
 | ==========================================================================*/

function Section({ icon: Icon, title, description, children }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div className="mb-5 flex items-start gap-3">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <Icon className="h-5 w-5" />
                </span>

                <div>
                    <h2 className="font-semibold text-slate-950">{title}</h2>
                    {description && (
                        <p className="mt-0.5 text-sm leading-6 text-slate-500">
                            {description}
                        </p>
                    )}
                </div>
            </div>

            {children}
        </section>
    );
}

function PasswordInput({
    value,
    onChange,
    visible,
    onToggle,
    ...props
}) {
    return (
        <div className="relative">
            <Input
                type={visible ? 'text' : 'password'}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="pr-12"
                required
                {...props}
            />

            <button
                type="button"
                onClick={onToggle}
                className="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                aria-label={
                    visible
                        ? 'Masquer le mot de passe'
                        : 'Afficher le mot de passe'
                }
            >
                {visible ? (
                    <EyeOff className="h-5 w-5" />
                ) : (
                    <Eye className="h-5 w-5" />
                )}
            </button>
        </div>
    );
}

/** Récapitulatif en direct, pour que le visiteur vérifie sa saisie avant l'envoi. */
function Summary({ data, schoolTypes, processing }) {
    const schoolTypeLabel = schoolTypes.find(
        (type) => type.value === data.school_type,
    )?.label;

    const points = [
        {
            label: 'Établissement',
            value: data.school_name || '—',
            hint: [data.short_name, schoolTypeLabel]
                .filter(Boolean)
                .join(' · '),
        },
        {
            label: 'Localisation',
            value: [data.city, data.department].filter(Boolean).join(', ') || '—',
        },
        {
            label: 'Contact',
            value: data.phone || '—',
            hint: data.email || null,
        },
        {
            label: 'Année scolaire',
            value: data.academic_year_name || '—',
            hint:
                data.academic_year_starts_on && data.academic_year_ends_on
                    ? `${data.academic_year_starts_on} → ${data.academic_year_ends_on}`
                    : null,
        },
        {
            label: 'Administrateur',
            value: data.admin_name || '—',
            hint: data.admin_email || null,
        },
    ];

    return (
        <aside className="space-y-4 lg:sticky lg:top-6">
            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 className="text-sm font-semibold text-slate-950">
                    Récapitulatif
                </h2>

                <dl className="mt-4 space-y-4">
                    {points.map((point) => (
                        <div key={point.label}>
                            <dt className="text-xs font-medium uppercase tracking-wide text-slate-400">
                                {point.label}
                            </dt>
                            <dd className="mt-1 text-sm font-medium break-words text-slate-900">
                                {point.value}
                            </dd>

                            {point.hint && (
                                <dd className="mt-0.5 text-xs break-words text-slate-500">
                                    {point.hint}
                                </dd>
                            )}
                        </div>
                    ))}
                </dl>
            </div>

            <div className="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5">
                <div className="flex items-center gap-2 text-sm font-semibold text-emerald-900">
                    <ShieldCheck className="h-4 w-4" />
                    Ce qui est créé
                </div>

                <ul className="mt-3 space-y-2 text-sm text-emerald-900/80">
                    {[
                        "L'établissement et son espace dédié",
                        "L'année scolaire de départ",
                        'Le compte administrateur connecté',
                    ].map((item) => (
                        <li key={item} className="flex items-start gap-2">
                            <Check className="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" />
                            <span>{item}</span>
                        </li>
                    ))}
                </ul>

                {processing && (
                    <p className="mt-4 text-xs text-emerald-900/70">
                        Création en cours, merci de patienter…
                    </p>
                )}
            </div>
        </aside>
    );
}
