<?php

namespace app\Http\Controllers;

use app\Services\BalanceService;

class UserController
{
    public function show()
    {
        return [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'balance' => app(BalanceService::class, ['team_id' => auth()->user()->currentTeam->id])->total(),
            'current_team_id' => auth()->user()->currentTeam->id,
        ];
    }
}