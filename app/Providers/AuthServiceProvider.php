<?php

namespace App\Providers;

use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\ReportCard;
use App\Models\SchoolSetting;
use App\Models\Timetable;
use App\Policies\AcademicResultPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\EvaluationPolicy;
use App\Policies\ReportCardPolicy;
use App\Policies\SchoolSettingPolicy;
use App\Policies\TimetablePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        SchoolSetting::class => SchoolSettingPolicy::class,
        Evaluation::class => EvaluationPolicy::class,
        AttendanceRecord::class => AttendancePolicy::class,
        ClassRoom::class => AcademicResultPolicy::class,
        ReportCard::class => ReportCardPolicy::class,
        Timetable::class => TimetablePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
