import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    ArrowUpRight,
    Check,
    Eye,
    FileImage,
    ImagePlus,
    LayoutDashboard,
    Plus,
    Save,
    Star,
    Trash2,
    Upload,
    Users,
    X,
} from 'lucide-react';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Button from '@/Components/UI/Button';
import Card from '@/Components/UI/Card';
import ConfirmDialog from '@/Components/UI/ConfirmDialog';
import FileUpload from '@/Components/UI/FileUpload';
import Input from '@/Components/UI/Input';
import Textarea from '@/Components/UI/Textarea';

export default function PublicSite({ school, publicSite, testimonials, gallery }) {
    const [activeTab, setActiveTab] = useState('content');
    const [testimonialEditing, setTestimonialEditing] = useState(null);
    const [galleryEditing, setGalleryEditing] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);

    const contentForm = useForm({
        mission: publicSite.mission ?? '',
        vision: publicSite.vision ?? '',
        values: [...(publicSite.values ?? []), ''].slice(0, 5),
        features: [
            ...(publicSite.features ?? []),
            { title: '', description: '' },
        ].slice(0, 6),
        hero_image: null,
        remove_hero_image: false,
    });

    const testimonialForm = useForm({
        name: '',
        relationship: '',
        message: '',
        photo: null,
        remove_photo: false,
        sort_order: testimonials.length,
        is_active: true,
    });

    const galleryForm = useForm({
        image: null,
        title: '',
        description: '',
        alt_text: '',
        sort_order: gallery.length,
        is_active: true,
    });

    function saveContent(event) {
        event.preventDefault();

        contentForm
            .transform((data) => ({
                ...data,
                values: data.values.filter((value) => value.trim()),
                features: data.features.filter((feature) => feature.title.trim()),
            }))
            .post(route('admin.settings.public-site.update'), {
                forceFormData: true,
                method: 'put',
            });
    }

    function resetTestimonial() {
        setTestimonialEditing(null);

        testimonialForm.reset();

        testimonialForm.setData({
            name: '',
            relationship: '',
            message: '',
            photo: null,
            remove_photo: false,
            sort_order: testimonials.length,
            is_active: true,
        });
    }

    function editTestimonial(item) {
        setTestimonialEditing(item.id);

        testimonialForm.setData({
            name: item.name,
            relationship: item.relationship ?? '',
            message: item.message,
            photo: null,
            remove_photo: false,
            sort_order: item.sort_order,
            is_active: item.is_active,
        });
    }

    function saveTestimonial(event) {
        event.preventDefault();

        const url = testimonialEditing
            ? route(
                  'admin.settings.public-site.testimonials.update',
                  testimonialEditing,
              )
            : route('admin.settings.public-site.testimonials.store');

        testimonialForm.post(url, {
            forceFormData: true,
            ...(testimonialEditing ? { method: 'put' } : {}),
            onSuccess: resetTestimonial,
        });
    }

    function resetGallery() {
        setGalleryEditing(null);

        galleryForm.setData({
            image: null,
            title: '',
            description: '',
            alt_text: '',
            sort_order: gallery.length,
            is_active: true,
        });

        galleryForm.clearErrors();
    }

    function editGallery(item) {
        setGalleryEditing(item.id);

        galleryForm.setData({
            image: null,
            title: item.title ?? '',
            description: item.description ?? '',
            alt_text: item.alt_text,
            sort_order: item.sort_order,
            is_active: item.is_active,
        });
    }

    function saveGallery(event) {
        event.preventDefault();

        const url = galleryEditing
            ? route(
                  'admin.settings.public-site.gallery.update',
                  galleryEditing,
              )
            : route('admin.settings.public-site.gallery.store');

        galleryForm.post(url, {
            forceFormData: true,
            ...(galleryEditing ? { method: 'put' } : {}),
            onSuccess: resetGallery,
        });
    }

    function deleteItem() {
        if (!deleteTarget) return;

        const url =
            deleteTarget.type === 'testimonial'
                ? route(
                      'admin.settings.public-site.testimonials.destroy',
                      deleteTarget.id,
                  )
                : route(
                      'admin.settings.public-site.gallery.destroy',
                      deleteTarget.id,
                  );

        router.delete(url, {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    }

    const tabs = [
        {
            key: 'content',
            label: 'Présentation',
            icon: LayoutDashboard,
        },
        {
            key: 'testimonials',
            label: 'Témoignages',
            count: testimonials.length,
            icon: Users,
        },
        {
            key: 'gallery',
            label: 'Galerie',
            count: gallery.length,
            icon: FileImage,
        },
    ];

    return (
        <AuthenticatedLayout title="Site public">
            <Head title="Site public" />

            <div className="mx-auto max-w-7xl space-y-6">
                {/* Header */}
                <section className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-700 to-cyan-700 p-6 shadow-lg sm:p-8">
                    <div className="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10" />
                    <div className="absolute -bottom-24 right-24 h-48 w-48 rounded-full bg-white/5" />

                    <div className="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex min-w-0 items-start gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20 backdrop-blur">
                                <LayoutDashboard className="h-7 w-7" />
                            </div>

                            <div className="min-w-0">
                                <p className="text-xs font-bold uppercase tracking-[0.18em] text-emerald-100">
                                    Présence en ligne
                                </p>

                                <h1 className="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                                    Site public
                                </h1>

                                <p className="mt-2 max-w-2xl text-sm leading-6 text-emerald-50 sm:text-base">
                                    Personnalisez la vitrine numérique de{' '}
                                    <span className="font-semibold text-white">
                                        {school.name}
                                    </span>{' '}
                                    et présentez votre établissement aux familles.
                                </p>
                            </div>
                        </div>

                        <div className="shrink-0">
                            {school.public_url ? (
                                <a
                                    href={school.public_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex min-h-11 items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-emerald-800 shadow-sm transition hover:bg-emerald-50"
                                >
                                    <Eye className="h-4 w-4" />
                                    Aperçu du site
                                    <ArrowUpRight className="h-4 w-4" />
                                </a>
                            ) : (
                                <div className="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-medium text-white/80 ring-1 ring-white/15">
                                    <Eye className="h-4 w-4" />
                                    Aucun domaine vérifié
                                </div>
                            )}
                        </div>
                    </div>
                </section>

                {/* Quick stats */}
                <div className="grid gap-3 sm:grid-cols-3">
                    <MiniStat
                        icon={LayoutDashboard}
                        label="Présentation"
                        value="Contenu"
                        description="Mission, vision et valeurs"
                    />

                    <MiniStat
                        icon={Users}
                        label="Témoignages"
                        value={testimonials.length}
                        description="Témoignages configurés"
                    />

                    <MiniStat
                        icon={FileImage}
                        label="Galerie"
                        value={gallery.length}
                        description="Images configurées"
                    />
                </div>

                {/* Tabs */}
                <div
                    className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                    role="tablist"
                    aria-label="Gestion du site public"
                >
                    <div className="flex overflow-x-auto">
                        {tabs.map((tab) => {
                            const Icon = tab.icon;
                            const active = activeTab === tab.key;

                            return (
                                <button
                                    key={tab.key}
                                    type="button"
                                    role="tab"
                                    aria-selected={active}
                                    onClick={() => setActiveTab(tab.key)}
                                    className={`relative flex min-h-14 shrink-0 items-center gap-2 border-b-2 px-5 text-sm font-semibold transition ${
                                        active
                                            ? 'border-emerald-600 bg-emerald-50/60 text-emerald-800'
                                            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-800'
                                    }`}
                                >
                                    <Icon
                                        className={`h-4 w-4 ${
                                            active
                                                ? 'text-emerald-600'
                                                : 'text-slate-400'
                                        }`}
                                    />

                                    {tab.label}

                                    {typeof tab.count === 'number' && (
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-[11px] font-bold ${
                                                active
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-slate-100 text-slate-500'
                                            }`}
                                        >
                                            {tab.count}
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Content */}
                {activeTab === 'content' && (
                    <form onSubmit={saveContent} className="space-y-6">
                        <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                            <Card.Header
                                title="Présentation de l'établissement"
                                description="Présentez l'identité, la mission et les principaux atouts de votre établissement."
                            />

                            <Card.Body className="space-y-7">
                                <div className="grid gap-5 md:grid-cols-2">
                                    <FormSection
                                        icon="M"
                                        title="Mission"
                                        description="Pourquoi votre établissement existe."
                                    >
                                        <Textarea
                                            rows={5}
                                            value={contentForm.data.mission}
                                            onChange={(event) =>
                                                contentForm.setData(
                                                    'mission',
                                                    event.target.value,
                                                )
                                            }
                                            error={contentForm.errors.mission}
                                            placeholder="La mission de votre établissement"
                                        />

                                        {contentForm.errors.mission && (
                                            <ErrorMessage>
                                                {contentForm.errors.mission}
                                            </ErrorMessage>
                                        )}
                                    </FormSection>

                                    <FormSection
                                        icon="V"
                                        title="Vision"
                                        description="L'ambition et la direction de l'établissement."
                                    >
                                        <Textarea
                                            rows={5}
                                            value={contentForm.data.vision}
                                            onChange={(event) =>
                                                contentForm.setData(
                                                    'vision',
                                                    event.target.value,
                                                )
                                            }
                                            error={contentForm.errors.vision}
                                            placeholder="La vision de votre établissement"
                                        />

                                        {contentForm.errors.vision && (
                                            <ErrorMessage>
                                                {contentForm.errors.vision}
                                            </ErrorMessage>
                                        )}
                                    </FormSection>
                                </div>

                                <div className="border-t border-slate-100 pt-6">
                                    <SectionHeading
                                        icon={Star}
                                        title="Nos valeurs"
                                        description="Jusqu'à cinq valeurs qui représentent l'identité de l'établissement."
                                    />

                                    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        {contentForm.data.values.map(
                                            (value, index) => (
                                                <div
                                                    key={index}
                                                    className="group relative rounded-xl border border-slate-200 bg-slate-50/60 p-3 transition focus-within:border-emerald-300 focus-within:bg-white"
                                                >
                                                    <div className="mb-2 flex items-center gap-2">
                                                        <span className="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 text-[11px] font-bold text-emerald-700">
                                                            {index + 1}
                                                        </span>
                                                        <span className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                                            Valeur
                                                        </span>
                                                    </div>

                                                    <Input
                                                        value={value}
                                                        maxLength={120}
                                                        onChange={(event) =>
                                                            contentForm.setData(
                                                                'values',
                                                                contentForm.data.values.map(
                                                                    (
                                                                        item,
                                                                        itemIndex,
                                                                    ) =>
                                                                        itemIndex ===
                                                                        index
                                                                            ? event
                                                                                  .target
                                                                                  .value
                                                                            : item,
                                                                ),
                                                            )
                                                        }
                                                        placeholder={`Valeur ${
                                                            index + 1
                                                        }`}
                                                    />
                                                </div>
                                            ),
                                        )}
                                    </div>

                                    {contentForm.errors.values && (
                                        <ErrorMessage>
                                            {contentForm.errors.values}
                                        </ErrorMessage>
                                    )}
                                </div>

                                <div className="border-t border-slate-100 pt-6">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <SectionHeading
                                            icon={Check}
                                            title="Pourquoi nous choisir ?"
                                            description="Ajoutez jusqu'à six points forts propres à l'établissement."
                                        />

                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            disabled={
                                                contentForm.data.features
                                                    .length >= 6
                                            }
                                            onClick={() =>
                                                contentForm.setData(
                                                    'features',
                                                    [
                                                        ...contentForm.data
                                                            .features,
                                                        {
                                                            title: '',
                                                            description: '',
                                                        },
                                                    ],
                                                )
                                            }
                                        >
                                            <Plus className="h-4 w-4" />
                                            Ajouter
                                        </Button>
                                    </div>

                                    <div className="mt-4 space-y-3">
                                        {contentForm.data.features.map(
                                            (feature, index) => (
                                                <div
                                                    key={index}
                                                    className="group rounded-2xl border border-slate-200 bg-slate-50/50 p-4 transition hover:border-emerald-200 hover:bg-white"
                                                >
                                                    <div className="flex gap-3">
                                                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-xs font-bold text-white shadow-sm">
                                                            {index + 1}
                                                        </span>

                                                        <div className="grid min-w-0 flex-1 gap-3 sm:grid-cols-[minmax(0,0.75fr)_minmax(0,1.25fr)]">
                                                            <Input
                                                                value={
                                                                    feature.title
                                                                }
                                                                maxLength={100}
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    contentForm.setData(
                                                                        'features',
                                                                        contentForm.data.features.map(
                                                                            (
                                                                                item,
                                                                                itemIndex,
                                                                            ) =>
                                                                                itemIndex ===
                                                                                index
                                                                                    ? {
                                                                                          ...item,
                                                                                          title: event
                                                                                              .target
                                                                                              .value,
                                                                                      }
                                                                                    : item,
                                                                        ),
                                                                    )
                                                                }
                                                                placeholder="Titre du point fort"
                                                            />

                                                            <Textarea
                                                                rows={2}
                                                                value={
                                                                    feature.description
                                                                }
                                                                maxLength={500}
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    contentForm.setData(
                                                                        'features',
                                                                        contentForm.data.features.map(
                                                                            (
                                                                                item,
                                                                                itemIndex,
                                                                            ) =>
                                                                                itemIndex ===
                                                                                index
                                                                                    ? {
                                                                                          ...item,
                                                                                          description:
                                                                                              event
                                                                                                  .target
                                                                                                  .value,
                                                                                      }
                                                                                    : item,
                                                                        ),
                                                                    )
                                                                }
                                                                placeholder="Courte description"
                                                            />
                                                        </div>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                contentForm.setData(
                                                                    'features',
                                                                    contentForm.data.features.filter(
                                                                        (
                                                                            _,
                                                                            itemIndex,
                                                                        ) =>
                                                                            itemIndex !==
                                                                            index,
                                                                    ),
                                                                )
                                                            }
                                                            className="flex h-9 w-9 shrink-0 items-center justify-center self-start rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                            aria-label="Retirer ce point fort"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>

                                    {Object.entries(contentForm.errors)
                                        .filter(([key]) =>
                                            key.startsWith('features'),
                                        )
                                        .map(([key, message]) => (
                                            <ErrorMessage key={key}>
                                                {message}
                                            </ErrorMessage>
                                        ))}
                                </div>
                            </Card.Body>
                        </Card>

                        {/* Hero image */}
                        <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                            <Card.Header
                                title="Image principale"
                                description="L'image de couverture affichée dans le hero du site public."
                            />

                            <Card.Body>
                                <div className="rounded-2xl border border-dashed border-emerald-200 bg-gradient-to-br from-emerald-50/80 via-white to-teal-50/60 p-4 sm:p-6">
                                    <div className="mb-4 flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                            <FileImage className="h-5 w-5" />
                                        </div>

                                        <div>
                                            <p className="text-sm font-bold text-slate-900">
                                                Couverture du site
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                JPG, PNG ou WebP · jusqu'à 5 Mo
                                            </p>
                                        </div>
                                    </div>

                                    <FileUpload
                                        label="Choisir une couverture"
                                        previewUrl={publicSite.hero_image_url}
                                        onChange={(file) => {
                                            contentForm.setData(
                                                'hero_image',
                                                file,
                                            );
                                            contentForm.setData(
                                                'remove_hero_image',
                                                false,
                                            );
                                        }}
                                        onRemove={() => {
                                            contentForm.setData(
                                                'hero_image',
                                                null,
                                            );
                                            contentForm.setData(
                                                'remove_hero_image',
                                                true,
                                            );
                                        }}
                                        error={contentForm.errors.hero_image}
                                        hint="Minimum 640 × 360 pixels. Une image d'illustration est utilisée en l'absence de couverture."
                                    />
                                </div>
                            </Card.Body>
                        </Card>

                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                loading={contentForm.processing}
                                className="min-w-48"
                            >
                                <Save className="h-4 w-4" />
                                Enregistrer la présentation
                            </Button>
                        </div>
                    </form>
                )}

                {/* Testimonials */}
                {activeTab === 'testimonials' && (
                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
                        <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                            <Card.Header
                                title="Témoignages publiés"
                                description="Les témoignages actifs apparaissent sur le site public."
                            />

                            <Card.Body className="p-0">
                                {testimonials.length === 0 ? (
                                    <EmptyState
                                        icon={Users}
                                        title="Aucun témoignage"
                                        description="Ajoutez le premier témoignage d'un parent, élève ou membre de la communauté."
                                    />
                                ) : (
                                    <div className="divide-y divide-slate-100">
                                        {testimonials.map((item) => (
                                            <article
                                                key={item.id}
                                                className="p-5 transition hover:bg-slate-50/70"
                                            >
                                                <div className="flex gap-4">
                                                    {item.photo_url ? (
                                                        <img
                                                            src={item.photo_url}
                                                            alt=""
                                                            className="h-12 w-12 shrink-0 rounded-2xl object-cover ring-2 ring-white shadow-sm"
                                                        />
                                                    ) : (
                                                        <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 text-base font-bold text-emerald-700">
                                                            {item.name
                                                                .slice(0, 1)
                                                                .toUpperCase()}
                                                        </span>
                                                    )}

                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <p className="font-bold text-slate-900">
                                                                {item.name}
                                                            </p>

                                                            {item.relationship && (
                                                                <span className="text-xs text-slate-500">
                                                                    {
                                                                        item.relationship
                                                                    }
                                                                </span>
                                                            )}

                                                            <StatusBadge
                                                                active={
                                                                    item.is_active
                                                                }
                                                            />
                                                        </div>

                                                        <div className="mt-2 flex gap-1 text-amber-400">
                                                            {Array.from({
                                                                length: 5,
                                                            }).map(
                                                                (_, index) => (
                                                                    <Star
                                                                        key={
                                                                            index
                                                                        }
                                                                        className="h-3.5 w-3.5 fill-current"
                                                                    />
                                                                ),
                                                            )}
                                                        </div>

                                                        <p className="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">
                                                            {item.message}
                                                        </p>

                                                        <div className="mt-4 flex flex-wrap items-center gap-4">
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    editTestimonial(
                                                                        item,
                                                                    )
                                                                }
                                                                className="text-xs font-bold text-emerald-700 transition hover:text-emerald-900"
                                                            >
                                                                Modifier
                                                            </button>

                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    router.put(
                                                                        route(
                                                                            'admin.settings.public-site.testimonials.update',
                                                                            item.id,
                                                                        ),
                                                                        {
                                                                            name: item.name,
                                                                            relationship:
                                                                                item.relationship,
                                                                            message:
                                                                                item.message,
                                                                            sort_order:
                                                                                item.sort_order,
                                                                            is_active:
                                                                                !item.is_active,
                                                                        },
                                                                        {
                                                                            preserveScroll:
                                                                                true,
                                                                        },
                                                                    )
                                                                }
                                                                className="text-xs font-bold text-slate-600 transition hover:text-slate-900"
                                                            >
                                                                {item.is_active
                                                                    ? 'Masquer'
                                                                    : 'Publier'}
                                                            </button>

                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    setDeleteTarget(
                                                                        {
                                                                            type: 'testimonial',
                                                                            id: item.id,
                                                                            name: item.name,
                                                                        },
                                                                    )
                                                                }
                                                                className="inline-flex items-center gap-1 text-xs font-bold text-rose-600 transition hover:text-rose-800"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                                Supprimer
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        ))}
                                    </div>
                                )}
                            </Card.Body>
                        </Card>

                        <Card className="h-fit overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                            <Card.Header
                                title={
                                    testimonialEditing
                                        ? 'Modifier le témoignage'
                                        : 'Ajouter un témoignage'
                                }
                                description={
                                    testimonialEditing
                                        ? 'Modifiez les informations puis enregistrez.'
                                        : 'Ajoutez un témoignage à votre site public.'
                                }
                            />

                            <Card.Body>
                                <form
                                    onSubmit={saveTestimonial}
                                    className="space-y-5"
                                >
                                    <Field label="Nom">
                                        <Input
                                            value={
                                                testimonialForm.data.name
                                            }
                                            onChange={(event) =>
                                                testimonialForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                            error={
                                                testimonialForm.errors.name
                                            }
                                            placeholder="Nom du témoin"
                                        />
                                    </Field>

                                    <Field label="Relation avec l'établissement">
                                        <Input
                                            value={
                                                testimonialForm.data.relationship
                                            }
                                            onChange={(event) =>
                                                testimonialForm.setData(
                                                    'relationship',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Parent d'élève"
                                        />
                                    </Field>

                                    <Field label="Témoignage">
                                        <Textarea
                                            rows={6}
                                            value={
                                                testimonialForm.data.message
                                            }
                                            onChange={(event) =>
                                                testimonialForm.setData(
                                                    'message',
                                                    event.target.value,
                                                )
                                            }
                                            error={
                                                testimonialForm.errors.message
                                            }
                                            placeholder="Écrivez le témoignage..."
                                        />
                                    </Field>

                                    <div className="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                                        <FileUpload
                                            label="Photo (facultative)"
                                            previewUrl={
                                                testimonialEditing
                                                    ? testimonials.find(
                                                          (item) =>
                                                              item.id ===
                                                              testimonialEditing,
                                                      )?.photo_url
                                                    : null
                                            }
                                            onChange={(file) =>
                                                testimonialForm.setData(
                                                    'photo',
                                                    file,
                                                )
                                            }
                                            onRemove={() => {
                                                testimonialForm.setData(
                                                    'photo',
                                                    null,
                                                );
                                                testimonialForm.setData(
                                                    'remove_photo',
                                                    true,
                                                );
                                            }}
                                            error={
                                                testimonialForm.errors.photo
                                            }
                                        />
                                    </div>

                                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-emerald-200 hover:bg-emerald-50/40">
                                        <input
                                            type="checkbox"
                                            checked={
                                                testimonialForm.data.is_active
                                            }
                                            onChange={(event) =>
                                                testimonialForm.setData(
                                                    'is_active',
                                                    event.target.checked,
                                                )
                                            }
                                            className="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        />

                                        <span>
                                            <span className="block text-sm font-semibold text-slate-800">
                                                Publier sur le site
                                            </span>
                                            <span className="mt-0.5 block text-xs text-slate-500">
                                                Le témoignage sera visible par
                                                les visiteurs.
                                            </span>
                                        </span>
                                    </label>

                                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        {testimonialEditing && (
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={resetTestimonial}
                                            >
                                                Annuler
                                            </Button>
                                        )}

                                        <Button
                                            type="submit"
                                            loading={
                                                testimonialForm.processing
                                            }
                                        >
                                            <Star className="h-4 w-4" />
                                            {testimonialEditing
                                                ? 'Enregistrer'
                                                : 'Ajouter le témoignage'}
                                        </Button>
                                    </div>
                                </form>
                            </Card.Body>
                        </Card>
                    </div>
                )}

                {/* Gallery */}
                {activeTab === 'gallery' && (
                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
                        <Card className="overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                            <Card.Header
                                title="Galerie du site"
                                description="Les images actives sont visibles dans la galerie publique."
                            />

                            <Card.Body className="p-4 sm:p-5">
                                {gallery.length === 0 ? (
                                    <EmptyState
                                        icon={FileImage}
                                        title="Galerie vide"
                                        description="Ajoutez des photos pour présenter votre établissement, vos activités et vos infrastructures."
                                    />
                                ) : (
                                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                        {gallery.map((item) => (
                                            <article
                                                key={item.id}
                                                className="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                            >
                                                <div className="relative overflow-hidden bg-slate-100">
                                                    <img
                                                        src={item.image_url}
                                                        alt={item.alt_text}
                                                        className="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                                    />

                                                    <div className="absolute left-3 top-3">
                                                        <StatusBadge
                                                            active={
                                                                item.is_active
                                                            }
                                                        />
                                                    </div>
                                                </div>

                                                <div className="p-4">
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="min-w-0">
                                                            <p className="truncate text-sm font-bold text-slate-900">
                                                                {item.title ||
                                                                    item.alt_text}
                                                            </p>

                                                            {item.description && (
                                                                <p className="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                                                                    {
                                                                        item.description
                                                                    }
                                                                </p>
                                                            )}
                                                        </div>

                                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                                            <ImagePlus className="h-4 w-4" />
                                                        </div>
                                                    </div>

                                                    <div className="mt-4 flex items-center gap-4 border-t border-slate-100 pt-3">
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                editGallery(item)
                                                            }
                                                            className="text-xs font-bold text-emerald-700 transition hover:text-emerald-900"
                                                        >
                                                            Modifier
                                                        </button>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                router.put(
                                                                    route(
                                                                        'admin.settings.public-site.gallery.update',
                                                                        item.id,
                                                                    ),
                                                                    {
                                                                        title: item.title,
                                                                        description:
                                                                            item.description,
                                                                        alt_text:
                                                                            item.alt_text,
                                                                        sort_order:
                                                                            item.sort_order,
                                                                        is_active:
                                                                            !item.is_active,
                                                                    },
                                                                    {
                                                                        preserveScroll:
                                                                            true,
                                                                    },
                                                                )
                                                            }
                                                            className="text-xs font-bold text-slate-600 transition hover:text-slate-900"
                                                        >
                                                            {item.is_active
                                                                ? 'Masquer'
                                                                : 'Publier'}
                                                        </button>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setDeleteTarget(
                                                                    {
                                                                        type: 'gallery',
                                                                        id: item.id,
                                                                        name:
                                                                            item.title ||
                                                                            item.alt_text,
                                                                    },
                                                                )
                                                            }
                                                            className="ml-auto inline-flex items-center gap-1 text-xs font-bold text-rose-600 transition hover:text-rose-800"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                            Supprimer
                                                        </button>
                                                    </div>
                                                </div>
                                            </article>
                                        ))}
                                    </div>
                                )}
                            </Card.Body>
                        </Card>

                        <Card className="h-fit overflow-hidden border-0 shadow-sm ring-1 ring-slate-200">
                            <Card.Header
                                title={
                                    galleryEditing
                                        ? "Modifier l'image"
                                        : 'Ajouter une image'
                                }
                                description="Ajoutez une image optimisée pour votre galerie publique."
                            />

                            <Card.Body>
                                <form
                                    onSubmit={saveGallery}
                                    className="space-y-5"
                                >
                                    <div className="rounded-2xl border border-dashed border-emerald-200 bg-gradient-to-br from-emerald-50/70 to-teal-50/50 p-4">
                                        <FileUpload
                                            label="Image"
                                            previewUrl={
                                                galleryEditing
                                                    ? gallery.find(
                                                          (item) =>
                                                              item.id ===
                                                              galleryEditing,
                                                      )?.image_url
                                                    : null
                                            }
                                            onChange={(file) =>
                                                galleryForm.setData(
                                                    'image',
                                                    file,
                                                )
                                            }
                                            onRemove={() =>
                                                galleryForm.setData(
                                                    'image',
                                                    null,
                                                )
                                            }
                                            error={galleryForm.errors.image}
                                            hint="JPG, PNG ou WebP, jusqu'à 4 Mo."
                                        />
                                    </div>

                                    <Field label="Texte alternatif">
                                        <Input
                                            value={
                                                galleryForm.data.alt_text
                                            }
                                            onChange={(event) =>
                                                galleryForm.setData(
                                                    'alt_text',
                                                    event.target.value,
                                                )
                                            }
                                            error={
                                                galleryForm.errors.alt_text
                                            }
                                            placeholder="Description de l'image"
                                        />
                                    </Field>

                                    <Field label="Titre" optional>
                                        <Input
                                            value={galleryForm.data.title}
                                            onChange={(event) =>
                                                galleryForm.setData(
                                                    'title',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Ex. Activité sportive"
                                        />
                                    </Field>

                                    <Field label="Description" optional>
                                        <Textarea
                                            rows={4}
                                            value={
                                                galleryForm.data.description
                                            }
                                            onChange={(event) =>
                                                galleryForm.setData(
                                                    'description',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Quelques mots sur cette image..."
                                        />
                                    </Field>

                                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-emerald-200 hover:bg-emerald-50/40">
                                        <input
                                            type="checkbox"
                                            checked={
                                                galleryForm.data.is_active
                                            }
                                            onChange={(event) =>
                                                galleryForm.setData(
                                                    'is_active',
                                                    event.target.checked,
                                                )
                                            }
                                            className="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        />

                                        <span>
                                            <span className="block text-sm font-semibold text-slate-800">
                                                Publier sur le site
                                            </span>
                                            <span className="mt-0.5 block text-xs text-slate-500">
                                                L'image sera immédiatement
                                                visible dans la galerie.
                                            </span>
                                        </span>
                                    </label>

                                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                        {galleryEditing && (
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={resetGallery}
                                            >
                                                Annuler
                                            </Button>
                                        )}

                                        <Button
                                            type="submit"
                                            loading={galleryForm.processing}
                                        >
                                            <ImagePlus className="h-4 w-4" />
                                            {galleryEditing
                                                ? 'Enregistrer'
                                                : 'Ajouter à la galerie'}
                                        </Button>
                                    </div>
                                </form>
                            </Card.Body>
                        </Card>
                    </div>
                )}
            </div>

            <ConfirmDialog
                show={Boolean(deleteTarget)}
                onClose={() => setDeleteTarget(null)}
                onConfirm={deleteItem}
                title="Confirmer la suppression"
                description={
                    deleteTarget
                        ? `Supprimer « ${deleteTarget.name} » ? Cette action est définitive.`
                        : undefined
                }
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}

function MiniStat({ icon: Icon, label, value, description }) {
    return (
        <div className="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm">
                    <Icon className="h-5 w-5" />
                </div>

                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {label}
                    </p>
                    <p className="mt-0.5 text-lg font-bold text-slate-900">
                        {value}
                    </p>
                </div>
            </div>

            <p className="mt-3 text-xs text-slate-500">{description}</p>
        </div>
    );
}

function SectionHeading({ icon: Icon, title, description }) {
    return (
        <div className="flex items-start gap-3">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                <Icon className="h-4 w-4" />
            </div>

            <div>
                <p className="text-sm font-bold text-slate-900">{title}</p>
                <p className="mt-1 text-xs leading-5 text-slate-500">
                    {description}
                </p>
            </div>
        </div>
    );
}

function FormSection({ icon, title, description, children }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
            <div className="mb-3 flex items-center gap-3">
                <span className="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-xs font-bold text-emerald-700 shadow-sm ring-1 ring-slate-200">
                    {icon}
                </span>

                <div>
                    <p className="text-sm font-bold text-slate-800">{title}</p>
                    <p className="text-xs text-slate-500">{description}</p>
                </div>
            </div>

            {children}
        </div>
    );
}

function Field({ label, optional = false, children }) {
    return (
        <label className="block">
            <span className="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700">
                {label}

                {optional && (
                    <span className="text-[11px] font-normal text-slate-400">
                        Facultatif
                    </span>
                )}
            </span>

            {children}
        </label>
    );
}

function ErrorMessage({ children }) {
    return (
        <p className="mt-1.5 text-xs font-medium text-rose-600">{children}</p>
    );
}

function StatusBadge({ active }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold ${
                active
                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                    : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'
            }`}
        >
            <span
                className={`h-1.5 w-1.5 rounded-full ${
                    active ? 'bg-emerald-500' : 'bg-slate-400'
                }`}
            />

            {active ? 'Publié' : 'Masqué'}
        </span>
    );
}

function EmptyState({ icon: Icon, title, description }) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-14 text-center">
            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 text-emerald-600 ring-1 ring-emerald-100">
                <Icon className="h-7 w-7" />
            </div>

            <p className="mt-4 text-sm font-bold text-slate-800">{title}</p>

            <p className="mt-1 max-w-md text-sm leading-6 text-slate-500">
                {description}
            </p>
        </div>
    );
}