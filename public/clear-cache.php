<?php
// SECURITY: Remove this file after use!
// Or add password protection

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<h1>Clearing Caches...</h1>";

// Clear all caches
$commands = [
    'optimize:clear' => 'Clear all cached bootstrap files',
    'config:cache' => 'Cache configuration',
    'route:cache' => 'Cache routes',
    'view:cache' => 'Cache views',
];

foreach ($commands as $command => $description) {
    echo "<p><strong>{$description}:</strong> ";
    try {
        $kernel->call($command);
        echo "<span style='color: green;'>✓ Success</span></p>";
    } catch (Exception $e) {
        echo "<span style='color: red;'>✗ Failed: {$e->getMessage()}</span></p>";
    }
}

echo "<h2>Route Check:</h2>";
echo "<pre>";
$kernel->call('route:list', ['--name' => 'customers.notes']);
echo "</pre>";

echo "<hr>";
echo "<p style='color: red;'><strong>IMPORTANT: Delete this file immediately for security!</strong></p>";
