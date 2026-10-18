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
        Schema::table('gyms', function (Blueprint $table): void {
            $table->index('created_at', 'gyms_dashboard_created_at_idx');
            $table->index('country_code', 'gyms_dashboard_country_code_idx');
            $table->index(['is_active', 'is_approved', 'expires_at'], 'gyms_dashboard_status_expiry_idx');
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->index('created_at', 'members_dashboard_created_at_idx');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index(['role', 'created_at'], 'users_dashboard_role_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table): void {
            $table->dropIndex('gyms_dashboard_created_at_idx');
            $table->dropIndex('gyms_dashboard_country_code_idx');
            $table->dropIndex('gyms_dashboard_status_expiry_idx');
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex('members_dashboard_created_at_idx');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_dashboard_role_created_at_idx');
        });
    }
};
