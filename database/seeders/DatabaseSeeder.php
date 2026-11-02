<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolesAndPermissionsSeeder::class,
            MasterDataSeeder::class,
            SettingsSeeder::class,
        ]);

        // Test accounts (admin@example.com / password ...) and demo listings are for development only.
        // In production run `php artisan db:seed --class=PropertySeeder` yourself if you want demo listings.
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                TestUsersSeeder::class,
                PropertySeeder::class,
            ]);
        }
    }
}
