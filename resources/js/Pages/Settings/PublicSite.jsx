import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    ImagePlus,
    Plus,
    Save,
    Star,
    Trash2,
    Upload,
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
        features: [...(publicSite.features ?? []), { title: '', description: '' }].slice(0, 6),
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
            ? route('admin.settings.public-site.testimonials.update', testimonialEditing)
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
            ? route('admin.settings.public-site.gallery.update', galleryEditing)
            : route('admin.settings.public-site.gallery.store');

        galleryForm.post(url, {
            forceFormData: true,
            ...(galleryEditing ? { method: 'put' } : {}),
            onSuccess: resetGallery,
        });
    }

    function deleteItem() {
        if (!deleteTarget) return;
        const url = deleteTarget.type === 'testimonial'
            ? route('admin.settings.public-site.testimonials.destroy', deleteTarget.id)
            : route('admin.settings.public-site.gallery.destroy', deleteTarget.id);

        router.delete(url, {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    }

    return (
        <AuthenticatedLayout title="Site public">
            <Head title="Site public" />
            <div className="mx-auto max-w-6xl space-y-6">
                <div className="border-b border-slate-200 pb-5">
                    <p className="text-xs font-semibold uppercase text-emerald-700">Présence en ligne</p>
                    <div className="mt-2 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-950">Site public</h1>
                            <p className="mt-1 text-sm text-slate-600">Personnalisez les informations visibles sur le site de {school.name}.</p>
                        </div>
                        {school.public_url ? (
                            <a href={school.public_url} target="_blank" rel="noreferrer" className="inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                                Aperçu du site <Upload className="h-4 w-4 rotate-45" />
                            </a>
                        ) : (
                            <span className="text-sm text-slate-500">Aucun domaine vérifié</span>
                        )}
                    </div>
                </div>

                <div className="flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Gestion du site public">
                    {[
                        ['content', 'Présentation'],
                        ['testimonials', `Témoignages (${testimonials.length})`],
                        ['gallery', `Galerie (${gallery.length})`],
                    ].map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            role="tab"
                            aria-selected={activeTab === key}
                            onClick={() => setActiveTab(key)}
                            className={`min-h-11 whitespace-nowrap border-b-2 px-4 text-sm font-medium ${activeTab === key ? 'border-emerald-700 text-emerald-800' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                {activeTab === 'content' && (
                    <form onSubmit={saveContent} className="space-y-5">
                        <Card>
                            <Card.Header title="Présentation" description="Ces informations complètent le nom, le slogan, la description et les coordonnées définis dans les paramètres de l’établissement." />
                            <Card.Body className="grid gap-5 md:grid-cols-2">
                                <label className="block text-sm font-medium text-slate-700">
                                    Mission
                                    <Textarea className="mt-1.5" rows={4} value={contentForm.data.mission} onChange={(event) => contentForm.setData('mission', event.target.value)} error={contentForm.errors.mission} placeholder="La mission de votre établissement" />
                                    {contentForm.errors.mission && <span className="mt-1 block text-xs text-red-600">{contentForm.errors.mission}</span>}
                                </label>
                                <label className="block text-sm font-medium text-slate-700">
                                    Vision
                                    <Textarea className="mt-1.5" rows={4} value={contentForm.data.vision} onChange={(event) => contentForm.setData('vision', event.target.value)} error={contentForm.errors.vision} placeholder="La vision de votre établissement" />
                                    {contentForm.errors.vision && <span className="mt-1 block text-xs text-red-600">{contentForm.errors.vision}</span>}
                                </label>
                                <div className="md:col-span-2">
                                    <p className="text-sm font-medium text-slate-700">Valeurs</p>
                                    <div className="mt-2 grid gap-3 sm:grid-cols-2">
                                        {contentForm.data.values.map((value, index) => (
                                            <Input key={index} value={value} maxLength={120} onChange={(event) => contentForm.setData('values', contentForm.data.values.map((item, itemIndex) => itemIndex === index ? event.target.value : item))} placeholder={`Valeur ${index + 1}`} />
                                        ))}
                                    </div>
                                    {contentForm.errors.values && <p className="mt-1 text-xs text-red-600">{contentForm.errors.values}</p>}
                                </div>
                                <div className="md:col-span-2">
                                    <div className="flex items-center justify-between gap-3">
                                        <div><p className="text-sm font-medium text-slate-700">Pourquoi nous choisir ?</p><p className="mt-1 text-xs text-slate-500">Ajoutez jusqu’à six points forts propres à l’établissement.</p></div>
                                        <Button type="button" variant="secondary" size="sm" disabled={contentForm.data.features.length >= 6} onClick={() => contentForm.setData('features', [...contentForm.data.features, { title: '', description: '' }])}><Plus className="h-4 w-4" />Ajouter</Button>
                                    </div>
                                    <div className="mt-3 space-y-3">
                                        {contentForm.data.features.map((feature, index) => (
                                            <div key={index} className="grid gap-3 rounded-md border border-slate-200 p-3 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_40px]">
                                                <Input value={feature.title} maxLength={100} onChange={(event) => contentForm.setData('features', contentForm.data.features.map((item, itemIndex) => itemIndex === index ? { ...item, title: event.target.value } : item))} placeholder="Titre" />
                                                <Textarea rows={2} value={feature.description} maxLength={500} onChange={(event) => contentForm.setData('features', contentForm.data.features.map((item, itemIndex) => itemIndex === index ? { ...item, description: event.target.value } : item))} placeholder="Courte description" />
                                                <button type="button" onClick={() => contentForm.setData('features', contentForm.data.features.filter((_, itemIndex) => itemIndex !== index))} className="flex h-10 w-10 items-center justify-center rounded-md text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Retirer ce point fort"><X className="h-4 w-4" /></button>
                                            </div>
                                        ))}
                                    </div>
                                    {Object.entries(contentForm.errors).filter(([key]) => key.startsWith('features')).map(([key, message]) => <p key={key} className="mt-1 text-xs text-red-600">{message}</p>)}
                                </div>
                            </Card.Body>
                        </Card>

                        <Card>
                            <Card.Header title="Image principale" description="Image de couverture affichée dans le hero. JPG, PNG ou WebP, jusqu’à 5 Mo." />
                            <Card.Body>
                                <FileUpload
                                    label="Couverture"
                                    previewUrl={publicSite.hero_image_url}
                                    onChange={(file) => { contentForm.setData('hero_image', file); contentForm.setData('remove_hero_image', false); }}
                                    onRemove={() => { contentForm.setData('hero_image', null); contentForm.setData('remove_hero_image', true); }}
                                    error={contentForm.errors.hero_image}
                                    hint="Minimum 640 × 360 pixels. Une image d’illustration est utilisée en l’absence de couverture."
                                />
                            </Card.Body>
                        </Card>

                        <div className="flex justify-end">
                            <Button type="submit" loading={contentForm.processing}><Save className="h-4 w-4" />Enregistrer la présentation</Button>
                        </div>
                    </form>
                )}

                {activeTab === 'testimonials' && (
                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
                        <Card>
                            <Card.Header title="Témoignages publiés" description="Seuls les témoignages actifs apparaissent sur le site public." />
                            <Card.Body className="space-y-3">
                                {testimonials.length === 0 ? <p className="py-5 text-sm text-slate-500">Aucun témoignage n’est configuré.</p> : testimonials.map((item) => (
                                    <article key={item.id} className="flex gap-3 border-b border-slate-100 pb-3 last:border-0">
                                        {item.photo_url ? <img src={item.photo_url} alt="" className="h-10 w-10 shrink-0 rounded-full object-cover" /> : <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600">{item.name.slice(0, 1)}</span>}
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2"><p className="text-sm font-semibold text-slate-900">{item.name}</p>{item.relationship && <span className="text-xs text-slate-500">{item.relationship}</span>}<span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>{item.is_active ? 'Publié' : 'Masqué'}</span></div>
                                            <p className="mt-1 line-clamp-2 text-sm text-slate-600">{item.message}</p>
                                            <div className="mt-2 flex gap-3"><button type="button" onClick={() => editTestimonial(item)} className="text-xs font-semibold text-emerald-700">Modifier</button><button type="button" onClick={() => router.put(route('admin.settings.public-site.testimonials.update', item.id), { name: item.name, relationship: item.relationship, message: item.message, sort_order: item.sort_order, is_active: !item.is_active }, { preserveScroll: true })} className="text-xs font-semibold text-slate-600">{item.is_active ? 'Masquer' : 'Publier'}</button><button type="button" onClick={() => setDeleteTarget({ type: 'testimonial', id: item.id, name: item.name })} className="text-xs font-semibold text-red-600">Supprimer</button></div>
                                        </div>
                                    </article>
                                ))}
                            </Card.Body>
                        </Card>

                        <Card>
                            <Card.Header title={testimonialEditing ? 'Modifier le témoignage' : 'Ajouter un témoignage'} />
                            <Card.Body>
                                <form onSubmit={saveTestimonial} className="space-y-4">
                                    <label className="block text-sm font-medium text-slate-700">Nom<Input className="mt-1.5" value={testimonialForm.data.name} onChange={(event) => testimonialForm.setData('name', event.target.value)} error={testimonialForm.errors.name} /></label>
                                    <label className="block text-sm font-medium text-slate-700">Relation avec l’établissement<Input className="mt-1.5" value={testimonialForm.data.relationship} onChange={(event) => testimonialForm.setData('relationship', event.target.value)} placeholder="Parent d’élève" /></label>
                                    <label className="block text-sm font-medium text-slate-700">Témoignage<Textarea className="mt-1.5" rows={5} value={testimonialForm.data.message} onChange={(event) => testimonialForm.setData('message', event.target.value)} error={testimonialForm.errors.message} /></label>
                                    <FileUpload label="Photo (facultative)" previewUrl={testimonialEditing ? testimonials.find((item) => item.id === testimonialEditing)?.photo_url : null} onChange={(file) => testimonialForm.setData('photo', file)} onRemove={() => { testimonialForm.setData('photo', null); testimonialForm.setData('remove_photo', true); }} error={testimonialForm.errors.photo} />
                                    <label className="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" checked={testimonialForm.data.is_active} onChange={(event) => testimonialForm.setData('is_active', event.target.checked)} className="h-4 w-4 rounded border-slate-300" />Publier sur le site</label>
                                    <div className="flex justify-end gap-2">{testimonialEditing && <Button type="button" variant="secondary" onClick={resetTestimonial}>Annuler</Button>}<Button type="submit" loading={testimonialForm.processing}><Star className="h-4 w-4" />{testimonialEditing ? 'Enregistrer' : 'Ajouter'}</Button></div>
                                </form>
                            </Card.Body>
                        </Card>
                    </div>
                )}

                {activeTab === 'gallery' && (
                    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
                        <Card>
                            <Card.Header title="Galerie publiée" description="Les images actives sont visibles sur le site public." />
                            <Card.Body className="grid gap-3 sm:grid-cols-2">
                                {gallery.length === 0 ? <p className="py-5 text-sm text-slate-500 sm:col-span-2">Aucune image n’est configurée.</p> : gallery.map((item) => (
                                    <article key={item.id} className="overflow-hidden border border-slate-200">
                                        <img src={item.image_url} alt={item.alt_text} className="aspect-[4/3] w-full object-cover" />
                                        <div className="p-3">
                                            <div className="flex items-center justify-between gap-2"><p className="truncate text-sm font-semibold text-slate-900">{item.title || item.alt_text}</p><span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${item.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}`}>{item.is_active ? 'Publié' : 'Masqué'}</span></div>
                                            {item.description && <p className="mt-1 line-clamp-2 text-xs text-slate-500">{item.description}</p>}
                                            <div className="mt-3 flex gap-3"><button type="button" onClick={() => editGallery(item)} className="text-xs font-semibold text-emerald-700">Modifier</button><button type="button" onClick={() => router.put(route('admin.settings.public-site.gallery.update', item.id), { title: item.title, description: item.description, alt_text: item.alt_text, sort_order: item.sort_order, is_active: !item.is_active }, { preserveScroll: true })} className="text-xs font-semibold text-slate-600">{item.is_active ? 'Masquer' : 'Publier'}</button><button type="button" onClick={() => setDeleteTarget({ type: 'gallery', id: item.id, name: item.title || item.alt_text })} className="text-xs font-semibold text-red-600">Supprimer</button></div>
                                        </div>
                                    </article>
                                ))}
                            </Card.Body>
                        </Card>

                        <Card>
                            <Card.Header title={galleryEditing ? 'Modifier l’image' : 'Ajouter une image'} />
                            <Card.Body>
                                <form onSubmit={saveGallery} className="space-y-4">
                                    <FileUpload label="Image" previewUrl={galleryEditing ? gallery.find((item) => item.id === galleryEditing)?.image_url : null} onChange={(file) => galleryForm.setData('image', file)} onRemove={() => galleryForm.setData('image', null)} error={galleryForm.errors.image} hint="JPG, PNG ou WebP, jusqu’à 4 Mo." />
                                    <label className="block text-sm font-medium text-slate-700">Texte alternatif<Input className="mt-1.5" value={galleryForm.data.alt_text} onChange={(event) => galleryForm.setData('alt_text', event.target.value)} error={galleryForm.errors.alt_text} placeholder="Description de l’image" /></label>
                                    <label className="block text-sm font-medium text-slate-700">Titre (facultatif)<Input className="mt-1.5" value={galleryForm.data.title} onChange={(event) => galleryForm.setData('title', event.target.value)} /></label>
                                    <label className="block text-sm font-medium text-slate-700">Description (facultative)<Textarea className="mt-1.5" rows={3} value={galleryForm.data.description} onChange={(event) => galleryForm.setData('description', event.target.value)} /></label>
                                    <label className="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" checked={galleryForm.data.is_active} onChange={(event) => galleryForm.setData('is_active', event.target.checked)} className="h-4 w-4 rounded border-slate-300" />Publier sur le site</label>
                                    <div className="flex justify-end gap-2">{galleryEditing && <Button type="button" variant="secondary" onClick={resetGallery}>Annuler</Button>}<Button type="submit" loading={galleryForm.processing}><ImagePlus className="h-4 w-4" />{galleryEditing ? 'Enregistrer' : 'Ajouter à la galerie'}</Button></div>
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
                description={deleteTarget ? `Supprimer « ${deleteTarget.name} » ? Cette action est définitive.` : undefined}
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}