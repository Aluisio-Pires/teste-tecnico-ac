<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::query()->firstOrCreate(['name' => 'view monitoring']);

        // Create roles and assign created permissions
        $role = Role::query()->firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo('view monitoring');

        // Create admin user
        if (User::query()->where('email', 'admin@admin.com')->doesntExist()) {
            $admin = User::factory()->create([
                'email' => 'admin@admin.com',
                'password' => 'password',
            ]);

            $admin->assignRole('admin');
        }
    }
}
