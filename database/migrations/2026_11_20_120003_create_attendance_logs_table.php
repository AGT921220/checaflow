<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One punch of the daily cycle: clock_in, lunch_out, lunch_in, clock_out.
     * Duplicate punches are rejected by the attendance application service.
     * The lookup index is not unique, matching the agreed schema.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->date('work_date');
            $table->enum('punch_type', ['clock_in', 'lunch_out', 'lunch_in', 'clock_out']);
            $table->timestamp('punched_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_within_geofence')->nullable();
            $table->enum('source', ['mobile_app', 'web', 'manual_adjustment']);
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'work_date'], 'attendance_logs_company_date_index');
            $table->index(
                ['employee_profile_id', 'work_date', 'punch_type'],
                'attendance_logs_employee_date_punch_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
