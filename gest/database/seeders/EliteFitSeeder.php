<?php

namespace Database\Seeders;

use App\Enums\PlanBillingPeriod;
use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\Plan;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EliteFitSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->create([
            'name' => 'Admin EliteFit',
            'email' => 'admin@elitefit.ci',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin->value,
            'email_verified_at' => now(),
        ]);

        $gym = Gym::query()->create([
            'name' => 'EliteFit Abidjan',
            'slug' => config('elitefit.gym_slug', 'elitefit-abidjan'),
            'owner_id' => $owner->id,
            'plan_saas' => 'single',
            'is_active' => true,
            'walk_in_price' => config('elitefit.walk_in_price', 3500),
            'expires_at' => now()->addYears(10),
        ]);

        $owner->managedGyms()->syncWithoutDetaching([$gym->id]);

        $plans = [
            ['name' => 'Séance unique', 'price' => 3500, 'duration_days' => 1, 'billing_period' => PlanBillingPeriod::Custom],
            ['name' => 'Mensuel', 'price' => 25000, 'duration_days' => 30, 'billing_period' => PlanBillingPeriod::Monthly],
            ['name' => 'Annuel', 'price' => 200000, 'duration_days' => 365, 'billing_period' => PlanBillingPeriod::Annual],
        ];

        foreach ($plans as $planData) {
            Plan::query()->create([
                'gym_id' => $gym->id,
                'name' => $planData['name'],
                'description' => $planData['name'],
                'price' => $planData['price'],
                'duration_days' => $planData['duration_days'],
                'billing_period' => $planData['billing_period'],
                'is_active' => true,
            ]);
        }

        $produits = [
            ['nom' => 'Whey Protéine 1kg', 'prix' => 18500, 'categorie' => 'Nutrition', 'featured' => true],
            ['nom' => 'Gants de sport pro', 'prix' => 7500, 'categorie' => 'Équipement', 'featured' => true],
            ['nom' => 'Tapis de sport premium', 'prix' => 12000, 'categorie' => 'Équipement', 'featured' => true],
        ];

        foreach ($produits as $p) {
            Produit::query()->create([
                'gym_id' => $gym->id,
                'slug' => Str::slug($p['nom']),
                'nom' => $p['nom'],
                'prix' => $p['prix'],
                'stock' => 50,
                'categorie' => $p['categorie'],
                'actif' => true,
                'featured' => $p['featured'],
            ]);
        }
    }
}
