<?php
namespace App\Services\Billing;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentContribution;
use Illuminate\Support\Collection;
class BillingService { public function settings(School $school): object { return \App\Models\SchoolBillingSetting::withoutGlobalScopes()->firstOrCreate(['school_id'=>$school->id], ['installation_fee'=>30000,'annual_student_fee'=>1000,'coriyase_share'=>700,'school_share'=>300,'included_sms_per_paid_student'=>6,'extra_sms_credit_unit'=>100,'communication_enabled'=>true]); } public function summary(School $school, ?int $yearId=null): array { $yearId ??= $school->academicYears()->where('is_current',true)->value('id'); $q=StudentContribution::withoutGlobalScopes()->where('school_id',$school->id)->when($yearId,fn($q)=>$q->where('academic_year_id',$yearId)); $settings=$this->settings($school); $paid=(clone $q)->where('status','paid')->count(); $collected=(int)(clone $q)->sum('amount_paid'); return ['students'=>(int)Student::withoutGlobalScopes()->where('school_id',$school->id)->where('is_active',true)->count(),'paid_contributions'=>$paid,'collected'=>$collected,'coriyase_share'=>(int)round($paid*$settings->coriyase_share),'school_share'=>(int)round($paid*$settings->school_share),'included_sms'=>$paid*$settings->included_sms_per_paid_student]; } }
