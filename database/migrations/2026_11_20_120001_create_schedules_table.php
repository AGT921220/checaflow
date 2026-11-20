<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expected workday for a company. working_days stores lowercase English
     * weekday names, for example ["monday","tuesday","wednesday","thursday","friday"].
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->time('clock_in_time');
            $table->time('clock_out_time');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(10);
            $table->unsignedSmallInteger('allowed_lunch_minutes')->default(60);
            $table->json('working_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active'], 'schedules_company_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
