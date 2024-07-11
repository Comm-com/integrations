<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use App\Services\BalanceService;
use Database\Factories\UserFactory;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{

    public function testToken()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $token = $user->createToken('test-token');
        $token = $token->plainTextToken;
        $this->get('/api/user', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJson(['id' => $user->id]);
    }
    
    public function testBalance()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->ownedTeams()->first()->id;
        $user->save();
        $token = $user->createToken('test-token');
        app(BalanceService::class,['team_id' => $user->current_team_id])->addBalance(1000);
        $token = $token->plainTextToken;
        $res = $this->get('/api/v1/user/balance/total', ['Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'])
            ->assertOk();
        
        $this->assertEquals(1000, $res->json('data.amount'));
    }
}