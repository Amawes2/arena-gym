<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Payment;
use App\Models\Plan;
use Database\Seeders\EliteFitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EliteFitInscriptionRapideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EliteFitSeeder::class);
        config(['elitefit.gym_id' => Gym::query()->value('id')]);
    }

    public function test_seance_unique_requires_only_three_fields(): void
    {
        $response = $this->postJson('/api/auth/register-rapide', [
            'prenom' => 'Aya',
            'nom' => 'Koné',
            'telephone' => '0701020304',
            'type' => 'seance_unique',
            'montant' => 0,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.membre.prenom', 'Aya')
            ->assertJsonPath('data.paiement.statut', 'pending')
            ->assertJsonPath('data.presence', null);

        $this->assertDatabaseHas('members', [
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'phone' => '0701020304',
        ]);
        $payment = Payment::query()->firstOrFail();
        $this->assertSame('pending', $payment->status->value);
        $this->assertSame(3500.0, (float) $payment->amount);
        $this->assertNull($payment->paid_at);
        $this->assertSame(0, CheckIn::query()->count());
    }

    public function test_abonnement_auto_generates_subscription(): void
    {
        $plan = Plan::query()->where('name', 'Mensuel')->firstOrFail();

        $response = $this->postJson('/api/auth/register-rapide', [
            'prenom' => 'Moussa',
            'nom' => 'Traoré',
            'telephone' => '0505060708',
            'type' => 'abonnement',
            'forfait_id' => $plan->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.abonnement.statut', 'pending');

        $this->assertDatabaseHas('subscriptions', [
            'plan_id' => $plan->id,
            'status' => 'pending',
        ]);

        $payment = Payment::query()->firstOrFail();
        $payment->update(['status' => 'completed']);

        $this->assertDatabaseHas('subscriptions', [
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_public_signup_rejects_inactive_plans(): void
    {
        $plan = Plan::query()->where('name', 'Mensuel')->firstOrFail();
        $plan->update(['is_active' => false]);

        $this->postJson('/api/auth/register-rapide', [
            'prenom' => 'Moussa',
            'nom' => 'Traore',
            'telephone' => '0505060708',
            'type' => 'abonnement',
            'forfait_id' => $plan->id,
        ])->assertNotFound();
    }

    public function test_public_signup_is_blocked_when_gym_is_suspended(): void
    {
        Gym::query()->firstOrFail()->update(['is_active' => false]);

        $this->postJson('/api/auth/register-rapide', [
            'prenom' => 'Aya',
            'nom' => 'Kone',
            'telephone' => '0701020304',
            'type' => 'seance_unique',
        ])->assertForbidden();
    }
}
