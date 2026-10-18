<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table already created by the earlier migration.
    }

    public function down(): void
    {
        // Intentionally left empty to avoid dropping an existing pivot table.
    }
};
