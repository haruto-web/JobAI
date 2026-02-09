<?php

use Illuminate\Support\Facades\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\WebSearchService;

echo "Testing WebSearchService...\n";

try {
    $service = new WebSearchService();
    echo "Service created successfully.\n";

    $results = $service->search('latest software development trends 2024', 3);
    echo "Search completed. Results:\n";
    print_r($results);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
