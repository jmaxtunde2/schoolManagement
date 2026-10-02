<?php

namespace App\Providers;

use App\Models\Evaluation;
use App\Models\AttendanceRecord;
use App\Models\SchoolSetting;
use App\Policies\AttendancePolicy;
use App\Policies\EvaluationPolicy;
use App\Policies\SchoolSettingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        SchoolSetting::class => SchoolSettingPolicy::class,
        Evaluation::class => EvaluationPolicy::class,
        AttendanceRecord::class => AttendancePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
