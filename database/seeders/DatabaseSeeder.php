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
        $this->call([
            RoleSeeder::class,
            SuperAdminSeeder::class,
            ModuleSeeder::class,
            // Bangladesh geocode seeders
            DivisionSeeder::class,
            DistrictSeeder::class,
            ThanaSeeder::class,
            UnionSeeder::class,
            WebsiteThemeSeeder::class,
            WebsiteMenuTemplateSeeder::class,
            WebsitePageTemplateSeeder::class,
        ]);
    }
}
