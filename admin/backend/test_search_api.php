<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Job Search API directly...\n";

try {
    $controller = new App\Http\Controllers\Api\JobController();

    // Create a mock request with query parameter
    $request = new Illuminate\Http\Request();
    $request->merge(['q' => 'Manila']); // Search for jobs in Manila

    // Call the search method
    $response = $controller->search($request);

    echo "Search response status: " . $response->getStatusCode() . "\n";
    $data = $response->getData();

    if (isset($data->jobs)) {
        echo "Found " . count($data->jobs) . " jobs:\n";
        foreach ($data->jobs as $job) {
            echo "- {$job->title} at {$job->company} in {$job->location}\n";
        }
    } else {
        echo "No jobs found in response.\n";
        print_r($data);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
