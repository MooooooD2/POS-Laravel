<?php
// SECURITY: Delete this file immediately after use.
// Only works while it exists on the server.

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

$secret = $_GET['secret'] ?? '';
if ($secret !== 'biskumarket-clear-2026') {
    http_response_code(403);
    die('Forbidden');
}

$deleted = [];
$failed  = [];

$cacheFiles = [
    __DIR__ . '/../bootstrap/cache/routes-v7.php',
    __DIR__ . '/../bootstrap/cache/config.php',
    __DIR__ . '/../bootstrap/cache/events.php',
];

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        if (@unlink($file)) {
            $deleted[] = basename($file);
        } else {
            $failed[] = basename($file);
        }
    }
}

header('Content-Type: text/plain');
echo "Deleted: " . (empty($deleted) ? 'none' : implode(', ', $deleted)) . "\n";
echo "Failed:  " . (empty($failed)  ? 'none' : implode(', ', $failed))  . "\n";
echo "\nDELETE THIS FILE NOW from public/clear-cache.php\n";
