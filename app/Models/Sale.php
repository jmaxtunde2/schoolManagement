<?php

namespace App\Models;

use App\Enums\SaleCategory;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = ['category', 'item', 'quantity', 'unit_price', 'total', 'sold_at', 'buyer', 'created_by'];

    protected function casts(): array
    {
        return [
            'category' => SaleCategory::class,
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'total' => 'integer',
            'sold_at' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}