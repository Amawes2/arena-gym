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
        Schema::table('gyms', function (Blueprint $table) {
            $table->boolean('is_approved')->default(true)->after('is_active');
            $table->string('country_code', 2)->default('CI')->after('is_approved');
            $table->string('currency_code', 3)->default('XOF')->after('country_code');
            $table->unsignedTinyInteger('provisional_subscriptions_created')->default(0)->after('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropColumn(['is_approved', 'country_code', 'currency_code', 'provisional_subscriptions_created']);
        });
    }
};
