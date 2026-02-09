<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Jobs in database:\n";
$jobs = App\Models\Job::all();
echo "Total jobs: " . $jobs->count() . "\n\n";

foreach ($jobs as $job) {
    echo "Title: " . $job->title . "\n";
    echo "Company: " . $job->company . "\n";
    echo "Location: " . $job->location . "\n";
    echo "Type: " . $job->type . "\n";
    echo "Salary: " . ($job->salary ?? 'Not specified') . "\n";
    echo "Status: " . $job->status . "\n";
    echo "---\n";
}
