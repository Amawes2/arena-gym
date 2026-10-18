<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthRecoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.resend.key', 'test-resend-key');
        config()->set('services.resend.from', 'Arena Gym <no-reply@arena.test>');
        Http::fake(['https://api.resend.com/emails' => Http::response(['id' => 'email-test-id'])]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->string('super_admin_guard')->nullable()->unique();
            $table->string('telephone')->nullable();
            $table->longText('app_authentication_secret')->nullable();
            $table->longText('app_authentication_recovery_codes')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('email_verification_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('pending_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 254)->unique();
            $table->string('role', 32);
            $table->longText('registration_data');
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('code_expires_at')->index();
            $table->timestamp('registration_expires_at')->index();
            $table->timestamps();
        });

        Schema::create('gyms', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('plan_saas')->default('basic');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->string('country_code', 2)->default('CI');
            $table->string('currency_code', 3)->default('XOF');
            $table->unsignedTinyInteger('provisional_subscriptions_created')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->float('walk_in_price')->default(0);
            $table->timestamps();
        });

        Schema::create('gym_user', function (Blueprint $table): void {
            $table->foreignId('gym_id')->constrained('gyms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['gym_id', 'user_id']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_public_registration_cannot_create_a_super_admin_or_gym_and_requires_email_verification(): void
    {
        config()->set('services.resend.key', 'test-resend-key');
        config()->set('services.resend.from', 'Arena Gym <no-reply@arena.test>');
        Http::fake(['https://api.resend.com/emails' => Http::response(['id' => 'email-test-id'])]);

        $response = $this->postJson('/api/auth/register', [
            'prenom' => 'Barry',
            'nom' => 'Mamadou',
            'telephone' => '0701020304',
            'email' => 'barry@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'super_admin',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('email', 'barry@example.com')
            ->assertJsonMissingPath('token')
            ->assertJsonStructure(['message', 'email']);
        $this->assertDatabaseMissing('users', ['email' => 'barry@example.com']);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'barry@example.com', 'role' => 'membre']);
        $this->assertDatabaseCount('email_verification_codes', 0);
        $this->assertSame(0, DB::table('gyms')->count());
        $this->assertGuest('web');
    }

    public function test_gym_registration_creates_an_admin_and_gym_and_returns_a_panel_redirect(): void
    {
        $response = $this->postJson('/api/auth/register-gym', [
            'gym_name' => 'Arena Fitness',
            'prenom' => 'Amsk',
            'nom' => 'Marie',
            'telephone' => '0701020304',
            'email' => 'amsk@arena.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertAccepted()
            ->assertJsonPath('email', 'amsk@arena.test')
            ->assertJsonMissingPath('token')
            ->assertJsonMissingPath('redirect_url');

        $this->assertDatabaseMissing('users', ['email' => 'amsk@arena.test']);
        $this->assertDatabaseCount('gyms', 0);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'amsk@arena.test', 'role' => 'admin']);
        $this->assertDatabaseCount('email_verification_codes', 0);
        $this->assertGuest('web');

        $code = $this->verificationCodeFor('amsk@arena.test');
        $verification = $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'amsk@arena.test',
            'code' => $code,
        ]);
        $verification->assertOk();

        $user = User::query()->where('email', 'amsk@arena.test')->firstOrFail();
        $gym = $user->gyms()->firstOrFail();
        $this->assertSame($user->id, $gym->owner_id);
        $this->assertSame('arena-fitness', $gym->slug);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertTrue($user->canAccessGym((int) $gym->id));
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('pending_registrations', ['email' => $user->email]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $verification->assertJsonPath('redirect_url', url('/admin'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_gym_registration_generates_a_unique_slug(): void
    {
        $firstResponse = $this->postJson('/api/auth/register-gym', [
            'gym_name' => 'Arena Fitness',
            'prenom' => 'Amsk',
            'nom' => 'Marie',
            'telephone' => '0701020304',
            'email' => 'first@arena.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $firstResponse->assertAccepted();
        $firstCode = $this->verificationCodeFor('first@arena.test');
        $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'first@arena.test',
            'code' => $firstCode,
        ])->assertOk();

        $secondResponse = $this->postJson('/api/auth/register-gym', [
            'gym_name' => 'Arena Fitness',
            'prenom' => 'Aminata',
            'nom' => 'Coulibaly',
            'telephone' => '0701020305',
            'email' => 'second@arena.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $secondResponse->assertAccepted();
        $secondCode = $this->verificationCodeFor('second@arena.test');
        $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'second@arena.test',
            'code' => $secondCode,
        ])->assertOk();
        $this->assertDatabaseHas('gyms', ['slug' => 'arena-fitness-2']);
    }

    public function test_admin_login_redirects_to_their_filament_tenant_and_creates_a_session(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@arena.test',
            'password' => 'Password123!',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym',
            'owner_id' => $user->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);
        $otherOwner = User::factory()->create(['role' => 'admin']);
        $managedGym = Gym::query()->create([
            'name' => 'Arena Gym Annex',
            'slug' => 'arena-gym-annex',
            'owner_id' => $otherOwner->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);
        $user->managedGyms()->attach($managedGym);
        $unrelatedOwner = User::factory()->create(['role' => 'admin']);
        Gym::query()->create([
            'name' => 'Other Gym',
            'slug' => 'other-gym',
            'owner_id' => $unrelatedOwner->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@arena.test',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonMissingPath('token')
            ->assertJsonPath('redirect_url', url('/admin'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonCount(2, 'gyms')
            ->assertJsonPath('gyms.0.id', $gym->id)
            ->assertJsonPath('gyms.0.name', $gym->name)
            ->assertJsonPath('gyms.1.id', $managedGym->id);
    }

    public function test_admin_login_accepts_a_single_use_authenticator_code_when_two_factor_is_enabled(): void
    {
        $user = User::factory()->create([
            'email' => 'two-factor@arena.test',
            'password' => 'Password123!',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym-two-factor',
            'owner_id' => $user->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        Filament::setCurrentPanel('admin');
        $authenticator = AppAuthentication::make();
        $secret = $authenticator->generateSecret();
        $user->saveAppAuthenticationSecret($secret);
        $code = $authenticator->getCurrentCode($user, $secret);

        $this->postJson('/api/auth/login', [
            'email' => 'TWO-FACTOR@ARENA.TEST',
            'password' => 'Password123!',
            'two_factor_code' => $code,
        ])
            ->assertOk()
            ->assertJsonPath('redirect_url', url('/admin'));

        $this->postJson('/api/auth/login', [
            'email' => 'two-factor@arena.test',
            'password' => 'Password123!',
            'two_factor_code' => $code,
        ])->assertUnprocessable()->assertJsonValidationErrors('two_factor_code');
    }

    public function test_unverified_gym_admin_cannot_log_in_with_a_password(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified-admin@arena.test',
            'password' => 'Password123!',
            'role' => 'admin',
        ]);
        Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym-unverified',
            'owner_id' => $user->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertForbidden()
            ->assertJsonPath('verification_required', true)
            ->assertJsonPath('email', $user->email);

        $this->assertGuest('web');
    }

    public function test_configured_super_admin_is_exempt_from_user_email_verification(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');
        $superAdmin = User::factory()->unverified()->create([
            'email' => 'owner@arena.test',
            'password' => 'Password123!',
            'role' => 'super_admin',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $superAdmin->email,
            'password' => 'Password123!',
        ])->assertOk()
            ->assertJsonPath('redirect_url', Filament::getPanel('platform')->getUrl());

        $this->assertAuthenticatedAs($superAdmin, 'web');
    }

    public function test_security_headers_are_added_to_public_pages(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_protected_api_routes_do_not_accept_personal_access_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'api-token@arena.test',
            'role' => 'admin',
        ]);
        $token = $user->createToken('external-test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/stats')
            ->assertUnauthorized();
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'prenom' => 'Barry',
            'nom' => 'Mamadou',
            'telephone' => '0701020304',
            'email' => 'barry@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_admin_panel_exposes_recovery_routes(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNull($routes->getByName('verification.challenge'));
        $this->assertNotNull($routes->getByName('filament.admin.auth.password-reset.request'));
        $this->assertNotNull($routes->getByName('filament.admin.auth.password-reset.reset'));
        $this->assertNotNull($routes->getByName('filament.admin.auth.profile'));
    }

    private function verificationCodeFor(string $email): string
    {
        $code = null;

        Http::assertSent(function (ClientRequest $request) use ($email, &$code): bool {
            if ($request->url() !== 'https://api.resend.com/emails' || $request['to'] !== [$email]) {
                return false;
            }

            preg_match('/<strong>(\d{6})<\/strong>/', $request['html'], $matches);
            $code = $matches[1] ?? null;

            return $code !== null;
        });

        $this->assertIsString($code);

        return $code;
    }
}
