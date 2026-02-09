<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Listing all jobs in database...\n";

try {
    $jobs = App\Models\Job::all();

    echo "Total jobs: " . $jobs->count() . "\n\n";

    foreach ($jobs as $job) {
        echo "ID: {$job->id}\n";
        echo "Title: {$job->title}\n";
        echo "Company: {$job->company}\n";
        echo "Location: {$job->location}\n";
        echo "Type: {$job->type}\n";
        echo "Salary: {$job->salary}\n";
        echo "Status: {$job->status}\n";
        echo "Description: " . substr($job->description ?? '', 0, 100) . "...\n";
        echo "---\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
