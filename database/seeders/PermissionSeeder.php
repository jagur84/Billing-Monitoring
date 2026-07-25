<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * One permission per sidebar menu item, checkable per-user on the Users & Roles page.
     * Super-admin bypasses all of this via a Gate::before in AppServiceProvider, so it never
     * needs any of these assigned.
     */
    public const MENUS = [
        'customers', 'packages', 'invoices', 'payments', 'reports',
        'mikrotik', 'genieacs', 'tickets', 'inventory', 'expenses',
    ];

    public function run(): void
    {
        foreach (self::MENUS as $menu) {
            Permission::firstOrCreate(['name' => $menu]);
        }

        // Grant full menu access to every existing non-super-admin user so this feature
        // shipping doesn't silently lock anyone out of screens they already used — a
        // super-admin can then tighten individual users down via Edit User.
        User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->get()
            ->each(fn (User $user) => $user->syncPermissions(self::MENUS));
    }
}
