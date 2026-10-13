<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Message adressé par un parent à l'établissement (interne, sans SMS ni e-mail). */
class ParentMessage extends Model
{
    use BelongsToSchool;

    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    protected $fillable = [
        'parent_id',
        'student_id',
        'subject',
        'body',
        'status',
        'read_at',
        'read_by',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    public function isRead(): bool
    {
        return $this->status === self::STATUS_READ;
    }
}
