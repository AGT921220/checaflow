<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One calculated row per employee and work date.
     * Minutes and status are written by CalculateDailyAttendance.
     */
    public function up(): void
    {
        Schema::create('daily_attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules')->restrictOnDelete();
            $table->date('work_date');
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('lunch_out_at')->nullable();
            $table->timestamp('lunch_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('lunch_minutes')->default(0);
            $table->unsignedSmallInteger('excess_lunch_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->enum('status', ['on_time', 'late', 'incomplete', 'absent']);
            $table->timestamps();

            $table->unique(
                ['employee_profile_id', 'work_date'],
                'daily_summaries_employee_date_unique',
            );
            $table->index(
                ['company_id', 'work_date', 'status'],
                'daily_summaries_company_date_status_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_attendance_summaries');
    }
};
