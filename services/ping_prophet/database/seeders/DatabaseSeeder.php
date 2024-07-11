<?php

namespace Database\Seeders;

use App\Models\User;

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
            echo "Environment is not local, skipping seeding\n";
            exit;
        }

        Artisan::call('migrate:fresh');

        $this->call([
            UserSeeder::class,
        ]);
    }
}
