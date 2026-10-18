<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')
            ->whereIn('billing_period', ['custom', 'semiannual'])
            ->update(['billing_period' => 'quarterly', 'duration_days' => 90]);
    }

    public function down(): void
    {
        // Pas de rollback exact possible (valeurs legacy remplacées).
    }
};
