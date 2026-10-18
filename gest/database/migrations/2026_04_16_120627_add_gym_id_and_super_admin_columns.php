<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['members', 'plans', 'subscriptions', 'payments', 'check_ins'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'gym_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('gym_id')->nullable()->constrained('gyms')->cascadeOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['members', 'plans', 'subscriptions', 'payments', 'check_ins'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'gym_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['gym_id']);
                    $table->dropColumn('gym_id');
                });
            }
        }

        // The is_super_admin column is managed by its dedicated migration.
    }
};
