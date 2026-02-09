<?php

use Illuminate\Support\Facades\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing OpenAI Service with Search Results...\n";

try {
    $openai = new App\Services\OpenAIService();
    $webSearch = new App\Services\WebSearchService();

    // Get some search results
    $searchResults = $webSearch->search('latest software development trends 2024', 3);
    echo "Search results obtained: " . count($searchResults) . "\n";

    // Test the enhanced response
    $message = 'What are the latest trends in software development for 2024?';
    $response = $openai->generateSearchEnhancedResponse($message, $searchResults, 'job_seeker');

    echo "AI Response with search enhancement:\n";
    echo $response . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
