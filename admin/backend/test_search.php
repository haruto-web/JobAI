<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing job search functionality...\n\n";

// Test different search queries
$searchQueries = [
    'Web Developer',
    'Manila',
    'full-time',
    'Chef',
    '15000',
    'contract',
    'Teacher'
];

foreach ($searchQueries as $query) {
    echo "Searching for: '$query'\n";

    $jobs = App\Models\Job::where('status', 'published')
        ->where(function($q) use ($query) {
            $searchTerm = strtolower($query);
            $q->whereRaw('LOWER(title) LIKE ?', ['%' . $searchTerm . '%'])
              ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $searchTerm . '%'])
              ->orWhereRaw('LOWER(summary) LIKE ?', ['%' . $searchTerm . '%'])
              ->orWhereRaw('LOWER(qualifications) LIKE ?', ['%' . $searchTerm . '%'])
              ->orWhereRaw('LOWER(company) LIKE ?', ['%' . $searchTerm . '%'])
              ->orWhereRaw('LOWER(location) LIKE ?', ['%' . $searchTerm . '%'])
              ->orWhereRaw('LOWER(type) LIKE ?', ['%' . $searchTerm . '%']);
        })
        ->limit(20)
        ->get();

    echo "Found " . $jobs->count() . " jobs:\n";
    foreach ($jobs as $job) {
        echo "  - " . $job->title . " (" . $job->company . ", " . $job->location . ")\n";
    }
    echo "\n";
}
