<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Database\Factories\UserFactory;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{

    public function testToken()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $token = $user->createToken('test-token');
        $token = $token->plainTextToken;
        $this->get('/api/v1/user', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJson(['id' => $user->id]);
    }
}