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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['super_admin_guard']);
            $table->dropColumn('super_admin_guard');
        });
    }
};
