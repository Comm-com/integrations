<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Laravel\Sanctum\PersonalAccessToken;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->withPersonalTeam()
            ->withBalance()
            ->create([
                'name' => 'Test User',
                'email' => 'user@user.com',
            ]);

        // update the user's current_team_id
        $user->currentTeam();
        $user->createToken('Comm.com Token');

        PersonalAccessToken::where('tokenable_id', $user->id)
            ->update([
                'token' => hash('sha256', 'Comm.com Token'),
            ]);
    }
}
