<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController
{
    public function show()
    {
        /** @var User $user */
        $user = auth()->user();
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'current_team_id' =>$user->currentTeam->id,
        ];
    }
}