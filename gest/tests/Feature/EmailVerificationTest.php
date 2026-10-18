<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\EmailVerificationService;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.resend.key', 'test-resend-key');
        config()->set('services.resend.from', 'Arena Gym <no-reply@arena.test>');
        Http::fake(['https://api.resend.com/emails' => Http::response(['id' => 'email-test-id'])]);
    }

    public function test_member_registration_sends_a_hashed_one_time_code_and_verification_starts_a_session(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'prenom' => 'Barry',
            'nom' => 'Mamadou',
            'telephone' => '0701020304',
            'email' => 'barry@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertAccepted()->assertJsonPath('email', 'barry@example.test');
        $this->assertGuest('web');

        $this->assertDatabaseMissing('users', ['email' => 'barry@example.test']);
        $pendingRegistration = PendingRegistration::query()->where('email', 'barry@example.test')->firstOrFail();
        $storedData = $pendingRegistration->getRawOriginal('registration_data');
        $this->assertIsString($storedData);
        $this->assertStringNotContainsString('Password123!', $storedData);
        $this->assertSame(0, $pendingRegistration->attempts);

        $sentCode = null;
        Http::assertSent(function (ClientRequest $request) use (&$sentCode): bool {
            preg_match('/<strong>(\d{6})<\/strong>/', $request['html'], $matches);
            $sentCode = $matches[1] ?? null;

            return $request->url() === 'https://api.resend.com/emails'
                && $request['to'] === ['barry@example.test']
                && $request['subject'] === 'Confirmez votre adresse e-mail - Arena Gym'
                && $sentCode !== null;
        });

        $this->assertIsString($sentCode);
        $this->assertNotSame($sentCode, $pendingRegistration->code_hash);

        $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'barry@example.test',
            'code' => $sentCode,
        ])->assertOk()->assertJsonPath('redirect_url', '/');

        $user = User::query()->where('email', 'barry@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('pending_registrations', ['email' => $user->email]);
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
    }

    public function test_incorrect_pending_code_sends_a_new_code_without_creating_the_account(): void
    {
        $this->postJson('/api/auth/register', [
            'prenom' => 'Barry',
            'nom' => 'Mamadou',
            'telephone' => '0701020304',
            'email' => 'barry@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertAccepted();

        $originalCode = $this->sentCode();
        $wrongCode = str_pad((string) (((int) $originalCode + 1) % 1000000), 6, '0', STR_PAD_LEFT);

        $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'barry@example.test',
            'code' => $wrongCode,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseMissing('users', ['email' => 'barry@example.test']);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'barry@example.test']);
        $newCode = $this->sentCodeDifferentFrom($originalCode);

        $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'barry@example.test',
            'code' => $newCode,
        ])->assertOk()->assertJsonPath('redirect_url', '/');

        $user = User::query()->where('email', 'barry@example.test')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('pending_registrations', ['email' => $user->email]);
    }

    public function test_pending_registration_code_expires_after_ten_minutes_and_is_reissued(): void
    {
        $this->postJson('/api/auth/register', [
            'prenom' => 'Barry',
            'nom' => 'Mamadou',
            'telephone' => '0701020304',
            'email' => 'barry@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertAccepted();

        $expiredCode = $this->sentCode();
        $this->travel(11)->minutes();

        $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'barry@example.test',
            'code' => $expiredCode,
        ])->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->assertDatabaseMissing('users', ['email' => 'barry@example.test']);
        $replacementCode = $this->sentCodeDifferentFrom($expiredCode);

        $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'barry@example.test',
            'code' => $replacementCode,
        ])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'barry@example.test']);
        $this->assertDatabaseMissing('pending_registrations', ['email' => 'barry@example.test']);
    }

    public function test_email_verification_code_expires_after_ten_minutes(): void
    {
        $user = User::factory()->unverified()->create(['role' => UserRole::Membre]);
        app(EmailVerificationService::class)->sendCode($user);

        $sentCode = $this->sentCode();
        $this->travel(11)->minutes();

        $this->postJson('/api/auth/email-verification/verify', [
            'email' => $user->email,
            'code' => $sentCode,
        ])->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->assertGuest('web');
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
    }

    public function test_five_incorrect_codes_consume_the_verification_attempts(): void
    {
        $user = User::factory()->unverified()->create(['role' => UserRole::Membre]);
        app(EmailVerificationService::class)->sendCode($user);
        $sentCode = $this->sentCode();
        $wrongCode = str_pad((string) (((int) $sentCode + 1) % 1000000), 6, '0', STR_PAD_LEFT);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/email-verification/verify', [
                'email' => $user->email,
                'code' => $wrongCode,
            ])->assertUnprocessable()->assertJsonValidationErrors('code');
        }

        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertGuest('web');
    }

    public function test_resend_is_private_for_unknown_emails_and_limited_per_address(): void
    {
        $this->postJson('/api/auth/email-verification/resend', ['email' => 'unknown@example.test'])
            ->assertAccepted();
        Http::assertNothingSent();

        $user = User::factory()->unverified()->create(['role' => UserRole::Membre]);
        $this->postJson('/api/auth/email-verification/resend', ['email' => $user->email])->assertAccepted();
        Http::assertSentCount(1);

        $this->postJson('/api/auth/email-verification/resend', ['email' => $user->email])->assertTooManyRequests();
        $this->assertGuest('web');
    }

    public function test_resend_sends_for_an_unverified_gym_owner_but_never_for_the_super_admin(): void
    {
        User::factory()->create(['role' => UserRole::Membre, 'email' => 'verified@example.test']);
        $admin = User::factory()->unverified()->create(['role' => UserRole::Admin, 'email' => 'admin@example.test']);
        config()->set('elitefit.super_admin_email', 'owner@arena.test');
        User::factory()->unverified()->create(['role' => UserRole::SuperAdmin, 'email' => 'owner@arena.test']);

        $this->postJson('/api/auth/email-verification/resend', ['email' => 'verified@example.test'])->assertAccepted();
        $this->postJson('/api/auth/email-verification/resend', ['email' => $admin->email])->assertAccepted();
        $this->postJson('/api/auth/email-verification/resend', ['email' => 'owner@arena.test'])->assertAccepted();

        Http::assertSentCount(1);
        Http::assertSent(fn (ClientRequest $request): bool => $request['to'] === ['admin@example.test']);
    }

    public function test_email_verification_does_not_skip_an_admins_second_factor(): void
    {
        $admin = User::factory()->unverified()->create(['role' => UserRole::Admin]);
        Filament::setCurrentPanel('admin');
        $authenticator = AppAuthentication::make();
        $admin->saveAppAuthenticationSecret($authenticator->generateSecret());

        app(EmailVerificationService::class)->sendCode($admin);
        $sentCode = $this->sentCode();

        $this->postJson('/api/auth/email-verification/verify', [
            'email' => $admin->email,
            'code' => $sentCode,
        ])->assertOk()->assertJsonPath('redirect_url', '/connexion');

        $this->assertNotNull($admin->fresh()->email_verified_at);
        $this->assertGuest('web');
    }

    private function sentCode(): string
    {
        $sentCode = null;

        Http::assertSent(function (ClientRequest $request) use (&$sentCode): bool {
            preg_match('/<strong>(\d{6})<\/strong>/', $request['html'], $matches);
            $sentCode = $matches[1] ?? null;

            return $sentCode !== null;
        });

        $this->assertIsString($sentCode);

        return $sentCode;
    }

    private function sentCodeDifferentFrom(string $previousCode): string
    {
        $sentCode = null;

        Http::assertSent(function (ClientRequest $request) use ($previousCode, &$sentCode): bool {
            preg_match('/<strong>(\d{6})<\/strong>/', $request['html'], $matches);
            $candidate = $matches[1] ?? null;
            if ($candidate === null || $candidate === $previousCode || $request['to'] !== ['barry@example.test']) {
                return false;
            }

            $sentCode = $candidate;

            return true;
        });

        $this->assertIsString($sentCode);

        return $sentCode;
    }
}
