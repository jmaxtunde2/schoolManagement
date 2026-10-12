<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Salle physique de l'établissement (table `rooms`). */
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use BelongsToSchool, HasFactory;

    protected $fillable = ['name', 'building', 'capacity'];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }
}