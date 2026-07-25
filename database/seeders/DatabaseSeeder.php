<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        $admin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@sebilling.test',
        ]);
        $admin->assignRole('super-admin');

        $this->call([
            DemoDataSeeder::class,
        ]);

        // Runs last so it can grant full menu access to every non-super-admin user just seeded.
        $this->call([
            PermissionSeeder::class,
        ]);
    }
}
