<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Billing plans for tenants, company plan_id, and company-admin account fields.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('segment');
            $table->unsignedInteger('min_employees');
            $table->unsignedInteger('max_employees')->nullable();
            $table->string('billing');
            $table->unsignedInteger('price_per_user_monthly');
            $table->unsignedInteger('price_per_user_monthly_max')->nullable();
            $table->unsignedInteger('minimum_ticket');
            $table->string('minimum_ticket_period');
            $table->timestamps();
        });

        $now = now();

        DB::table('plans')->insert([
            [
                'code' => 'micro',
                'segment' => '1 a 9 empleados (Micro)',
                'min_employees' => 1,
                'max_employees' => 9,
                'billing' => 'annual',
                'price_per_user_monthly' => 20,
                'price_per_user_monthly_max' => null,
                'minimum_ticket' => 2400,
                'minimum_ticket_period' => 'year',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'small_business',
                'segment' => '10 a 49 empleados (Pyme)',
                'min_employees' => 10,
                'max_employees' => 49,
                'billing' => 'monthly_or_annual',
                'price_per_user_monthly' => 20,
                'price_per_user_monthly_max' => null,
                'minimum_ticket' => 200,
                'minimum_ticket_period' => 'month',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'enterprise',
                'segment' => '50+ empleados (Empresa)',
                'min_employees' => 50,
                'max_employees' => null,
                'billing' => 'monthly_or_annual',
                'price_per_user_monthly' => 16,
                'price_per_user_monthly_max' => 18,
                'minimum_ticket' => 800,
                'minimum_ticket_period' => 'month',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('plan_id')
                ->nullable()
                ->after('tax_id')
                ->constrained('plans')
                ->restrictOnDelete();
        });

        $plans = DB::table('plans')->pluck('id', 'code');

        foreach (DB::table('companies')->get(['id', 'max_employees', 'plan_code']) as $company) {
            $code = $this->planCodeFor((int) $company->max_employees, (string) $company->plan_code);
            DB::table('companies')->where('id', $company->id)->update([
                'plan_id' => $plans[$code],
                'plan_code' => $code,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('email');
        });

        $this->makeEmailNullable();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone']);
        });

        $this->makeEmailRequired();

        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });

        Schema::dropIfExists('plans');
    }

    private function makeEmailNullable(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    private function makeEmailRequired(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }

    private function planCodeFor(int $maxEmployees, string $current): string
    {
        if (in_array($current, ['micro', 'small_business', 'enterprise'], true)) {
            return $current;
        }

        if ($maxEmployees <= 9) {
            return 'micro';
        }

        if ($maxEmployees <= 49) {
            return 'small_business';
        }

        return 'enterprise';
    }
};
