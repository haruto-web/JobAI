<?php

// One-off script to test POST /api/manage-money via Laravel HTTP kernel
// Run from backend folder: php scripts/run_manage_money_test.php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Use Eloquent & Auth
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

// Create or get an employer user
$employer = App\Models\User::where('user_type', 'employer')->first();
if (! $employer) {
    // Try to create a user without factories if not available
    $employer = App\Models\User::create([
        'name' => 'CLI Employer',
        'email' => 'cli-employer@example.test',
        'password' => Hash::make('password123'),
        'user_type' => 'employer',
    ]);
    echo "Created employer user id={$employer->id}\n";
} else {
    echo "Found employer user id={$employer->id}\n";
}

// Create personal access token
$token = $employer->createToken('cli-test-token')->plainTextToken;
echo "Token: {$token}\n";

// Build the request
$payload = json_encode([
    'amount' => 500.00,
    'description' => 'CLI test add money'
]);

$request = Request::create('/api/manage-money', 'POST', [], [], [], [
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
], $payload);
$request->headers->set('Content-Type', 'application/json');

// Handle request through the HTTP kernel
$response = $app->handle($request);

$status = $response->getStatusCode();
$body = (string) $response->getContent();

echo "Status: {$status}\n";
echo "Body: {$body}\n";

// Print any recent log lines
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    echo "\n--- Last 40 log lines ---\n";
    $lines = array_slice(file($logFile), -40);
    foreach ($lines as $line) echo $line;
}

// Terminate
$app->terminate($request, $response);

