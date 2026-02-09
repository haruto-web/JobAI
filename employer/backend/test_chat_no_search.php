<?php

use Illuminate\Support\Facades\Auth;
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing AI Chat without Web Search...\n";

try {
    // Get first user
    $user = App\Models\User::first();
    if (!$user) {
        echo "No users found in database. Please create a user first.\n";
        exit(1);
    }

    echo "Using user: " . $user->email . "\n";

    // Login user
    Auth::login($user);

    // Create request with a message that doesn't require search
    $request = new Illuminate\Http\Request();
    $request->merge(['message' => 'What jobs do you recommend for someone with Python skills?']);

    // Create controller and call chat method
    $controller = new App\Http\Controllers\Api\AiController();
    $response = $controller->chat($request);

    echo "Response received:\n";
    echo $response->getData()->response . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
