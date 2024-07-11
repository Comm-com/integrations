<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()
            ->withPersonalTeam()
            ->withBalance()
            ->create([
                'name' => 'Test User',
                'email' => 'user@user.com',
            ]);

        // update the user's current_team_id
        $user->currentTeam();
    }
}
