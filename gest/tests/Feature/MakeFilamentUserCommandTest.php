<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeFilamentUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_filament_user_prompts_for_the_role_and_gym(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Admin]);
        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym',
            'owner_id' => $owner->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        $this->artisan('make:filament-user')
            ->expectsChoice('Quel rôle souhaitez-vous attribuer ?', 'Admin salle', ['Admin salle', 'Super Admin'])
            ->expectsQuestion('Nom complet', 'Admin Salle')
            ->expectsQuestion('Adresse e-mail', 'admin@arena.test')
            ->expectsChoice('À quelle salle rattacher cet admin ?', "{$gym->id}: Arena Gym", ["{$gym->id}: Arena Gym"])
            ->expectsQuestion('Mot de passe', 'Password123!')
            ->expectsOutputToContain('Compte admin créé : admin@arena.test')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'admin@arena.test')->firstOrFail();

        $this->assertTrue(Hash::check('Password123!', $admin->password));
        $this->assertTrue($admin->managedGyms()->whereKey($gym->id)->exists());
    }

    public function test_make_filament_user_reprompts_when_the_email_is_already_used(): void
    {
        User::factory()->create(['email' => 'existing@arena.test']);
        $owner = User::factory()->create(['role' => UserRole::Admin]);
        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym',
            'owner_id' => $owner->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        $this->artisan('make:filament-user', ['--role' => UserRole::Admin->value])
            ->expectsQuestion('Nom complet', 'Admin Salle')
            ->expectsQuestion('Adresse e-mail', 'existing@arena.test')
            ->expectsQuestion('Adresse e-mail', 'admin@arena.test')
            ->expectsChoice('À quelle salle rattacher cet admin ?', "{$gym->id}: Arena Gym", ["{$gym->id}: Arena Gym"])
            ->expectsQuestion('Mot de passe', 'Password123!')
            ->expectsOutputToContain('Un utilisateur avec cette adresse e-mail existe déjà.')
            ->expectsOutputToContain('Compte admin créé : admin@arena.test')
            ->assertExitCode(0);
    }

    public function test_make_filament_user_creates_a_hashed_admin_and_attaches_the_selected_gym(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Admin]);
        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym',
            'owner_id' => $owner->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);

        $this->artisan('make:filament-user', [
            '--role' => UserRole::Admin->value,
            '--name' => 'Admin Salle',
            '--email' => 'admin@arena.test',
            '--password' => 'Password123!',
            '--gym' => (string) $gym->id,
        ])
            ->expectsOutputToContain('Compte admin créé : admin@arena.test')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'admin@arena.test')->firstOrFail();

        $this->assertTrue(Hash::check('Password123!', $admin->password));
        $this->assertTrue($admin->managedGyms()->whereKey($gym->id)->exists());
        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_make_filament_user_creates_a_hashed_super_admin_for_the_configured_email(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');

        $this->artisan('make:filament-user', [
            '--role' => UserRole::SuperAdmin->value,
            '--name' => 'Platform Owner',
            '--email' => 'owner@arena.test',
            '--password' => 'Password123!',
        ])
            ->expectsOutputToContain('Compte super_admin créé : owner@arena.test')
            ->assertExitCode(0);

        $superAdmin = User::query()->where('email', 'owner@arena.test')->firstOrFail();

        $this->assertTrue(Hash::check('Password123!', $superAdmin->password));
        $this->assertTrue($superAdmin->canAccessPanel(Filament::getPanel('platform')));
    }

    public function test_make_filament_user_rejects_a_super_admin_email_that_is_not_configured(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');

        $this->artisan('make:filament-user', [
            '--role' => UserRole::SuperAdmin->value,
            '--name' => 'Wrong Owner',
            '--email' => 'wrong@arena.test',
            '--password' => 'Password123!',
        ])
            ->expectsOutputToContain('Le Super Admin doit utiliser l’adresse définie dans ARENA_SUPER_ADMIN_EMAIL.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'wrong@arena.test']);
    }
}
