<?php

namespace database\seeders;

use App\Models\Team;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!app()->environment('local')) {
            if (Team::count()) {
                echo "Environment is not local, skipping seeding\n";
                exit;
            }
        }

        if (!app()->environment('testing')) {
            app(\Faker\Generator::class)->seed(2);
        }

        Artisan::call('migrate:fresh');

        $this->call([
            UserSeeder::class,
        ]);
    }
}
