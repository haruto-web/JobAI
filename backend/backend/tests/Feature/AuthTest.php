<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_endpoints()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        // Test login
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertOk()
                ->assertJsonStructure([
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ]
                ]);

        $token = $response->json('token');

        // Test protected user endpoint with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/user');

        $response->assertOk()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'email'
                ]);

        // Test protected endpoint without token
        $response = $this->getJson('/api/user');
        $response->assertUnauthorized();
    }
}