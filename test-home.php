<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$request = Illuminate\Http\Request::create('/', 'GET', [], [], [], [
    'HTTP_HOST' => '127.0.0.1',
    'SERVER_NAME' => '127.0.0.1',
]);

$start = microtime(true);
$response = $app->handleRequest($request);
$elapsed = round(microtime(true) - $start, 2);

echo "Status: {$response->getStatusCode()}\n";
echo "Time: {$elapsed}s\n";
