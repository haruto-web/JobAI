<?php

use Illuminate\Support\Facades\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Different User Types in AI Chat...\n";

try {
    // Test with job seeker user
    $jobSeeker = App\Models\User::where('user_type', 'jobseeker')->first();
    if (!$jobSeeker) {
        $jobSeeker = App\Models\User::first();
        if ($jobSeeker) {
            $jobSeeker->user_type = 'jobseeker';
            $jobSeeker->save();
        }
    }

    if ($jobSeeker) {
        echo "\n--- Testing Job Seeker ---\n";
        Auth::login($jobSeeker);

        $request = new Illuminate\Http\Request();
        $request->merge(['message' => 'What are the latest trends in software development?']);

        $controller = new App\Http\Controllers\Api\AiController();
        $response = $controller->chat($request);

        echo "Status: " . $response->getStatusCode() . "\n";
        if (isset($response->getData()->response)) {
            echo "Response preview: " . substr($response->getData()->response, 0, 100) . "...\n";
        }
    }

    // Test with employer user
    $employer = App\Models\User::where('user_type', 'employer')->first();
    if (!$employer) {
        $employer = App\Models\User::factory()->create([
            'name' => 'Test Employer',
            'email' => 'employer@test.com',
            'user_type' => 'employer',
            'password' => bcrypt('password'),
        ]);
    }

    if ($employer) {
        echo "\n--- Testing Employer ---\n";
        Auth::login($employer);

        $request = new Illuminate\Http\Request();
        $request->merge(['message' => 'Create a job for me: Software Developer at Tech Corp, remote, $80k']);

        $controller = new App\Http\Controllers\Api\AiController();
        $response = $controller->chat($request);

        echo "Status: " . $response->getStatusCode() . "\n";
        if (isset($response->getData()->response)) {
            echo "Response preview: " . substr($response->getData()->response, 0, 100) . "...\n";
        }
    }

    // Test employer job creation
    if ($employer) {
        echo "\n--- Testing Job Creation for Employer ---\n";
        Auth::login($employer);

        $request = new Illuminate\Http\Request();
        $request->merge(['message' => 'create job for me']);

        $controller = new App\Http\Controllers\Api\AiController();
        $response = $controller->chat($request);

        echo "Status: " . $response->getStatusCode() . "\n";
        if (isset($response->getData()->response)) {
            echo "Job creation response preview: " . substr($response->getData()->response, 0, 100) . "...\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
