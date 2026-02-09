<?php

use Illuminate\Support\Facades\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Edge Cases in AI Chat...\n";

try {
    // Get first user
    $user = Auth::user() ?? App\Models\User::first();
    if (!$user) {
        echo "No users found in database. Please create a user first.\n";
        exit(1);
    }

    Auth::login($user);
    $controller = new App\Http\Controllers\Api\AiController();

    // Test cases
    $testCases = [
        'empty_message' => '',
        'very_long_message' => str_repeat('This is a very long message. ', 100),
        'special_characters' => 'What are the trends in AI/ML? @#$%^&*()',
        'sql_injection_attempt' => "'; DROP TABLE users; --",
        'script_injection' => '<script>alert("xss")</script> Hello',
        'unicode_characters' => 'What are 软件开发 trends? 🚀',
    ];

    foreach ($testCases as $testName => $message) {
        echo "\n--- Testing: {$testName} ---\n";
        echo "Message: " . substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '') . "\n";

        try {
            $request = new Illuminate\Http\Request();
            $request->merge(['message' => $message]);

            $response = $controller->chat($request);
            $statusCode = $response->getStatusCode();
            $data = $response->getData();

            echo "Status: {$statusCode}\n";
            if (isset($data->response)) {
                echo "Response: " . substr($data->response, 0, 100) . (strlen($data->response) > 100 ? '...' : '') . "\n";
            }

            if ($statusCode === 200) {
                echo "✓ Request handled successfully\n";
            } else {
                echo "⚠ Unexpected status code: {$statusCode}\n";
            }

        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "Setup error: " . $e->getMessage() . "\n";
}
