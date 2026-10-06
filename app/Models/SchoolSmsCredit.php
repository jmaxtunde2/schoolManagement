<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SchoolSmsCredit extends Model
{
    use BelongsToSchool;

    protected $fillable = ['credits', 'source', 'amount_paid'];
}
