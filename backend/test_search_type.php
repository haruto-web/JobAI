<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing search by job type...\n";

$testQueries = ['full-time', 'contract', 'part-time'];

foreach ($testQueries as $query) {
    echo "\n--- Testing type search: '{$query}' ---\n";

    $request = new Illuminate\Http\Request();
    $request->merge(['q' => $query]);

    $controller = new App\Http\Controllers\Api\JobController();
    $response = $controller->search($request);
    $content = $response->getContent();

    $data = json_decode($content, true);
    $jobs = $data['jobs'] ?? [];

    echo "Found " . count($jobs) . " jobs\n";
    foreach ($jobs as $job) {
        echo "- {$job['title']}: {$job['type']} in {$job['location']}\n";
    }
}
