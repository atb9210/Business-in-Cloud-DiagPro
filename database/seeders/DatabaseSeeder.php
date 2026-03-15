<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user for Filament (only if not exists)
        if (!User::where('email', env('ADMIN_EMAIL', 'admin@businesscloud.it'))->exists()) {
            User::create([
                'name' => env('ADMIN_NAME', 'Admin'),
                'email' => env('ADMIN_EMAIL', 'admin@businesscloud.it'),
                'password' => env('ADMIN_PASSWORD', 'changeme123!'),
                'email_verified_at' => now(),
            ]);
        }

        // Keep legacy test user for development
        if (app()->environment('local', 'testing')) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $this->call([
            TrafficSourceSeeder::class,
            GoogleMapsSettingsSeeder::class,
            ObiettivoSeeder::class,
            OpenAISettingSeeder::class,
        ]);
    }
}
