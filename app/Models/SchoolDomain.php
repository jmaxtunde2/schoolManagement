<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SchoolDomain extends Model { protected $fillable=['school_id','domain','is_primary','verified']; protected $casts=['is_primary'=>'boolean','verified'=>'boolean']; public function school(): BelongsTo { return $this->belongsTo(School::class); } }
