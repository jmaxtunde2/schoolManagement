import { useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    Building2,
    CheckCircle2,
    Globe2,
    Image as ImageIcon,
    Mail,
    MapPin,
    Palette,
    Phone,
    Save,
    Sparkles,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/UI/Card';
import Tabs from '@/Components/UI/Tabs';
import Field from '@/Components/UI/Field';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import Textarea from '@/Components/UI/Textarea';
import ColorPicker from '@/Components/UI/ColorPicker';
import FileUpload from '@/Components/UI/FileUpload';
import Button from '@/Components/UI/Button';

const TABS = [
    {
        key: 'general',
        label: 'Informations générales',
    },
    {
        key: 'contact',
        label: 'Coordonnées',
    },
    {
        key: 'brand',
        label: 'Identité visuelle',
    },
];

export default function SchoolSettings({
    settings,
    defaults,
    schoolTypes,
}) {
    const [tab, setTab] = useState('general');

    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm({
        school_name: settings.school_name ?? '',
        short_name: settings.short_name ?? '',
        slogan: settings.slogan ?? '',
        description: settings.description ?? '',
        school_type: settings.school_type ?? '',
        founded_year: settings.founded_year ?? '',
        address: settings.address ?? '',
        city: settings.city ?? '',
        country: settings.country ?? '',
        phone: settings.phone ?? '',
        phone_secondary:
            settings.phone_secondary ?? '',
        email: settings.email ?? '',
        website: settings.website ?? '',
        contact_name:
            settings.contact_name ?? '',
        primary_color:
            settings.primary_color ??
            defaults.primary_color,
        secondary_color:
            settings.secondary_color ??
            defaults.secondary_color,
        accent_color:
            settings.accent_color ??
            defaults.accent_color,
        logo: null,
        remove_logo: false,
    });

    const [logoPreview, setLogoPreview] = useState(
        settings.logo_url
    );

    const contrast = useMemo(
        () => contrastColor(data.primary_color),
        [data.primary_color]
    );

    function submit(e) {
        e.preventDefault();

        post(route('admin.settings.update'), {
            forceFormData: true,
            method: 'put',
            onSuccess: () => {
                setData('logo', null);
            },
        });
    }

    return (
        <AuthenticatedLayout title="Paramètres de l'établissement">
            <Head title="Établissement" />

            <div className="space-y-6">
                {/* Hero */}
                <div
                    className="relative overflow-hidden rounded-2xl p-6 shadow-lg"
                    style={{
                        background: `linear-gradient(135deg, ${data.primary_color}, ${data.secondary_color})`,
                    }}
                >
                    <div
                        className="absolute inset-0 opacity-20"
                        style={{
                            background: `radial-gradient(circle at top right, ${data.accent_color}, transparent 45%)`,
                        }}
                    />

                    <div className="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div
                                className="mb-2 flex items-center gap-2 text-sm font-medium"
                                style={{
                                    color: contrast,
                                    opacity: 0.8,
                                }}
                            >
                                <Building2 className="h-5 w-5" />

                                <span>
                                    Configuration de l'établissement
                                </span>
                            </div>

                            <h1
                                className="text-2xl font-bold sm:text-3xl"
                                style={{ color: contrast }}
                            >
                                Paramètres de l'établissement
                            </h1>

                            <p
                                className="mt-2 max-w-2xl text-sm leading-6 sm:text-base"
                                style={{
                                    color: contrast,
                                    opacity: 0.8,
                                }}
                            >
                                Personnalisez les informations,
                                coordonnées et éléments visuels qui
                                représentent votre établissement sur
                                la plateforme.
                            </p>
                        </div>

                        <div
                            className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl backdrop-blur-sm"
                            style={{
                                backgroundColor:
                                    'rgba(255,255,255,0.15)',
                                color: contrast,
                            }}
                        >
                            <SettingsIcon />
                        </div>
                    </div>

                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-28 right-32 h-64 w-64 rounded-full bg-white/5" />
                </div>

                <form
                    onSubmit={submit}
                    className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
                >
                    {/* Main configuration */}
                    <div>
                        <Card className="overflow-hidden">
                            <div className="border-b border-slate-100 bg-slate-50/70">
                                <Tabs
                                    tabs={TABS}
                                    active={tab}
                                    onChange={setTab}
                                />
                            </div>

                            <Card.Body>
                                {tab === 'general' && (
                                    <GeneralTab
                                        data={data}
                                        setData={setData}
                                        errors={errors}
                                        schoolTypes={
                                            schoolTypes
                                        }
                                    />
                                )}

                                {tab === 'contact' && (
                                    <ContactTab
                                        data={data}
                                        setData={setData}
                                        errors={errors}
                                    />
                                )}

                                {tab === 'brand' && (
                                    <BrandTab
                                        data={data}
                                        setData={setData}
                                        errors={errors}
                                        logoPreview={
                                            logoPreview
                                        }
                                        setLogoPreview={
                                            setLogoPreview
                                        }
                                    />
                                )}
                            </Card.Body>

                            <div className="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/60 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                                <div className="flex items-center gap-2 text-xs text-slate-500">
                                    <CheckCircle2 className="h-4 w-4 text-emerald-500" />

                                    <span>
                                        Les modifications seront
                                        appliquées à l'établissement.
                                    </span>
                                </div>

                                <Button
                                    type="submit"
                                    loading={processing}
                                >
                                    <Save className="h-4 w-4" />
                                    Enregistrer les modifications
                                </Button>
                            </div>
                        </Card>
                    </div>

                    {/* Preview */}
                    <div>
                        <Card className="sticky top-20 overflow-hidden">
                            <Card.Header
                                title="Aperçu"
                                description="Visualisez immédiatement l'identité de votre établissement."
                            />

                            {/* Brand preview */}
                            <div
                                className="relative overflow-hidden p-5"
                                style={{
                                    background: `linear-gradient(135deg, ${data.primary_color}, ${data.secondary_color})`,
                                }}
                            >
                                <div
                                    className="absolute -right-10 -top-10 h-32 w-32 rounded-full"
                                    style={{
                                        backgroundColor:
                                            data.accent_color,
                                        opacity: 0.2,
                                    }}
                                />

                                <div className="relative z-10">
                                    <div className="flex items-center gap-3">
                                        {logoPreview ? (
                                            <img
                                                src={logoPreview}
                                                alt="Logo"
                                                className="h-14 w-14 rounded-xl bg-white object-contain p-1.5 shadow-sm"
                                            />
                                        ) : (
                                            <div
                                                className="flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 text-lg font-bold backdrop-blur-sm"
                                                style={{
                                                    color: contrast,
                                                }}
                                            >
                                                {(
                                                    data.short_name ||
                                                    data.school_name ||
                                                    'EC'
                                                )
                                                    .slice(0, 2)
                                                    .toUpperCase()}
                                            </div>
                                        )}

                                        <div className="min-w-0">
                                            <p
                                                className="truncate font-bold"
                                                style={{
                                                    color: contrast,
                                                }}
                                            >
                                                {data.school_name ||
                                                    "Nom de l'établissement"}
                                            </p>

                                            {data.slogan && (
                                                <p
                                                    className="mt-0.5 truncate text-sm"
                                                    style={{
                                                        color: contrast,
                                                        opacity: 0.8,
                                                    }}
                                                >
                                                    {data.slogan}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    {data.description && (
                                        <p
                                            className="mt-5 line-clamp-3 text-sm leading-5"
                                            style={{
                                                color: contrast,
                                                opacity: 0.85,
                                            }}
                                        >
                                            {
                                                data.description
                                            }
                                        </p>
                                    )}
                                </div>
                            </div>

                            <Card.Body className="space-y-4">
                                <PreviewInfo
                                    icon={MapPin}
                                    label="Localisation"
                                    value={
                                        [
                                            data.city,
                                            data.country,
                                        ]
                                            .filter(Boolean)
                                            .join(', ') ||
                                        'Non renseignée'
                                    }
                                />

                                <PreviewInfo
                                    icon={Phone}
                                    label="Téléphone"
                                    value={
                                        data.phone ||
                                        'Non renseigné'
                                    }
                                />

                                <PreviewInfo
                                    icon={Mail}
                                    label="Email"
                                    value={
                                        data.email ||
                                        'Non renseigné'
                                    }
                                />

                                <PreviewInfo
                                    icon={Globe2}
                                    label="Site web"
                                    value={
                                        data.website ||
                                        'Non renseigné'
                                    }
                                />

                                <div className="border-t border-slate-100 pt-4">
                                    <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Palette de marque
                                    </p>

                                    <div className="space-y-3">
                                        <PreviewSwatch
                                            label="Primaire"
                                            color={
                                                data.primary_color
                                            }
                                        />

                                        <PreviewSwatch
                                            label="Secondaire"
                                            color={
                                                data.secondary_color
                                            }
                                        />

                                        <PreviewSwatch
                                            label="Accent"
                                            color={
                                                data.accent_color
                                            }
                                        />
                                    </div>
                                </div>
                            </Card.Body>
                        </Card>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

function GeneralTab({
    data,
    setData,
    errors,
    schoolTypes,
}) {
    return (
        <div className="space-y-6">
            <Section
                icon={Building2}
                title="Identité de l'établissement"
                description="Les informations principales utilisées pour identifier l'établissement."
                tone="blue"
            >
                <Field
                    label="Nom de l'établissement"
                    required
                    error={errors.school_name}
                >
                    <Input
                        value={data.school_name}
                        onChange={(e) =>
                            setData(
                                'school_name',
                                e.target.value
                            )
                        }
                        placeholder="Ex : Collège..."
                    />
                </Field>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field
                        label="Nom court"
                        error={errors.short_name}
                        hint="Utilisé dans le menu et les SMS."
                    >
                        <Input
                            value={data.short_name}
                            onChange={(e) =>
                                setData(
                                    'short_name',
                                    e.target.value
                                )
                            }
                            placeholder="Ex : CEG..."
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
                                    e.target.value
                                )
                            }
                        >
                            <option value="">—</option>

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
                </div>
            </Section>

            <Section
                icon={Sparkles}
                title="Présentation"
                description="Présentez brièvement votre établissement aux utilisateurs."
                tone="violet"
            >
                <Field
                    label="Slogan"
                    error={errors.slogan}
                >
                    <Input
                        value={data.slogan}
                        onChange={(e) =>
                            setData(
                                'slogan',
                                e.target.value
                            )
                        }
                        placeholder="Votre slogan..."
                    />
                </Field>

                <Field
                    label="Description"
                    error={errors.description}
                >
                    <Textarea
                        value={data.description}
                        onChange={(e) =>
                            setData(
                                'description',
                                e.target.value
                            )
                        }
                        placeholder="Présentez brièvement l'établissement..."
                        rows={5}
                    />
                </Field>
            </Section>

            <Section
                icon={CheckCircle2}
                title="Historique"
                description="Informations complémentaires sur l'établissement."
                tone="emerald"
            >
                <Field
                    label="Année de création"
                    error={errors.founded_year}
                    hint="Année de fondation de l'établissement."
                >
                    <Input
                        type="number"
                        min="1800"
                        max="2100"
                        className="max-w-[180px]"
                        value={data.founded_year}
                        onChange={(e) =>
                            setData(
                                'founded_year',
                                e.target.value
                            )
                        }
                        placeholder="Ex : 1998"
                    />
                </Field>
            </Section>
        </div>
    );
}

function ContactTab({
    data,
    setData,
    errors,
}) {
    return (
        <div className="space-y-6">
            <Section
                icon={MapPin}
                title="Localisation"
                description="Adresse physique et localisation de l'établissement."
                tone="blue"
            >
                <Field
                    label="Adresse"
                    error={errors.address}
                >
                    <Input
                        value={data.address}
                        onChange={(e) =>
                            setData(
                                'address',
                                e.target.value
                            )
                        }
                        placeholder="Adresse complète"
                    />
                </Field>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field
                        label="Ville"
                        error={errors.city}
                    >
                        <Input
                            value={data.city}
                            onChange={(e) =>
                                setData(
                                    'city',
                                    e.target.value
                                )
                            }
                            placeholder="Ville"
                        />
                    </Field>

                    <Field
                        label="Pays"
                        error={errors.country}
                    >
                        <Input
                            value={data.country}
                            onChange={(e) =>
                                setData(
                                    'country',
                                    e.target.value
                                )
                            }
                            placeholder="Pays"
                        />
                    </Field>
                </div>
            </Section>

            <Section
                icon={Phone}
                title="Téléphones"
                description="Coordonnées téléphoniques utilisées par l'établissement."
                tone="emerald"
            >
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field
                        label="Téléphone principal"
                        error={errors.phone}
                    >
                        <Input
                            value={data.phone}
                            onChange={(e) =>
                                setData(
                                    'phone',
                                    e.target.value
                                )
                            }
                            placeholder="+229..."
                        />
                    </Field>

                    <Field
                        label="Téléphone secondaire"
                        error={
                            errors.phone_secondary
                        }
                    >
                        <Input
                            value={
                                data.phone_secondary
                            }
                            onChange={(e) =>
                                setData(
                                    'phone_secondary',
                                    e.target.value
                                )
                            }
                            placeholder="+229..."
                        />
                    </Field>
                </div>
            </Section>

            <Section
                icon={Globe2}
                title="Présence numérique"
                description="Coordonnées numériques publiques de l'établissement."
                tone="violet"
            >
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field
                        label="Email"
                        error={errors.email}
                    >
                        <Input
                            type="email"
                            value={data.email}
                            onChange={(e) =>
                                setData(
                                    'email',
                                    e.target.value
                                )
                            }
                            placeholder="contact@ecole.com"
                        />
                    </Field>

                    <Field
                        label="Site web"
                        error={errors.website}
                        hint="Inclure https://"
                    >
                        <Input
                            value={data.website}
                            onChange={(e) =>
                                setData(
                                    'website',
                                    e.target.value
                                )
                            }
                            placeholder="https://..."
                        />
                    </Field>
                </div>
            </Section>

            <Section
                icon={Building2}
                title="Contact administratif"
                description="Personne à contacter pour les échanges administratifs."
                tone="amber"
            >
                <Field
                    label="Nom du contact"
                    error={errors.contact_name}
                >
                    <Input
                        value={data.contact_name}
                        onChange={(e) =>
                            setData(
                                'contact_name',
                                e.target.value
                            )
                        }
                        placeholder="Nom et prénom"
                    />
                </Field>
            </Section>
        </div>
    );
}

function BrandTab({
    data,
    setData,
    errors,
    logoPreview,
    setLogoPreview,
}) {
    return (
        <div className="space-y-6">
            <Section
                icon={ImageIcon}
                title="Logo"
                description="Ajoutez le logo qui représentera votre établissement."
                tone="blue"
            >
                <FileUpload
                    label="Logo de l'établissement"
                    previewUrl={logoPreview}
                    error={errors.logo}
                    hint="JPG, PNG ou WebP, 2 Mo maximum."
                    onChange={(file) => {
                        setData((current) => ({
                            ...current,
                            logo: file,
                            remove_logo: false,
                        }));

                        if (file) {
                            setLogoPreview(
                                URL.createObjectURL(file)
                            );
                        }
                    }}
                    onRemove={() => {
                        setLogoPreview(null);

                        setData((current) => ({
                            ...current,
                            logo: null,
                            remove_logo: true,
                        }));
                    }}
                />
            </Section>

            <Section
                icon={Palette}
                title="Couleurs de l'établissement"
                description="Personnalisez les couleurs utilisées dans l'interface et les éléments de marque."
                tone="violet"
            >
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <ColorPicker
                        label="Couleur primaire"
                        value={data.primary_color}
                        onChange={(value) =>
                            setData(
                                'primary_color',
                                value
                            )
                        }
                        error={errors.primary_color}
                    />

                    <ColorPicker
                        label="Couleur secondaire"
                        value={data.secondary_color}
                        onChange={(value) =>
                            setData(
                                'secondary_color',
                                value
                            )
                        }
                        error={errors.secondary_color}
                    />

                    <ColorPicker
                        label="Couleur d'accent"
                        value={data.accent_color}
                        onChange={(value) =>
                            setData(
                                'accent_color',
                                value
                            )
                        }
                        error={errors.accent_color}
                    />
                </div>
            </Section>

            <div className="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                <div className="flex items-start gap-3">
                    <Palette className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />

                    <div>
                        <p className="font-semibold text-amber-900">
                            Aperçu en direct
                        </p>

                        <p className="mt-1 text-sm leading-5 text-amber-700">
                            Les couleurs et le logo sont
                            immédiatement visibles dans l'aperçu à
                            droite. Les changements ne deviennent
                            définitifs qu'après l'enregistrement.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

function Section({
    icon: Icon,
    title,
    description,
    tone = 'blue',
    children,
}) {
    const tones = {
        blue: {
            wrapper:
                'border-blue-100 bg-blue-50/40',
            icon: 'bg-blue-100 text-blue-600',
        },
        violet: {
            wrapper:
                'border-violet-100 bg-violet-50/40',
            icon: 'bg-violet-100 text-violet-600',
        },
        emerald: {
            wrapper:
                'border-emerald-100 bg-emerald-50/40',
            icon: 'bg-emerald-100 text-emerald-600',
        },
        amber: {
            wrapper:
                'border-amber-100 bg-amber-50/40',
            icon: 'bg-amber-100 text-amber-600',
        },
    };

    const current = tones[tone] ?? tones.blue;

    return (
        <section
            className={`rounded-2xl border p-5 ${current.wrapper}`}
        >
            <div className="mb-5 flex items-start gap-3">
                <div
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${current.icon}`}
                >
                    <Icon className="h-5 w-5" />
                </div>

                <div>
                    <h2 className="font-semibold text-slate-800">
                        {title}
                    </h2>

                    <p className="mt-1 text-sm leading-5 text-slate-500">
                        {description}
                    </p>
                </div>
            </div>

            <div className="space-y-4">
                {children}
            </div>
        </section>
    );
}

function PreviewInfo({
    icon: Icon,
    label,
    value,
}) {
    return (
        <div className="flex items-start gap-3">
            <div className="rounded-lg bg-slate-100 p-2 text-slate-500">
                <Icon className="h-4 w-4" />
            </div>

            <div className="min-w-0">
                <p className="text-xs font-medium uppercase tracking-wide text-slate-400">
                    {label}
                </p>

                <p className="mt-0.5 truncate text-sm font-medium text-slate-700">
                    {value}
                </p>
            </div>
        </div>
    );
}

function PreviewSwatch({
    label,
    color,
}) {
    return (
        <div className="flex items-center gap-3">
            <span
                className="h-7 w-7 shrink-0 rounded-lg border border-slate-200 shadow-sm"
                style={{
                    backgroundColor: color,
                }}
            />

            <span className="text-sm text-slate-600">
                {label}
            </span>

            <span className="ml-auto font-mono text-xs text-slate-400">
                {color}
            </span>
        </div>
    );
}

function SettingsIcon() {
    return (
        <div className="relative">
            <SettingsGear />
        </div>
    );
}

function SettingsGear() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            className="h-8 w-8"
        >
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M10.3 3.2h3.4l.5 2a7.7 7.7 0 0 1 1.7 1l1.9-.8 2.4 2.4-.8 1.9a7.7 7.7 0 0 1 1 1.7l2 .5v3.4l-2 .5a7.7 7.7 0 0 1-1 1.7l.8 1.9-2.4 2.4-1.9-.8a7.7 7.7 0 0 1-1.7 1l-.5 2h-3.4l-.5-2a7.7 7.7 0 0 1-1.7-1l-1.9.8-2.4-2.4.8-1.9a7.7 7.7 0 0 1-1-1.7l-2-.5v-3.4l2-.5a7.7 7.7 0 0 1 1-1.7l-.8-1.9L6.2 5.4l1.9.8a7.7 7.7 0 0 1 1.7-1l.5-2Z"
            />
            <circle
                cx="12"
                cy="13.6"
                r="3.1"
            />
        </svg>
    );
}

function contrastColor(hex) {
    if (!hex || hex.length !== 7) {
        return '#FFFFFF';
    }

    const [r, g, b] = [1, 3, 5].map(
        (index) =>
            parseInt(
                hex.slice(index, index + 2),
                16
            ) / 255
    );

    const lin = (channel) =>
        channel <= 0.03928
            ? channel / 12.92
            : ((channel + 0.055) / 1.055) ** 2.4;

    const luminance =
        0.2126 * lin(r) +
        0.7152 * lin(g) +
        0.0722 * lin(b);

    return luminance > 0.4
        ? '#111827'
        : '#FFFFFF';
}