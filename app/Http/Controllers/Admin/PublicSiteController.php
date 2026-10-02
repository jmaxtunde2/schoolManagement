<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\UpdatePublicSiteSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePublicSiteSettingsRequest;
use App\Models\SchoolPublicGalleryItem;
use App\Models\SchoolPublicTestimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function edit(Request $request): Response
    {
        $school = $request->user()->school;
        $setting = $school->settingsOrNew();
        Gate::authorize('view', $setting);

        $publicSite = data_get($setting->options, 'public_site', []);
        $publicSite = is_array($publicSite) ? $publicSite : [];
        $verifiedDomain = $school->domains()
            ->where('verified', true)
            ->orderByDesc('is_primary')
            ->value('domain');

        return Inertia::render('Settings/PublicSite', [
            'school' => [
                'name' => $setting->school_name ?: $school->name,
                'slogan' => $setting->slogan,
                'logo_url' => $setting->logo_url,
                'primary_color' => $setting->primary_color,
                'secondary_color' => $setting->secondary_color,
                'address' => $setting->address,
                'phone' => $setting->phone,
                'email' => $setting->email,
                'public_url' => $verifiedDomain
                    ? 'https://'.$verifiedDomain
                    : route('public.school.slug', $school->slug),
            ],
            'publicSite' => [
                'mission' => $publicSite['mission'] ?? '',
                'vision' => $publicSite['vision'] ?? '',
                'values' => $publicSite['values'] ?? [],
                'features' => $publicSite['features'] ?? [],
                'hero_image_url' => ! empty($publicSite['hero_image_path'])
                    ? Storage::disk('public')->url($publicSite['hero_image_path'])
                    : null,
            ],
            'testimonials' => SchoolPublicTestimonial::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (SchoolPublicTestimonial $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'relationship' => $item->relationship,
                    'message' => $item->message,
                    'photo_url' => $item->photo_url,
                    'is_active' => $item->is_active,
                    'sort_order' => $item->sort_order,
                ]),
            'gallery' => SchoolPublicGalleryItem::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (SchoolPublicGalleryItem $item) => [
                    'id' => $item->id,
                    'image_url' => $item->image_url,
                    'title' => $item->title,
                    'description' => $item->description,
                    'alt_text' => $item->alt_text,
                    'is_active' => $item->is_active,
                    'sort_order' => $item->sort_order,
                ]),
        ]);
    }

    public function update(
        UpdatePublicSiteSettingsRequest $request,
        UpdatePublicSiteSettings $action
    ): RedirectResponse {
        $action->handle(
            $request->user()->school,
            $request->safe()->except(['hero_image', 'remove_hero_image']),
            $request->file('hero_image'),
            $request->boolean('remove_hero_image')
        );

        return back()->with('success', 'Le contenu public a été enregistré.');
    }

    public function storeTestimonial(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'relationship' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=2000,max_height=2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $school = $request->user()->school;
        $path = $request->file('photo')?->store(
            "schools/{$school->id}/public-site/testimonials",
            'public'
        );

        $school->publicTestimonials()->create([
            ...collect($data)->except('photo')->all(),
            'photo_path' => $path,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Témoignage ajouté.');
    }

    public function updateTestimonial(Request $request, int $testimonial): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'relationship' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=2000,max_height=2000'],
            'remove_photo' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $schoolId = $request->user()->school_id;
        $item = SchoolPublicTestimonial::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->findOrFail($testimonial);
        $oldPhoto = $item->photo_path;

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store(
                "schools/{$schoolId}/public-site/testimonials",
                'public'
            );
        } elseif ($request->boolean('remove_photo')) {
            $data['photo_path'] = null;
        }

        $item->update(collect($data)->except(['photo', 'remove_photo'])->all());

        if ($oldPhoto && $oldPhoto !== $item->photo_path) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return back()->with('success', 'Témoignage mis à jour.');
    }

    public function destroyTestimonial(Request $request, int $testimonial): RedirectResponse
    {
        $item = SchoolPublicTestimonial::withoutGlobalScopes()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($testimonial);
        $photo = $item->photo_path;
        $item->delete();

        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        return back()->with('success', 'Témoignage supprimé.');
    }

    public function storeGalleryItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=320,min_height=200,max_width=4000,max_height=3000'],
            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'alt_text' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $school = $request->user()->school;
        $path = $request->file('image')->store(
            "schools/{$school->id}/public-site/gallery",
            'public'
        );

        $school->publicGalleryItems()->create([
            ...collect($data)->except('image')->all(),
            'image_path' => $path,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Image ajoutée à la galerie.');
    }

    public function updateGalleryItem(Request $request, int $galleryItem): RedirectResponse
    {
        $data = $request->validate([
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=320,min_height=200,max_width=4000,max_height=3000'],
            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'alt_text' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $schoolId = $request->user()->school_id;
        $item = SchoolPublicGalleryItem::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->findOrFail($galleryItem);
        $oldImage = $item->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store(
                "schools/{$schoolId}/public-site/gallery",
                'public'
            );
        }

        $item->update(collect($data)->except('image')->all());

        if ($oldImage !== $item->image_path) {
            Storage::disk('public')->delete($oldImage);
        }

        return back()->with('success', 'Image de la galerie mise à jour.');
    }

    public function destroyGalleryItem(Request $request, int $galleryItem): RedirectResponse
    {
        $item = SchoolPublicGalleryItem::withoutGlobalScopes()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($galleryItem);
        $image = $item->image_path;
        $item->delete();
        Storage::disk('public')->delete($image);

        return back()->with('success', 'Image supprimée de la galerie.');
    }
}