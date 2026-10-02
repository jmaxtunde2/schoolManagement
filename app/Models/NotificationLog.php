<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use BelongsToSchool;

    protected $fillable = ['notification_id', 'status', 'message'];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
