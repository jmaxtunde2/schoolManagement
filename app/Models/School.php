<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\ReservedSchoolSlugs;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (School $school) {
            if (empty($school->slug)) {
                $school->slug = static::generateUniqueSlug($school->name);
            }
        });
    }

    /** Slug public unique dérivé du nom (ex. « Ketry School » → ketry-school-1up6). */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'ecole';
        $reserved = ReservedSchoolSlugs::all();

        do {
            $slug = $base.'-'.Str::lower(Str::random(4));
        } while (
            in_array($slug, $reserved, true)
            || static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        );

        return $slug;
    }

    /**
     * URL publique canonique de l'établissement sur la plateforme
     * (ex. https://www.corischool.com/ketry-school).
     */
    public function publicUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$this->slug;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(SchoolDomain::class);
    }

    public function billingSettings(): HasOne
    {
        return $this->hasOne(SchoolBillingSetting::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(SchoolSetting::class);
    }

    public function publicTestimonials(): HasMany
    {
        return $this->hasMany(SchoolPublicTestimonial::class);
    }

    public function publicGalleryItems(): HasMany
    {
        return $this->hasMany(SchoolPublicGalleryItem::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function classRooms(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    /** Membres du personnel rattachés à cet établissement. */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->whereIn(
            'role',
            array_map(fn (Role $role) => $role->value, Role::assignableStaff())
        );
    }

    public function mailSettings(): HasOne
    {
        return $this->hasOne(SchoolMailSetting::class);
    }

    public function communicationSettings(): HasOne
    {
        return $this->hasOne(SchoolCommunicationSetting::class);
    }

    /** Configuration existante ou instance vide (non persistée) rattachée à l'école. */
    public function settingsOrNew(): SchoolSetting
    {
        return $this->settings ?? $this->settings()->make();
    }
}
