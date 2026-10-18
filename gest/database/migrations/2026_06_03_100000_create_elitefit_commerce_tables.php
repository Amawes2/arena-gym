<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->decimal('prix', 12, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->string('categorie')->default('Général');
            $table->string('image')->nullable();
            $table->boolean('actif')->default(true);
            $table->boolean('featured')->default(false);
            $table->timestamps();

            $table->unique(['gym_id', 'slug']);
        });

        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('numero')->unique();
            $table->string('nom_client');
            $table->string('telephone_client', 20);
            $table->decimal('montant_total', 12, 2);
            $table->string('statut')->default('en_attente');
            $table->string('mode_paiement')->nullable();
            $table->timestamps();
        });

        Schema::create('commande_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->foreignId('produit_id')->nullable()->constrained('produits')->nullOnDelete();
            $table->string('nom_produit');
            $table->unsignedInteger('quantite');
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('sous_total', 12, 2);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('admin')->after('password');
            }
            if (! Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone', 20)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_lignes');
        Schema::dropIfExists('commandes');
        Schema::dropIfExists('produits');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'telephone')) {
                $table->dropColumn('telephone');
            }
        });
    }
};
