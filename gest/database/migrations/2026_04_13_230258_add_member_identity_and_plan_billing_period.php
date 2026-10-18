<?php

use App\Enums\PlanBillingPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('members', 'first_name')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->string('first_name')->default('');
                $table->string('last_name')->default('');
                $table->string('id_document_number')->nullable();
            });
        }

        foreach (DB::table('members')->select('id', 'name')->cursor() as $row) {
            $name = trim((string) ($row->name ?? ''));

            if ($name === '') {
                DB::table('members')->where('id', $row->id)->update([
                    'first_name' => '-',
                    'last_name' => '-',
                    'name' => '-',
                ]);

                continue;
            }

            $parts = preg_split('/\s+/u', $name, 2, PREG_SPLIT_NO_EMPTY);
            if ($parts === false || $parts === []) {
                DB::table('members')->where('id', $row->id)->update([
                    'first_name' => '-',
                    'last_name' => '-',
                    'name' => '-',
                ]);

                continue;
            }

            if (count($parts) === 1) {
                $firstName = '-';
                $lastName = $parts[0];
            } else {
                $firstName = $parts[0];
                $lastName = $parts[1] ?? '';
            }

            $display = trim($firstName.' '.$lastName);

            DB::table('members')->where('id', $row->id)->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $display !== '' ? $display : '-',
            ]);
        }

        if (! Schema::hasColumn('plans', 'billing_period')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->string('billing_period')->nullable()->after('duration_days');
            });
        }

        DB::table('plans')->whereNull('billing_period')->update([
            'billing_period' => PlanBillingPeriod::Custom->value,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'billing_period')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->dropColumn('billing_period');
            });
        }

        if (Schema::hasColumn('members', 'first_name')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->dropColumn(['first_name', 'last_name', 'id_document_number']);
            });
        }
    }
};
