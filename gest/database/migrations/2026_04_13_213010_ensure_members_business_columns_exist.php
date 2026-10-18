<?php

use App\Enums\ClientType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certaines bases locales peuvent avoir une table `members` incomplète (ex. uniquement id + timestamps + soft deletes).
 * Cette migration aligne le schéma sur `2026_04_11_224303_create_members_table` sans casser une base déjà correcte.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('members', 'name')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->string('name')->default('');
            });
        }

        if (! Schema::hasColumn('members', 'phone')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->string('phone', 20)->nullable()->unique();
            });
        }

        if (! Schema::hasColumn('members', 'email')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->string('email')->nullable()->unique();
            });
        }

        if (! Schema::hasColumn('members', 'photo_path')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->string('photo_path')->nullable();
            });
        }

        if (! Schema::hasColumn('members', 'bio')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->text('bio')->nullable();
            });
        }

        if (! Schema::hasColumn('members', 'client_type')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->string('client_type')->default(ClientType::Subscriber->value);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
