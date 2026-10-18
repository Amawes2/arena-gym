<?php

namespace Tests\Feature;

use App\Enums\CheckInType;
use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMemberAndCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_a_member_in_their_gym(): void
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

        $response = $this->actingAs($admin, 'web')->postJson('/api/membres', [
            'prenom' => 'Awa',
            'nom' => 'Diallo',
            'telephone' => '+225 01 02 03 04',
            'email' => 'AWA@EXAMPLE.TEST',
            'type' => 'walk_in',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.prenom', 'Awa')
            ->assertJsonPath('data.telephone', '+22501020304')
            ->assertJsonPath('data.email', 'awa@example.test');

        $member = Member::query()->withoutGlobalScopes()->findOrFail($response->json('data.id'));

        $this->putJson("/api/membres/{$member->id}", [
            'prenom' => 'Awa-Marie',
            'nom' => 'Diallo',
            'telephone' => '01020304',
            'email' => null,
        ])
            ->assertOk()
            ->assertJsonPath('data.prenom', 'Awa-Marie')
            ->assertJsonPath('data.email', null);

        $this->assertSame((int) $gym->id, (int) $member->fresh()->gym_id);
    }

    public function test_admin_can_check_in_a_member_and_consume_a_subscription_session(): void
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
        $member = Member::factory()->create([
            'gym_id' => $gym->id,
            'client_type' => 'subscriber',
        ]);
        $plan = Plan::factory()->monthly()->create([
            'gym_id' => $gym->id,
            'sessions_limit' => 5,
        ]);
        $subscription = Subscription::factory()->active()->create([
            'gym_id' => $gym->id,
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'sessions_remaining' => 5,
        ]);

        $response = $this->actingAs($admin, 'web')->postJson('/api/presences', [
            'member_id' => $member->id,
            'notes' => 'Entrée du matin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Présence enregistrée.')
            ->assertJsonPath('data.type', CheckInType::Subscription->value)
            ->assertJsonPath('data.membre.id', $member->id)
            ->assertJsonPath('data.forfait', $plan->name);

        $this->assertDatabaseHas('check_ins', [
            'gym_id' => $gym->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'type' => CheckInType::Subscription->value,
            'notes' => 'Entrée du matin',
        ]);
        $this->assertSame(4, $subscription->fresh()->sessions_remaining);
    }

    public function test_admin_cannot_check_in_a_member_from_another_gym(): void
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
        $otherOwner = User::factory()->create(['role' => UserRole::Admin]);
        $otherGym = Gym::query()->create([
            'name' => 'Other Gym',
            'slug' => 'other-gym',
            'owner_id' => $otherOwner->id,
            'plan_saas' => 'basic',
            'is_active' => true,
            'expires_at' => now()->addMonth(),
        ]);
        $member = Member::factory()->create(['gym_id' => $otherGym->id]);

        $this->actingAs($admin, 'web')
            ->postJson('/api/presences', ['member_id' => $member->id])
            ->assertNotFound();

        $this->assertDatabaseMissing('check_ins', [
            'gym_id' => $gym->id,
            'member_id' => $member->id,
        ]);
    }
}
