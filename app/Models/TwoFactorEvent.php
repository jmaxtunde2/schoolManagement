<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class TwoFactorEvent extends Model { protected $fillable=['school_id','user_id','event','ip_address','user_agent']; }
