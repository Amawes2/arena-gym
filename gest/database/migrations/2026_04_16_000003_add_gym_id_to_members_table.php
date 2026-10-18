<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'gym_id')) {
                $table->foreignId('gym_id')->nullable()->constrained('gyms')->cascadeOnDelete()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'gym_id')) {
                $table->dropForeign(['gym_id']);
                $table->dropColumn('gym_id');
            }
        });
    }
};
