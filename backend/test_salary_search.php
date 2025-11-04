<?php

use Illuminate\Support\Facades\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Salary-Related Search Queries...\n";

try {
    // Get first user
    $user = Auth::user() ?? App\Models\User::first();
    if (!$user) {
        echo "No users found in database. Please create a user first.\n";
        exit(1);
    }

    Auth::login($user);
    $controller = new App\Http\Controllers\Api\AiController();

    // Test salary-related queries
    $salaryQueries = [
        'what is best job with high salary?',
        'highest paying jobs in tech',
        'what are the top paying careers?',
        'jobs with highest salaries',
        'most lucrative professions',
        'best paid jobs 2024',
    ];

    foreach ($salaryQueries as $query) {
        echo "\n--- Testing: '{$query}' ---\n";

        $webSearch = new App\Services\WebSearchService();
        $requiresSearch = $webSearch->requiresWebSearch($query);
        echo "Requires web search: " . ($requiresSearch ? 'YES' : 'NO') . "\n";

        if ($requiresSearch) {
            $request = new Illuminate\Http\Request();
            $request->merge(['message' => $query]);

            $response = $controller->chat($request);
            $statusCode = $response->getStatusCode();
            $data = $response->getData();

            echo "Status: {$statusCode}\n";
            if (isset($data->response)) {
                echo "Response preview: " . substr($data->response, 0, 200) . "...\n";
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
