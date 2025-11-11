<?php

use Illuminate\Support\Facades\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Error Handling in WebSearchService...\n";

try {
    // Test with invalid API key (temporarily modify config)
    $originalKey = config('services.google_search.api_key');
    config(['services.google_search.api_key' => 'invalid_key']);

    $service = new App\Services\WebSearchService();
    echo "This should not print - service should throw exception\n";

} catch (\RuntimeException $e) {
    echo "✓ Correctly caught RuntimeException for missing API key: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Unexpected error: " . $e->getMessage() . "\n";
}

// Restore config
config(['services.google_search.api_key' => $originalKey]);

echo "\nTesting WebSearchService with valid config...\n";
try {
    $service = new App\Services\WebSearchService();
    echo "✓ Service created successfully\n";

    // Test requiresWebSearch method
    $testMessages = [
        'What are the latest trends in software development?' => true,
        'Show me jobs with Python skills' => false,
        'How do I apply for a job?' => false,
        'Tell me about artificial intelligence' => true,
    ];

    foreach ($testMessages as $message => $expected) {
        $result = $service->requiresWebSearch($message);
        $status = $result === $expected ? '✓' : '✗';
        echo "{$status} '{$message}' -> requires search: " . ($result ? 'true' : 'false') . "\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTesting chat with unauthenticated user...\n";
try {
    // Don't login user
    $request = new Illuminate\Http\Request();
    $request->merge(['message' => 'Hello']);

    $controller = new App\Http\Controllers\Api\AiController();
    $response = $controller->chat($request);

    echo "Response: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() === 401) {
        echo "✓ Correctly returned 401 for unauthenticated request\n";
    } else {
        echo "✗ Expected 401, got " . $response->getStatusCode() . "\n";
    }

} catch (Exception $e) {
    echo "Error in unauthenticated test: " . $e->getMessage() . "\n";
}
