<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GymRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gym_registration_explains_the_minimum_password_length_in_french(): void
    {
        $response = $this->postJson('/inscription-salle', [
            'gym_name' => 'Nouvelle Salle',
            'prenom' => 'Amsk',
            'nom' => 'Marie',
            'telephone' => '0701020304',
            'email' => 'admin@nouvelle-salle.test',
            'password' => 'Short123!',
            'password_confirmation' => 'Short123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.password.0', 'Le mot de passe doit contenir au moins 12 caractères.');
    }

    public function test_new_gym_admin_verifies_email_then_opens_their_gym_dashboard_without_relogging(): void
    {
        config()->set('services.resend.key', 'test-resend-key');
        config()->set('services.resend.from', 'Arena Gym <no-reply@arena.test>');
        Http::fake(['https://api.resend.com/emails' => Http::response(['id' => 'email-test-id'])]);

        $registration = $this->postJson('/inscription-salle', [
            'gym_name' => 'Nouvelle Salle',
            'prenom' => 'Amsk',
            'nom' => 'Marie',
            'telephone' => '0701020304',
            'email' => 'admin@nouvelle-salle.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $registration->assertAccepted()
            ->assertJsonPath('email', 'admin@nouvelle-salle.test')
            ->assertJsonMissingPath('token');

        $this->assertDatabaseMissing('users', ['email' => 'admin@nouvelle-salle.test']);
        $this->assertSame(0, Gym::query()->count());
        $pendingRegistration = PendingRegistration::query()->where('email', 'admin@nouvelle-salle.test')->firstOrFail();
        $this->assertSame('admin', $pendingRegistration->role?->value);
        $this->assertGuest('web');

        $code = null;
        Http::assertSent(function (ClientRequest $request) use (&$code): bool {
            if ($request->url() !== 'https://api.resend.com/emails' || $request['to'] !== ['admin@nouvelle-salle.test']) {
                return false;
            }

            preg_match('/<strong>(\d{6})<\/strong>/', $request['html'], $matches);
            $code = $matches[1] ?? null;

            return $code !== null;
        });
        $this->assertIsString($code);

        $verification = $this->postJson('/api/auth/email-verification/verify', [
            'email' => 'admin@nouvelle-salle.test',
            'code' => $code,
        ]);
        $verification->assertOk();

        $user = User::query()->where('email', 'admin@nouvelle-salle.test')->firstOrFail();
        $gym = Gym::query()->where('owner_id', $user->id)->firstOrFail();

        $this->assertSame('admin', $user->role?->value);
        $this->assertSame('nouvelle-salle', $gym->slug);
        $this->assertFalse($gym->is_approved);
        $this->assertSame('CI', $gym->country_code);
        $this->assertSame('XOF', $gym->currency_code);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $verification->assertJsonPath('redirect_url', url('/admin'));

        $this->assertAuthenticatedAs($user, 'web');

        $this->getJson('/api/admin/stats')
            ->assertOk();

        $this->actingAs($user, 'web')
            ->get(route('filament.admin.pages.home-dashboard', ['tenant' => $gym->getRouteKey()]))
            ->assertOk();
    }
}
