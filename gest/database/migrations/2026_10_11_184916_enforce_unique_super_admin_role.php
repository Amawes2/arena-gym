<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('users')->where('role', 'super_admin')->count() > 1) {
            throw new RuntimeException('Resolve duplicate Super Admin accounts before applying this migration.');
        }

        if (Schema::hasIndex('users', 'users_super_admin_guard_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_super_admin_guard_unique');
            });
        }

        if (Schema::hasColumn('users', 'super_admin_guard')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('super_admin_guard');
            });
        }

        if (! Schema::hasColumn('users', 'super_admin_guard')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('super_admin_guard')
                    ->nullable()
                    ->virtualAs("CASE WHEN role = 'super_admin' THEN 'only-super-admin' ELSE NULL END");
            });
        }

        if (! Schema::hasIndex('users', 'users_super_admin_guard_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('super_admin_guard');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('users', 'users_super_admin_guard_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_super_admin_guard_unique');
            });
        }

        if (Schema::hasColumn('users', 'super_admin_guard')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('super_admin_guard');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('super_admin_guard')->nullable();
        });

        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['super_admin_guard' => 'only-super-admin']);

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('super_admin_guard');
        });
    }
};
