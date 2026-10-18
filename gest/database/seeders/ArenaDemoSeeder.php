<?php

namespace Database\Seeders;

use App\Enums\CheckInType;
use App\Enums\ClientType;
use App\Enums\CommandeStatut;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlanBillingPeriod;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\CheckIn;
use App\Models\Commande;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Produit;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ArenaDemoSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'ArenaDemo#2026';

    /**
     * Create repeatable local demo data without touching the configured Super Admin.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Le seeder de démonstration est réservé aux environnements local et testing.');
        }

        DB::transaction(function (): void {
            $gyms = [
                ['key' => 'cocody', 'name' => 'Arena Gym Cocody'],
                ['key' => 'plateau', 'name' => 'Arena Gym Plateau'],
                ['key' => 'yopougon', 'name' => 'Arena Gym Yopougon'],
            ];

            foreach ($gyms as $gymDetails) {
                $this->seedGym($gymDetails['key'], $gymDetails['name']);
            }
        });
    }

    private function seedGym(string $key, string $name): void
    {
        $admin = $this->seedStaff($key, UserRole::Admin);
        $receptionist = $this->seedStaff($key, UserRole::Receptionist);

        $gym = Gym::updateOrCreate(
            ['slug' => 'arena-gym-'.$key],
            [
                'name' => $name,
                'owner_id' => $admin->id,
                'plan_saas' => 'pro',
                'is_active' => true,
                'expires_at' => today()->addYear(),
                'walk_in_price' => 3500,
            ],
        );

        $admin->managedGyms()->syncWithoutDetaching([$gym->id]);
        $receptionist->managedGyms()->syncWithoutDetaching([$gym->id]);

        $plans = $this->seedPlans($gym);
        $members = $this->seedMembers($gym, $key);
        $subscriptions = $this->seedSubscriptions($gym, $plans, $members);
        $this->seedPayments($gym, $members, $subscriptions);
        $this->seedCheckIns($gym, $members, $subscriptions);
        $products = $this->seedProducts($gym);
        $this->seedOrders($gym, $members, $products, $key);
    }

    private function seedStaff(string $gymKey, UserRole $role): User
    {
        $isAdmin = $role === UserRole::Admin;
        $prefix = $isAdmin ? 'admin' : 'accueil';
        $name = $isAdmin ? 'Admin '.ucfirst($gymKey) : 'Réception '.ucfirst($gymKey);

        return User::updateOrCreate(
            ['email' => $prefix.'.'.$gymKey.'@arena-demo.test'],
            [
                'name' => $name,
                'password' => self::DEMO_PASSWORD,
                'role' => $role,
                'telephone' => '+225010000'.($isAdmin ? '01' : '02'),
            ],
        );
    }

    /**
     * @return array<string, Plan>
     */
    private function seedPlans(Gym $gym): array
    {
        $definitions = [
            'mensuel' => ['name' => 'Mensuel', 'price' => 25000, 'duration' => 30, 'period' => PlanBillingPeriod::Monthly, 'sessions' => null],
            'trimestriel' => ['name' => 'Trimestriel', 'price' => 65000, 'duration' => 90, 'period' => PlanBillingPeriod::Quarterly, 'sessions' => null],
            'annuel' => ['name' => 'Annuel', 'price' => 220000, 'duration' => 365, 'period' => PlanBillingPeriod::Annual, 'sessions' => null],
            'pass-10' => ['name' => 'Pass 10 séances', 'price' => 45000, 'duration' => 45, 'period' => PlanBillingPeriod::Custom, 'sessions' => 10],
        ];

        $plans = [];

        foreach ($definitions as $key => $definition) {
            $plans[$key] = Plan::updateOrCreate(
                ['gym_id' => $gym->id, 'name' => $definition['name']],
                [
                    'description' => 'Forfait de démonstration Arena Gym.',
                    'price' => $definition['price'],
                    'duration_days' => $definition['duration'],
                    'billing_period' => $definition['period'],
                    'sessions_limit' => $definition['sessions'],
                    'is_active' => true,
                ],
            );
        }

        return $plans;
    }

    /**
     * @return array<string, Member>
     */
    private function seedMembers(Gym $gym, string $gymKey): array
    {
        $definitions = [
            'actif' => ['first' => 'Bamba', 'last' => 'Ibrahim', 'type' => ClientType::Subscriber],
            'fin-bientot' => ['first' => 'Kouadio', 'last' => 'Aïcha', 'type' => ClientType::Subscriber],
            'expire' => ['first' => 'Traoré', 'last' => 'Amadou', 'type' => ClientType::Subscriber],
            'annuel' => ['first' => 'Diarra', 'last' => 'Fatou', 'type' => ClientType::Subscriber],
            'en-attente' => ['first' => 'Koné', 'last' => 'Moussa', 'type' => ClientType::Subscriber],
            'annule' => ['first' => 'Yao', 'last' => 'Nadia', 'type' => ClientType::Subscriber],
            'pass' => ['first' => 'N Guessan', 'last' => 'Mireille', 'type' => ClientType::Subscriber],
            'passage' => ['first' => 'Coulibaly', 'last' => 'Jean', 'type' => ClientType::WalkIn],
        ];

        $phoneNumbers = [
            'actif' => 1,
            'fin-bientot' => 2,
            'expire' => 3,
            'annuel' => 4,
            'en-attente' => 5,
            'annule' => 6,
            'passage' => 7,
            'pass' => 8,
        ];
        $members = [];

        foreach ($definitions as $key => $definition) {
            $member = Member::withTrashed()->updateOrCreate(
                ['email' => $key.'.'.$gymKey.'@membres-arena.test'],
                [
                    'gym_id' => $gym->id,
                    'first_name' => $definition['first'],
                    'last_name' => $definition['last'],
                    'phone' => '+2250700'.str_pad((string) ($gym->id * 100 + $phoneNumbers[$key]), 7, '0', STR_PAD_LEFT),
                    'id_document_number' => 'DEMO-'.strtoupper($gymKey).'-'.strtoupper($key),
                    'bio' => 'Profil fictif créé pour les essais de Arena Gym.',
                    'client_type' => $definition['type'],
                ],
            );

            if ($member->trashed()) {
                $member->restore();
            }

            $members[$key] = $member;
        }

        return $members;
    }

    /**
     * @param  array<string, Plan>  $plans
     * @param  array<string, Member>  $members
     * @return array<string, Subscription>
     */
    private function seedSubscriptions(Gym $gym, array $plans, array $members): array
    {
        $today = today();
        $definitions = [
            'actif' => ['plan' => 'mensuel', 'status' => SubscriptionStatus::Active, 'end' => $today->copy()->addDays(24), 'start' => $today->copy()->subDays(6)],
            'fin-bientot' => ['plan' => 'mensuel', 'status' => SubscriptionStatus::Active, 'end' => $today->copy()->addDays(3), 'start' => $today->copy()->subDays(27)],
            'expire' => ['plan' => 'trimestriel', 'status' => SubscriptionStatus::Expired, 'end' => $today->copy()->subDays(12), 'start' => $today->copy()->subDays(102)],
            'annuel' => ['plan' => 'annuel', 'status' => SubscriptionStatus::Active, 'end' => $today->copy()->addDays(200), 'start' => $today->copy()->subDays(165)],
            'en-attente' => ['plan' => 'mensuel', 'status' => SubscriptionStatus::Pending, 'end' => $today->copy()->addDays(30), 'start' => $today],
            'annule' => ['plan' => 'trimestriel', 'status' => SubscriptionStatus::Cancelled, 'end' => $today->copy()->addDays(60), 'start' => $today->copy()->subDays(30)],
            'pass' => ['plan' => 'pass-10', 'status' => SubscriptionStatus::Active, 'end' => $today->copy()->addDays(25), 'start' => $today->copy()->subDays(20)],
        ];

        $subscriptions = [];

        foreach ($definitions as $key => $definition) {
            $subscriptions[$key] = Subscription::updateOrCreate(
                ['member_id' => $members[$key]->id],
                [
                    'gym_id' => $gym->id,
                    'plan_id' => $plans[$definition['plan']]->id,
                    'start_date' => $definition['start'],
                    'end_date' => $definition['end'],
                    'sessions_remaining' => $key === 'pass' ? 7 : null,
                    'status' => $definition['status'],
                ],
            );
        }

        return $subscriptions;
    }

    /**
     * @param  array<string, Member>  $members
     * @param  array<string, Subscription>  $subscriptions
     */
    private function seedPayments(Gym $gym, array $members, array $subscriptions): void
    {
        $definitions = [
            'actif' => ['status' => PaymentStatus::Completed, 'method' => PaymentMethod::Wave, 'days_ago' => 3],
            'fin-bientot' => ['status' => PaymentStatus::Completed, 'method' => PaymentMethod::OrangeMoney, 'days_ago' => 5],
            'expire' => ['status' => PaymentStatus::Completed, 'method' => PaymentMethod::Cash, 'days_ago' => 40],
            'annuel' => ['status' => PaymentStatus::Completed, 'method' => PaymentMethod::Card, 'days_ago' => 80],
            'en-attente' => ['status' => PaymentStatus::Pending, 'method' => PaymentMethod::MobileMoney, 'days_ago' => null],
            'annule' => ['status' => PaymentStatus::Failed, 'method' => PaymentMethod::MtnMoney, 'days_ago' => null],
            'pass' => ['status' => PaymentStatus::Completed, 'method' => PaymentMethod::MobileMoney, 'days_ago' => 2],
        ];

        foreach ($definitions as $key => $definition) {
            $paidAt = $definition['days_ago'] === null
                ? null
                : today()->subDays($definition['days_ago'])->setTime(11, 0);

            Payment::updateOrCreate(
                ['reference' => 'DEMO-'.$gym->slug.'-'.$key],
                [
                    'gym_id' => $gym->id,
                    'member_id' => $members[$key]->id,
                    'subscription_id' => $subscriptions[$key]->id,
                    'amount' => $subscriptions[$key]->plan->price,
                    'payment_method' => $definition['method'],
                    'status' => $definition['status'],
                    'paid_at' => $paidAt,
                ],
            );
        }

        Payment::updateOrCreate(
            ['reference' => 'DEMO-'.$gym->slug.'-passage'],
            [
                'gym_id' => $gym->id,
                'member_id' => $members['passage']->id,
                'subscription_id' => null,
                'amount' => $gym->walk_in_price,
                'payment_method' => PaymentMethod::Cash,
                'status' => PaymentStatus::Completed,
                'paid_at' => today()->setTime(9, 30),
            ],
        );
    }

    /**
     * @param  array<string, Member>  $members
     * @param  array<string, Subscription>  $subscriptions
     */
    private function seedCheckIns(Gym $gym, array $members, array $subscriptions): void
    {
        foreach (['actif', 'fin-bientot', 'annuel', 'pass'] as $memberKey) {
            foreach ([0, 1, 3, 5, 8] as $daysAgo) {
                $date = today()->subDays($daysAgo)->setTime(7 + ($daysAgo % 4), 30);
                $noteKey = 'DEMO-'.$memberKey.'-J'.$daysAgo;

                CheckIn::updateOrCreate(
                    ['gym_id' => $gym->id, 'member_id' => $members[$memberKey]->id, 'notes' => $noteKey],
                    [
                        'subscription_id' => $subscriptions[$memberKey]->id,
                        'type' => CheckInType::Subscription,
                        'checked_in_at' => $date,
                    ],
                );
            }
        }

        CheckIn::updateOrCreate(
            ['gym_id' => $gym->id, 'member_id' => $members['passage']->id, 'notes' => 'DEMO-visiteur-du-jour'],
            [
                'subscription_id' => null,
                'type' => CheckInType::WalkIn,
                'checked_in_at' => today()->setTime(10, 15),
            ],
        );
    }

    /**
     * @return array<string, Produit>
     */
    private function seedProducts(Gym $gym): array
    {
        $definitions = [
            'whey-protein' => ['name' => 'Whey Protein', 'price' => 35000, 'stock' => 2, 'category' => 'Nutrition'],
            'eau-500ml' => ['name' => 'Bouteille eau 500 ml', 'price' => 500, 'stock' => 40, 'category' => 'Boissons'],
            'gants-boxe' => ['name' => 'Gants de boxe', 'price' => 18000, 'stock' => 0, 'category' => 'Équipement'],
            't-shirt-arena' => ['name' => 'T-shirt Arena', 'price' => 10000, 'stock' => 8, 'category' => 'Vêtements'],
            'serviette-microfibre' => ['name' => 'Serviette microfibre', 'price' => 4500, 'stock' => 15, 'category' => 'Accessoires'],
        ];

        $products = [];

        foreach ($definitions as $slug => $definition) {
            $products[$slug] = Produit::updateOrCreate(
                ['gym_id' => $gym->id, 'slug' => $slug],
                [
                    'nom' => $definition['name'],
                    'description' => 'Article fictif pour les essais de stock et de commandes.',
                    'prix' => $definition['price'],
                    'stock' => $definition['stock'],
                    'categorie' => $definition['category'],
                    'actif' => true,
                    'featured' => $slug === 'whey-protein',
                ],
            );
        }

        return $products;
    }

    /**
     * @param  array<string, Member>  $members
     * @param  array<string, Produit>  $products
     */
    private function seedOrders(Gym $gym, array $members, array $products, string $gymKey): void
    {
        $definitions = [
            'livree' => [
                'status' => CommandeStatut::Livree,
                'member' => 'actif',
                'items' => [['product' => 'whey-protein', 'quantity' => 1], ['product' => 'eau-500ml', 'quantity' => 2]],
            ],
            'attente' => [
                'status' => CommandeStatut::EnAttente,
                'member' => 'fin-bientot',
                'items' => [['product' => 'gants-boxe', 'quantity' => 1], ['product' => 't-shirt-arena', 'quantity' => 1]],
            ],
        ];

        foreach ($definitions as $key => $definition) {
            $total = 0;

            foreach ($definition['items'] as $item) {
                $total += (float) $products[$item['product']]->prix * $item['quantity'];
            }

            $order = Commande::updateOrCreate(
                ['numero' => 'DEMO-'.$gymKey.'-'.strtoupper($key)],
                [
                    'gym_id' => $gym->id,
                    'member_id' => $members[$definition['member']]->id,
                    'nom_client' => $members[$definition['member']]->name,
                    'telephone_client' => $members[$definition['member']]->phone,
                    'montant_total' => $total,
                    'statut' => $definition['status'],
                    'mode_paiement' => $key === 'livree' ? 'wave' : null,
                ],
            );

            foreach ($definition['items'] as $item) {
                $product = $products[$item['product']];
                $subtotal = (float) $product->prix * $item['quantity'];

                $order->lignes()->updateOrCreate(
                    ['produit_id' => $product->id],
                    [
                        'nom_produit' => $product->nom,
                        'quantite' => $item['quantity'],
                        'prix_unitaire' => $product->prix,
                        'sous_total' => $subtotal,
                    ],
                );
            }
        }
    }
}
