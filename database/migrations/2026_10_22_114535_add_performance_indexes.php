<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip index checks for SQLite (tests) - SHOW INDEX not supported
        $isSqlite = DB::getDriverName() === 'sqlite';

        // Teachers table - indexes for active teachers and assignments
        Schema::table('teachers', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('teachers', 'teachers_school_user_idx')) {
                $table->index(['school_id', 'user_id'], 'teachers_school_user_idx');
            }
        });

        // Timetables table - additional indexes for dashboard queries
        Schema::table('timetables', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('timetables', 'timetables_school_year_day_idx')) {
                $table->index(['school_id', 'academic_year_id', 'day_of_week'], 'timetables_school_year_day_idx');
            }
            if ($isSqlite || ! $this->indexExists('timetables', 'timetables_teacher_day_idx')) {
                $table->index(['school_id', 'teacher_id', 'day_of_week'], 'timetables_teacher_day_idx');
            }
        });

        // Evaluations table - indexes for dashboard and results
        Schema::table('evaluations', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('evaluations', 'evaluations_teacher_status_idx')) {
                $table->index(['school_id', 'teacher_id', 'status'], 'evaluations_teacher_status_idx');
            }
            if ($isSqlite || ! $this->indexExists('evaluations', 'evaluations_class_period_status_idx')) {
                $table->index(['school_id', 'class_id', 'academic_period_id', 'status'], 'evaluations_class_period_status_idx');
            }
        });

        // Evaluation results - indexes for results calculation
        Schema::table('evaluation_results', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('evaluation_results', 'eval_results_school_eval_student_idx')) {
                $table->index(['school_id', 'evaluation_id', 'student_id'], 'eval_results_school_eval_student_idx');
            }
            if ($isSqlite || ! $this->indexExists('evaluation_results', 'eval_results_student_absent_idx')) {
                $table->index(['school_id', 'student_id', 'is_absent'], 'eval_results_student_absent_idx');
            }
        });

        // Attendance records - indexes for dashboard incidents
        Schema::table('attendance_records', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('attendance_records', 'attendance_school_date_status_idx')) {
                $table->index(['school_id', 'attendance_date', 'status'], 'attendance_school_date_status_idx');
            }
            if ($isSqlite || ! $this->indexExists('attendance_records', 'attendance_student_date_idx')) {
                $table->index(['school_id', 'student_id', 'attendance_date'], 'attendance_student_date_idx');
            }
        });

        // Report cards - indexes for validation dashboard
        Schema::table('report_cards', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('report_cards', 'report_cards_status_class_idx')) {
                $table->index(['school_id', 'status', 'class_room_id'], 'report_cards_status_class_idx');
            }
        });

        // Student contributions - indexes for finance dashboard
        Schema::table('student_contributions', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('student_contributions', 'contributions_school_year_status_idx')) {
                $table->index(['school_id', 'academic_year_id', 'status'], 'contributions_school_year_status_idx');
            }
            if ($isSqlite || ! $this->indexExists('student_contributions', 'contributions_student_amounts_idx')) {
                $table->index(['school_id', 'student_id', 'amount_due', 'amount_paid'], 'contributions_student_amounts_idx');
            }
        });

        // Expenses - indexes for finance dashboard
        Schema::table('expenses', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('expenses', 'expenses_school_status_date_idx')) {
                $table->index(['school_id', 'status', 'expense_date'], 'expenses_school_status_date_idx');
            }
        });

        // Sales - indexes for finance dashboard
        Schema::table('sales', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('sales', 'sales_school_date_category_idx')) {
                $table->index(['school_id', 'sold_at', 'category'], 'sales_school_date_category_idx');
            }
        });

        // Notifications - indexes for notification logs
        Schema::table('notifications', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('notifications', 'notifications_school_status_provider_idx')) {
                $table->index(['school_id', 'status', 'provider'], 'notifications_school_status_provider_idx');
            }
        });

        // Class rooms - index for primary classes (by level)
        Schema::table('classes', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('classes', 'classes_school_level_idx')) {
                $table->index(['school_id', 'level'], 'classes_school_level_idx');
            }
        });

        // Academic periods - index for current period queries
        Schema::table('academic_periods', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || ! $this->indexExists('academic_periods', 'periods_school_year_closed_idx')) {
                $table->index(['school_id', 'academic_year_id', 'is_closed'], 'periods_school_year_closed_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        Schema::table('teachers', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('teachers', 'teachers_school_user_idx')) {
                $table->dropIndex('teachers_school_user_idx');
            }
        });

        Schema::table('timetables', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('timetables', 'timetables_school_year_day_idx')) {
                $table->dropIndex('timetables_school_year_day_idx');
            }
            if ($isSqlite || $this->indexExists('timetables', 'timetables_teacher_day_idx')) {
                $table->dropIndex('timetables_teacher_day_idx');
            }
        });

        Schema::table('evaluations', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('evaluations', 'evaluations_teacher_status_idx')) {
                $table->dropIndex('evaluations_teacher_status_idx');
            }
            if ($isSqlite || $this->indexExists('evaluations', 'evaluations_class_period_status_idx')) {
                $table->dropIndex('evaluations_class_period_status_idx');
            }
        });

        Schema::table('evaluation_results', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('evaluation_results', 'eval_results_school_eval_student_idx')) {
                $table->dropIndex('eval_results_school_eval_student_idx');
            }
            if ($isSqlite || $this->indexExists('evaluation_results', 'eval_results_student_absent_idx')) {
                $table->dropIndex('eval_results_student_absent_idx');
            }
        });

        Schema::table('attendance_records', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('attendance_records', 'attendance_school_date_status_idx')) {
                $table->dropIndex('attendance_school_date_status_idx');
            }
            if ($isSqlite || $this->indexExists('attendance_records', 'attendance_student_date_idx')) {
                $table->dropIndex('attendance_student_date_idx');
            }
        });

        Schema::table('report_cards', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('report_cards', 'report_cards_status_class_idx')) {
                $table->dropIndex('report_cards_status_class_idx');
            }
        });

        Schema::table('student_contributions', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('student_contributions', 'contributions_school_year_status_idx')) {
                $table->dropIndex('contributions_school_year_status_idx');
            }
            if ($isSqlite || $this->indexExists('student_contributions', 'contributions_student_amounts_idx')) {
                $table->dropIndex('contributions_student_amounts_idx');
            }
        });

        Schema::table('expenses', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('expenses', 'expenses_school_status_date_idx')) {
                $table->dropIndex('expenses_school_status_date_idx');
            }
        });

        Schema::table('sales', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('sales', 'sales_school_date_category_idx')) {
                $table->dropIndex('sales_school_date_category_idx');
            }
        });

        Schema::table('notifications', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('notifications', 'notifications_school_status_provider_idx')) {
                $table->dropIndex('notifications_school_status_provider_idx');
            }
        });

        Schema::table('classes', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('classes', 'classes_school_level_idx')) {
                $table->dropIndex('classes_school_level_idx');
            }
        });

        Schema::table('academic_periods', function (Blueprint $table) use ($isSqlite) {
            if ($isSqlite || $this->indexExists('academic_periods', 'periods_school_year_closed_idx')) {
                $table->dropIndex('periods_school_year_closed_idx');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }
};