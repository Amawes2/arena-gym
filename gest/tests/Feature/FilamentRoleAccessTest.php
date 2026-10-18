<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FilamentRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_allows_only_one_super_admin_account(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');

        $superAdmin = User::factory()->create([
            'email' => 'owner@arena.test',
            'role' => UserRole::SuperAdmin,
        ]);

        $this->assertTrue($superAdmin->canAccessPanel(Filament::getPanel('admin')));
        $this->assertTrue($superAdmin->canAccessPanel(Filament::getPanel('platform')));

        $this->actingAs($superAdmin)
            ->get('/super-admin/gyms')
            ->assertOk();

        $this->expectException(ValidationException::class);

        User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    public function test_the_database_rejects_super_admin_roles_assigned_through_query_builder(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');

        User::factory()->create([
            'email' => 'owner@arena.test',
            'role' => UserRole::SuperAdmin,
        ]);

        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'Second Super Admin',
            'email' => 'second-super-admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::SuperAdmin->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_only_the_configured_email_can_hold_the_super_admin_role(): void
    {
        config()->set('elitefit.super_admin_email', 'owner@arena.test');

        $otherUser = new User([
            'name' => 'Other User',
            'email' => 'other@arena.test',
            'role' => UserRole::SuperAdmin,
        ]);

        $this->assertFalse($otherUser->isSuperAdmin());
        $this->assertNull($otherUser->roleEnum());
        $this->assertFalse($otherUser->canAccessPanel(Filament::getPanel('platform')));

        $this->expectException(ValidationException::class);

        $otherUser->save();
    }

    public function test_unknown_roles_cannot_access_the_filament_panel(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Unknown Role',
            'email' => 'unknown@example.com',
            'password' => Hash::make('password'),
            'role' => 'unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($userId);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_receptionist_can_run_front_desk_operations_but_cannot_manage_finance_or_plans(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Admin]);
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);

        $gym = Gym::query()->create([
            'name' => 'Arena Gym',
            'slug' => 'arena-gym',
            'owner_id' => $owner->id,
            'plan_saas' => 'basic',
            'is_active' => true,
        ]);
        $receptionist->managedGyms()->attach($gym);

        $this->assertFalse($receptionist->canAccessPanel(Filament::getPanel('platform')));
        $this->assertFalse($receptionist->can('viewAny', Gym::class));
        $this->assertTrue($receptionist->can('viewAny', Member::class));
        $this->assertTrue($receptionist->can('create', CheckIn::class));
        $this->assertTrue($receptionist->can('create', Payment::class));
        $this->assertTrue($receptionist->can('viewAny', Plan::class));
        $this->assertFalse($receptionist->can('create', Plan::class));
        $this->assertFalse($receptionist->can('update', new Plan(['gym_id' => $gym->id])));
        $this->assertFalse($receptionist->can('update', new Payment(['gym_id' => $gym->id])));
        $this->assertFalse($receptionist->can('deleteAny', CheckIn::class));

        $this->actingAs($owner)
            ->get(route('filament.admin.pages.home-dashboard', ['tenant' => $gym->getRouteKey()]))
            ->assertOk();
    }

    public function test_staff_api_keeps_read_access_for_inactive_or_expired_gyms(): void
    {
        foreach ([
            ['name' => 'Inactive Gym', 'slug' => 'inactive-gym', 'is_active' => false, 'expires_at' => now()->addMonth()],
            ['name' => 'Expired Gym', 'slug' => 'expired-gym', 'is_active' => true, 'expires_at' => now()->subDay()],
        ] as $gymData) {
            $admin = User::factory()->create(['role' => UserRole::Admin]);
            $gym = Gym::query()->create($gymData + [
                'owner_id' => $admin->id,
                'plan_saas' => 'basic',
            ]);
            $admin->managedGyms()->attach($gym);

            $this->actingAs($admin, 'web')
                ->getJson('/api/admin/stats')
                ->assertOk();
        }
    }
}
