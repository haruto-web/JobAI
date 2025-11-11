<?php

use App\Models\User;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('allows an employer to add money via manage-money', function () {
    // Create an employer user
    $employer = User::factory()->create(['user_type' => 'employer']);

    // Act as employer using sanctum
    $this->actingAs($employer, 'sanctum');

    $payload = [
        'amount' => 100.00,
        'description' => 'Add funds for test'
    ];

    $response = $this->postJson('/api/manage-money', $payload);

    $response->assertStatus(200)->assertJsonStructure(['message', 'payment']);

    $this->assertDatabaseHas('payments', [
        'employer_id' => $employer->id,
        'amount' => 100.00,
        'description' => 'Add funds for test',
        'type' => 'money_added'
    ]);
});
