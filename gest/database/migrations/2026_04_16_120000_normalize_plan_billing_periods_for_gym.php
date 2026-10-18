<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Les périodes trimestriel / semestriel sont retirées du modèle : les forfaits existants
     * passent en « personnalisé » pour conserver la durée en jours déjà stockée.
     */
    public function up(): void
    {
        DB::table('plans')
            ->whereIn('billing_period', ['quarterly', 'semiannual'])
            ->update(['billing_period' => 'custom']);
    }

    public function down(): void
    {
        // Impossible de restaurer trimestriel / semestriel sans historique.
    }
};
