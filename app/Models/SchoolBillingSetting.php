<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
class SchoolBillingSetting extends Model { use BelongsToSchool; protected $fillable=['installation_fee','annual_student_fee','coriyase_share','school_share','included_sms_per_paid_student','extra_sms_credit_unit','communication_enabled']; protected $casts=['communication_enabled'=>'boolean']; }
