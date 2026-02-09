<?php

use Illuminate\Support\Facades\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Full AI Chat Flow with Resume Analysis and Job Suggestions...\n";

try {
    // Get or create a job seeker user
    $user = App\Models\User::where('user_type', 'jobseeker')->first();
    if (!$user) {
        $user = App\Models\User::first();
        if ($user) {
            $user->user_type = 'jobseeker';
            $user->save();
        }
    }

    if (!$user) {
        echo "No users found. Please create a user first.\n";
        exit(1);
    }

    Auth::login($user);
    $controller = new App\Http\Controllers\Api\AiController();

    // Test resume analysis
    echo "\n--- Testing Resume Analysis ---\n";
    $resumeContent = "John Doe\nSoftware Developer\n\nExperience:\n- 3 years PHP/Laravel development\n- 2 years React.js\n\nSkills:\n- PHP, Laravel, JavaScript, React, MySQL\n\nEducation:\n- Bachelor's in Computer Science";

    $request = new Illuminate\Http\Request();
    $request->merge(['message' => 'analyze my resume', 'resume_content' => $resumeContent]);

    $response = $controller->chat($request);
    echo "Resume Analysis Status: " . $response->getStatusCode() . "\n";
    if (isset($response->getData()->response)) {
        echo "Response preview: " . substr($response->getData()->response, 0, 150) . "...\n";
    }

    // Test job suggestions
    echo "\n--- Testing Job Suggestions ---\n";
    $request = new Illuminate\Http\Request();
    $request->merge(['message' => 'suggest jobs for me']);

    $response = $controller->chat($request);
    echo "Job Suggestions Status: " . $response->getStatusCode() . "\n";
    if (isset($response->getData()->response)) {
        echo "Response preview: " . substr($response->getData()->response, 0, 150) . "...\n";
    }

    // Test web search query
    echo "\n--- Testing Web Search Query ---\n";
    $request = new Illuminate\Http\Request();
    $request->merge(['message' => 'What are the latest trends in software development for 2024?']);

    $response = $controller->chat($request);
    echo "Web Search Query Status: " . $response->getStatusCode() . "\n";
    if (isset($response->getData()->response)) {
        echo "Response preview: " . substr($response->getData()->response, 0, 150) . "...\n";
    }

    // Test regular chat
    echo "\n--- Testing Regular Chat ---\n";
    $request = new Illuminate\Http\Request();
    $request->merge(['message' => 'How do I apply for a job?']);

    $response = $controller->chat($request);
    echo "Regular Chat Status: " . $response->getStatusCode() . "\n";
    if (isset($response->getData()->response)) {
        echo "Response preview: " . substr($response->getData()->response, 0, 150) . "...\n";
    }

    echo "\n✓ Full flow testing completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
