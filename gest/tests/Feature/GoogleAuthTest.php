<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_sign_in_redirects_to_the_provider_when_configured(): void
    {
        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');

        Socialite::fake('google');

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_google_sign_in_requires_server_credentials(): void
    {
        config()->set('services.google.client_id', null);
        config()->set('services.google.client_secret', null);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('/connexion?google_error=not_configured');
    }

    public function test_google_sign_in_authenticates_an_existing_verified_gym_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'gym-admin@example.test',
            'email_verified_at' => null,
            'role' => UserRole::Admin,
        ]);
        Gym::factory()->create(['owner_id' => $user->id]);

        Socialite::fake('google', GoogleUser::fake([
            'email' => 'gym-admin@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_google_sign_in_does_not_create_unknown_accounts(): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'email' => 'unknown@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/connexion?google_error=no_account');

        $this->assertGuest('web');
        $this->assertDatabaseMissing('users', ['email' => 'unknown@example.test']);
    }

    public function test_google_sign_in_does_not_bypass_two_factor_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'gym-admin@example.test',
            'role' => UserRole::Admin,
        ]);
        Gym::factory()->create(['owner_id' => $user->id]);
        $user->saveAppAuthenticationSecret('test-two-factor-secret');

        Socialite::fake('google', GoogleUser::fake([
            'email' => 'gym-admin@example.test',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/connexion?google_error=two_factor');

        $this->assertGuest('web');
    }
}
