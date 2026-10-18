<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_honeypot_rejects_automated_member_registration(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'prenom' => 'Barry',
            'nom' => 'Mamadou',
            'telephone' => '0701020304',
            'email' => 'barry@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'website' => 'https://spam.example',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('website');
        $this->assertDatabaseMissing('users', ['email' => 'barry@example.test']);
    }

    public function test_login_is_rate_limited_by_ip_and_email(): void
    {
        $credentials = ['email' => 'admin@example.test', 'password' => 'incorrect'];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', $credentials)->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', $credentials)->assertTooManyRequests();
    }

    public function test_turnstile_token_is_checked_with_cloudflare_before_gym_registration(): void
    {
        config()->set('services.turnstile.site_key', 'public-test-key');
        config()->set('services.turnstile.secret_key', 'private-test-key');
        config()->set('services.turnstile.hostname', 'arena.test');
        config()->set('services.resend.key', 'test-resend-key');
        config()->set('services.resend.from', 'Arena Gym <no-reply@arena.test>');

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'action' => 'gym_registration',
                'hostname' => 'arena.test',
            ]),
            'https://api.resend.com/emails' => Http::response(['id' => 'email-test-id']),
        ]);

        $response = $this->postJson('/api/auth/register-gym', [
            'gym_name' => 'Arena Fitness',
            'prenom' => 'Amsk',
            'nom' => 'Marie',
            'telephone' => '0701020304',
            'email' => 'owner@arena.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'turnstile_token' => 'valid-turnstile-token',
        ]);

        $response->assertAccepted();
        $this->assertDatabaseMissing('users', ['email' => 'owner@arena.test']);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'owner@arena.test', 'role' => 'admin']);
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'private-test-key'
            && $request['response'] === 'valid-turnstile-token'
            && $request['remoteip'] !== null);
    }

    public function test_turnstile_rejects_a_token_for_a_different_action(): void
    {
        config()->set('services.turnstile.site_key', 'public-test-key');
        config()->set('services.turnstile.secret_key', 'private-test-key');

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'action' => 'login',
                'hostname' => 'arena.test',
            ]),
        ]);

        $this->postJson('/api/auth/register-gym', [
            'gym_name' => 'Arena Fitness',
            'prenom' => 'Amsk',
            'nom' => 'Marie',
            'telephone' => '0701020304',
            'email' => 'owner@arena.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'turnstile_token' => 'wrong-action-token',
        ])->assertUnprocessable()->assertJsonValidationErrors('turnstile_token');

        $this->assertDatabaseMissing('users', ['email' => 'owner@arena.test']);
        $this->assertDatabaseMissing('pending_registrations', ['email' => 'owner@arena.test']);
    }

    public function test_magic_link_authenticates_an_admin_once_and_opens_their_gym_panel(): void
    {
        $this->fakeResend();

        $admin = User::factory()->create([
            'email' => 'admin@arena.test',
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym',
            'owner_id' => $admin->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        $this->postJson('/api/auth/magic-link', ['email' => $admin->email])
            ->assertAccepted()
            ->assertJsonPath('message', 'Si un compte administrateur correspond à cette adresse, un lien de connexion vient d’être envoyé.');

        $magicUrl = $this->sentMagicUrlFor($admin->email);
        $token = basename((string) parse_url($magicUrl, PHP_URL_PATH));
        $this->assertDatabaseHas('magic_login_tokens', [
            'user_id' => $admin->id,
            'token_hash' => hash('sha256', $token),
            'used_at' => null,
        ]);

        $this->get($magicUrl)->assertOk()->assertViewIs('auth.magic-link-confirm');

        $this->post($magicUrl)
            ->assertRedirect(url('/admin'));
        $this->assertAuthenticatedAs($admin, 'web');

        $this->post($magicUrl)->assertRedirect('/connexion?magic=invalid');
        $this->assertDatabaseMissing('magic_login_tokens', [
            'user_id' => $admin->id,
            'token_hash' => hash('sha256', $token),
            'used_at' => null,
        ]);
    }

    public function test_magic_link_cannot_bypass_two_factor_authentication(): void
    {
        $this->fakeResend();

        $admin = User::factory()->create([
            'email' => 'two-factor-admin@arena.test',
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);
        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym-two-factor',
            'owner_id' => $admin->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        Filament::setCurrentPanel('admin');
        $authenticator = AppAuthentication::make();
        $secret = $authenticator->generateSecret();
        $admin->saveAppAuthenticationSecret($secret);
        $code = $authenticator->getCurrentCode($admin, $secret);

        $this->postJson('/api/auth/magic-link', ['email' => $admin->email])->assertAccepted();

        $magicUrl = $this->sentMagicUrlFor($admin->email);
        $this->get($magicUrl)
            ->assertOk()
            ->assertSee('two_factor_code');

        $this->post($magicUrl)
            ->assertRedirect('/connexion?magic=invalid');
        $this->assertGuest('web');

        $this->post($magicUrl, ['two_factor_code' => $code])
            ->assertRedirect(url('/admin'));
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_magic_link_response_does_not_disclose_non_admin_accounts(): void
    {
        $this->fakeResend();
        $member = User::factory()->create([
            'email' => 'member@arena.test',
            'role' => UserRole::Membre,
        ]);

        $response = $this->postJson('/api/auth/magic-link', ['email' => $member->email]);

        $response->assertAccepted()
            ->assertJsonPath('message', 'Si un compte administrateur correspond à cette adresse, un lien de connexion vient d’être envoyé.');
        Http::assertNothingSent();
    }

    public function test_magic_link_supports_only_the_configured_super_admin_account(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');
        $this->fakeResend();

        $superAdmin = User::factory()->create([
            'email' => 'owner@arena.test',
            'role' => UserRole::SuperAdmin,
        ]);

        $this->postJson('/api/auth/magic-link', ['email' => $superAdmin->email])->assertAccepted();

        $magicUrl = $this->sentMagicUrlFor($superAdmin->email);
        $this->post($magicUrl)->assertRedirect(Filament::getPanel('platform')->getUrl());
        $this->assertAuthenticatedAs($superAdmin, 'web');
    }

    public function test_unverified_admin_cannot_receive_a_magic_link(): void
    {
        $this->fakeResend();

        $admin = User::factory()->create([
            'email' => 'unverified@arena.test',
            'role' => UserRole::Admin,
            'email_verified_at' => null,
        ]);
        Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym-unverified',
            'owner_id' => $admin->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        $this->postJson('/api/auth/magic-link', ['email' => $admin->email])->assertAccepted();

        Http::assertNothingSent();
    }

    public function test_magic_link_requests_are_limited_per_email(): void
    {
        $payload = ['email' => 'unknown@arena.test'];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/auth/magic-link', $payload)->assertAccepted();
        }

        $this->postJson('/api/auth/magic-link', $payload)->assertTooManyRequests();
    }

    private function fakeResend(): void
    {
        config()->set('services.resend.key', 'test-resend-key');
        config()->set('services.resend.from', 'ARENA GYM <no-reply@arena.test>');
        Http::fake(['https://api.resend.com/emails' => Http::response(['id' => 'email-test-id'])]);
    }

    private function sentMagicUrlFor(string $email): string
    {
        $magicUrl = null;

        Http::assertSent(function (ClientRequest $request) use ($email, &$magicUrl): bool {
            if ($request->url() !== 'https://api.resend.com/emails' || $request['to'] !== [$email]) {
                return false;
            }

            preg_match('/href="([^"]+)"/', $request['html'], $matches);
            $magicUrl = isset($matches[1]) ? html_entity_decode($matches[1], ENT_QUOTES) : null;

            return $magicUrl !== null;
        });

        $this->assertIsString($magicUrl);

        return $magicUrl;
    }
}
