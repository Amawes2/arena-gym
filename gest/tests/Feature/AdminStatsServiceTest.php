<?php

namespace Tests\Feature;

use App\Enums\ClientType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_delta_counts_new_active_members_not_walk_ins_or_pending_signups(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym',
            'owner_id' => $admin->id,
            'plan_saas' => 'basic',
            'is_active' => true,
            'expires_at' => now()->addMonth(),
        ]);
        $admin->managedGyms()->attach($gym);

        $plan = Plan::query()->create([
            'gym_id' => $gym->id,
            'name' => 'Mensuel',
            'price' => 25000,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $activeMember = $this->createMember($gym, '0700000001');
        $pendingMember = $this->createMember($gym, '0700000002');
        $this->createMember($gym, '0700000003', ClientType::WalkIn);

        foreach ([
            [$activeMember, SubscriptionStatus::Active],
            [$pendingMember, SubscriptionStatus::Pending],
        ] as [$member, $status]) {
            Subscription::query()->create([
                'gym_id' => $gym->id,
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'start_date' => today()->toDateString(),
                'end_date' => today()->addDays(30)->toDateString(),
                'status' => $status,
            ]);
        }

        $this->actingAs($admin, 'web')
            ->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('kpis.membres_actifs', 1)
            ->assertJsonPath('kpis.membres_actifs_delta', 1);
    }

    private function createMember(Gym $gym, string $phone, ClientType $clientType = ClientType::Subscriber): Member
    {
        return Member::query()->create([
            'gym_id' => $gym->id,
            'first_name' => 'Aya',
            'last_name' => 'Kone',
            'phone' => $phone,
            'client_type' => $clientType,
        ]);
    }
}
