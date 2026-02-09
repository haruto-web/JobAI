<?php

use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class);

it('resumes job creation session for employer', function () {
    $employer = User::factory()->create(['user_type' => 'employer']);

    // Simulate an ongoing job creation session
    Session::put("job_creation_{$employer->id}", [
        'step' => 'location',
        'data' => ['title' => 'Software Developer']
    ]);

    $response = $this->actingAs($employer, 'sanctum')
        ->postJson('/api/ai/chat', ['message' => 'New York']);

    $response->assertStatus(200)
        ->assertJsonStructure(['response']);

    // Verify the session was updated
    $session = Session::get("job_creation_{$employer->id}");
    expect($session['data']['location'])->toBe('New York');
});

it('starts new job creation for employer', function () {
    $employer = User::factory()->create(['user_type' => 'employer']);

    $response = $this->actingAs($employer, 'sanctum')
        ->postJson('/api/ai/chat', ['message' => 'create job for me']);

    $response->assertStatus(200)
        ->assertJsonStructure(['response']);

    // Verify session was created
    $session = Session::get("job_creation_{$employer->id}");
    expect($session)->not->toBeNull();
    expect($session['step'])->toBe('title');
});

it('does not interfere with normal chat for employer without session', function () {
    $employer = User::factory()->create(['user_type' => 'employer']);

    $response = $this->actingAs($employer, 'sanctum')
        ->postJson('/api/ai/chat', ['message' => 'hello']);

    $response->assertStatus(200)
        ->assertJsonStructure(['response']);

    // Verify no session was created
    $session = Session::get("job_creation_{$employer->id}");
    expect($session)->toBeNull();
});

it('handles job creation flow completion', function () {
    $employer = User::factory()->create(['user_type' => 'employer']);

    // Set up a session at the review step
    Session::put("job_creation_{$employer->id}", [
        'step' => 'review',
        'data' => [
            'title' => 'Test Job',
            'location' => 'Remote',
            'type' => 'full-time',
            'summary' => 'Test summary',
            'description' => 'Test description',
            'salary' => '50000'
        ]
    ]);

    $response = $this->actingAs($employer, 'sanctum')
        ->postJson('/api/ai/chat', ['message' => 'approve']);

    $response->assertStatus(200)
        ->assertJsonStructure(['response']);

    // Verify session was cleared
    $session = Session::get("job_creation_{$employer->id}");
    expect($session)->toBeNull();

    // Verify job was created
    $this->assertDatabaseHas('jobs', [
        'title' => 'Test Job',
        'location' => 'Remote',
        'type' => 'full-time',
        'user_id' => $employer->id
    ]);
});
