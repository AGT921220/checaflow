<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the old generic web source into mobile_web and web_admin.
     * The create migration already ran, so the enum changes here.
     */
    public function up(): void
    {
        if ($this->usesMysqlEnum()) {
            $this->modifySource([
                'mobile_web',
                'mobile_app',
                'web',
                'web_admin',
                'manual_adjustment',
            ]);

            DB::table('attendance_logs')->where('source', 'web')->update(['source' => 'mobile_web']);

            $this->modifySource([
                'mobile_web',
                'mobile_app',
                'web_admin',
                'manual_adjustment',
            ]);

            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        $this->replaceSqliteSource([
            'mobile_web',
            'mobile_app',
            'web_admin',
            'manual_adjustment',
        ], [
            'web' => 'mobile_web',
        ]);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->replaceSqliteSource([
                'mobile_app',
                'web',
                'manual_adjustment',
            ], [
                'mobile_web' => 'web',
                'web_admin' => 'web',
            ]);

            return;
        }

        if (! $this->usesMysqlEnum()) {
            return;
        }

        $this->modifySource([
            'mobile_web',
            'mobile_app',
            'web',
            'web_admin',
            'manual_adjustment',
        ]);

        DB::table('attendance_logs')
            ->whereIn('source', ['mobile_web', 'web_admin'])
            ->update(['source' => 'web']);

        $this->modifySource([
            'mobile_app',
            'web',
            'manual_adjustment',
        ]);
    }

    private function usesMysqlEnum(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * @param  list<string>  $values
     */
    private function modifySource(array $values): void
    {
        $enum = implode("', '", $values);

        DB::statement("ALTER TABLE attendance_logs MODIFY source ENUM('{$enum}') NOT NULL");
    }

    /**
     * SQLite stores enum as a check constraint. Tests rebuild that constraint
     * because the create migration already ran with the old source list.
     *
     * @param  list<string>  $allowed
     * @param  array<string, string>  $rename
     */
    private function replaceSqliteSource(array $allowed, array $rename): void
    {
        if (DB::table('attendance_logs')->count() === 0) {
            Schema::table('attendance_logs', function (Blueprint $table): void {
                $table->dropColumn('source');
            });

            Schema::table('attendance_logs', function (Blueprint $table) use ($allowed): void {
                $table->enum('source', $allowed);
            });

            return;
        }

        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->string('source_expanded', 32)->nullable();
        });

        foreach ($rename as $from => $to) {
            DB::table('attendance_logs')->where('source', $from)->update(['source_expanded' => $to]);
        }

        DB::table('attendance_logs')->whereNull('source_expanded')->update([
            'source_expanded' => DB::raw('source'),
        ]);

        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->dropColumn('source');
        });

        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->string('source', 32)->nullable();
        });

        DB::table('attendance_logs')->update([
            'source' => DB::raw('source_expanded'),
        ]);

        Schema::table('attendance_logs', function (Blueprint $table): void {
            $table->dropColumn('source_expanded');
        });
    }
};
