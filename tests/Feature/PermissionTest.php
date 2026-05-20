<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    // Roles and permissions are seeded via DatabaseSeeder in most cases,
    // but for tests we ensure they exist.
    if (Role::where('name', 'admin')->doesntExist()) {
        $permission = Permission::firstOrCreate(['name' => 'view monitoring']);
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo($permission);
    }
});

test('admin can access telescope gate', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect(Gate::forUser($admin)->check('viewTelescope'))->toBeTrue();
});

test('regular user cannot access telescope gate', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->check('viewTelescope'))->toBeFalse();
});

test('inertia shares permissions', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.permissions', fn ($permissions) => collect($permissions)->contains('view monitoring'))
        );
});
