<?php

namespace Tests\Feature;

use App\Filament\Resources\SupportMessages\Pages\ManageSupportMessages;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Produit;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\PricingEstimateService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GymAccessAndSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_unapproved_gym_can_create_only_three_member_subscriptions(): void
    {
        [$user, $gym] = $this->makeAdminGym(['is_approved' => false, 'expires_at' => null]);
        $this->actingAs($user, 'web');

        $members = Member::factory()->count(4)->create(['gym_id' => $gym->id]);
        $plan = Plan::factory()->create(['gym_id' => $gym->id]);

        foreach ($members->take(3) as $member) {
            $this->postJson('/api/abonnements', [
                'member_id' => $member->id,
                'forfait_id' => $plan->id,
                'date_debut' => today()->toDateString(),
            ])->assertCreated();
        }

        $this->postJson('/api/abonnements', [
            'member_id' => $members->last()->id,
            'forfait_id' => $plan->id,
            'date_debut' => today()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('subscription');

        $this->assertSame(3, $gym->fresh()->provisionalSubscriptionCount());
    }

    public function test_expired_gym_keeps_read_access_and_blocks_business_writes(): void
    {
        [$user, $gym] = $this->makeAdminGym(['is_approved' => true, 'expires_at' => today()->subDay()]);
        Member::factory()->create(['gym_id' => $gym->id]);

        $this->actingAs($user, 'web')
            ->getJson('/api/membres')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->postJson('/api/membres', [
            'prenom' => 'Nina',
            'nom' => 'Test',
            'telephone' => '0701020304',
        ])->assertForbidden()->assertJsonPath('read_only', true);

        $this->assertDatabaseCount('members', 1);
    }

    public function test_support_messages_are_isolated_by_gym_and_can_be_sent_in_read_only_mode(): void
    {
        [$firstAdmin, $firstGym] = $this->makeAdminGym(['expires_at' => today()->subDay()]);
        [$secondAdmin, $secondGym] = $this->makeAdminGym(['expires_at' => today()->addMonth()]);

        SupportMessage::factory()->create([
            'gym_id' => $firstGym->id,
            'user_id' => $firstAdmin->id,
            'body' => 'Question de la salle A',
        ]);
        SupportMessage::factory()->create([
            'gym_id' => $secondGym->id,
            'user_id' => $secondAdmin->id,
            'body' => 'Message confidentiel salle B',
        ]);

        $response = $this->actingAs($firstAdmin, 'web')->getJson('/api/support/messages');
        $response->assertOk()->assertSeeText('Question de la salle A')->assertDontSeeText('Message confidentiel salle B');

        $this->postJson('/api/support/messages', ['body' => 'Je souhaite de l’aide.'])
            ->assertCreated();

        $this->getJson('/api/support/messages?gym_id='.$secondGym->id)->assertNotFound();
        $this->assertDatabaseHas('support_messages', [
            'gym_id' => $firstGym->id,
            'user_id' => $firstAdmin->id,
            'body' => 'Je souhaite de l’aide.',
        ]);
    }

    public function test_renewal_request_is_manual_and_keeps_data_in_read_only_mode(): void
    {
        [$user, $gym] = $this->makeAdminGym(['is_approved' => true, 'expires_at' => today()->subDay()]);

        $this->actingAs($user, 'web')
            ->getJson('/api/billing/status')
            ->assertOk()
            ->assertJsonPath('gym.is_read_only', true)
            ->assertJsonPath('price.base_amount', 7999)
            ->assertJsonPath('price.currency_code', 'XOF');

        $this->postJson('/api/billing/renewal-request')->assertCreated();
        $this->assertDatabaseHas('support_messages', [
            'gym_id' => $gym->id,
            'category' => 'renewal',
            'sender_role' => 'admin',
        ]);
        $this->assertTrue($gym->fresh()->isSaasReadOnly());
    }

    public function test_price_estimate_uses_reference_rates_and_euro_parity(): void
    {
        Cache::flush();
        Http::fake([
            'https://api.frankfurter.dev/v2/rate/eur/usd' => Http::response([
                'base' => 'EUR',
                'quote' => 'USD',
                'rate' => 1.1,
                'date' => '2026-10-17',
            ]),
            'https://api.frankfurter.dev/v2/rate/eur/gbp' => Http::response([], 503),
        ]);

        $gym = Gym::factory()->create(['currency_code' => 'USD', 'country_code' => 'US']);
        $estimate = app(PricingEstimateService::class)->forGym($gym);

        $this->assertSame('USD', $estimate['currency_code']);
        $this->assertEqualsWithDelta((7999 / 655.957) * 1.1, $estimate['local_amount'], 0.01);
        $this->assertTrue($estimate['estimate']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.frankfurter.dev/v2/rate/eur/usd');

        $euroGym = Gym::factory()->create(['currency_code' => 'EUR', 'country_code' => 'FR']);
        $euroEstimate = app(PricingEstimateService::class)->forGym($euroGym);
        $this->assertSame('parity', $euroEstimate['rate_source']);
        $this->assertEqualsWithDelta(12.19, $euroEstimate['local_amount'], 0.01);

        $unavailableGym = Gym::factory()->create(['currency_code' => 'GBP', 'country_code' => 'GB']);
        $unavailableEstimate = app(PricingEstimateService::class)->forGym($unavailableGym);
        $this->assertNull($unavailableEstimate['local_amount']);
        $this->assertSame('unavailable', $unavailableEstimate['rate_source']);
    }

    public function test_tenant_cannot_request_another_gyms_products(): void
    {
        [$user, $gym] = $this->makeAdminGym();
        [, $otherGym] = $this->makeAdminGym();

        Produit::query()->create([
            'gym_id' => $gym->id,
            'nom' => 'Produit salle A',
            'prix' => 1000,
            'stock' => 2,
            'categorie' => 'Fitness',
            'actif' => true,
        ]);
        Produit::query()->create([
            'gym_id' => $otherGym->id,
            'nom' => 'Produit privé B',
            'prix' => 1000,
            'stock' => 2,
            'categorie' => 'Fitness',
            'actif' => true,
        ]);

        $this->actingAs($user, 'web')->getJson('/api/produits')
            ->assertOk()
            ->assertSeeText('Produit salle A')
            ->assertDontSeeText('Produit privé B');

        $this->getJson('/api/produits?gym_id='.$otherGym->id)->assertNotFound();
    }

    public function test_onboarding_completion_is_saved_for_the_authenticated_user(): void
    {
        [$user] = $this->makeAdminGym();

        $this->actingAs($user, 'web')->postJson('/api/onboarding/complete')->assertOk();

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
    }

    public function test_only_the_configured_super_admin_can_open_the_support_inbox(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');
        $superAdmin = User::factory()->create([
            'email' => 'owner@arena.test',
            'role' => 'super_admin',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($superAdmin, 'web')->get('/super-admin/support-messages')->assertOk();
        $this->actingAs($admin, 'web')->get('/super-admin/support-messages')->assertRedirect();
        $this->assertGuest('web');
    }

    public function test_super_admin_can_reply_to_a_gym_support_message(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');
        $superAdmin = User::factory()->create([
            'email' => 'owner@arena.test',
            'role' => 'super_admin',
        ]);
        [$admin, $gym] = $this->makeAdminGym();
        $message = SupportMessage::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $admin->id,
            'body' => 'Question au Super Admin',
        ]);
        Filament::setCurrentPanel('platform');

        Livewire::actingAs($superAdmin)->test(ManageSupportMessages::class)
            ->callTableAction('reply', $message, ['body' => 'Voici la réponse.']);

        $this->assertDatabaseHas('support_messages', [
            'gym_id' => $gym->id,
            'user_id' => $superAdmin->id,
            'sender_role' => 'super_admin',
            'body' => 'Voici la réponse.',
        ]);
    }

    /** @return array{User, Gym} */
    private function makeAdminGym(array $gymAttributes = []): array
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $gym = Gym::factory()->create([
            'owner_id' => $user->id,
            ...$gymAttributes,
        ]);

        return [$user, $gym];
    }
}
