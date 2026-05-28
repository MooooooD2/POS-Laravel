<?php
require 'vendor/autoload.php';
use Illuminate\Support\Str;

echo "escapeLike exists: " . (int)method_exists('Illuminate\Support\Str', 'escapeLike') . PHP_EOL;
$methods = get_class_methods('Illuminate\Support\Str');
$elike = array_filter($methods, fn($m) => stripos($m, 'escape') !== false);
echo "escape methods: " . implode(', ', $elike) . PHP_EOL;

// Try DB connection approach
try {
    $app = require 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    echo "Str::escapeLike exists via macros: " . (int)Str::hasMacro('escapeLike') . PHP_EOL;
    echo "Test: " . Str::escapeLike('test%value') . PHP_EOL;
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
