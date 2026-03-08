<?php

require __DIR__ . '/../vendor/autoload.php';

use SoWasIt\SowasitClient;
use SoWasIt\Exception\SowasitException;

$client = new SowasitClient([
    'baseUrl' => 'https://api.sowasit.io/v1',
    'apiKey' => getenv('SOWASIT_API_KEY') ?: 'sk_live_xxxxx',
]);

try {
    echo "=== Health Check ===\n";
    $health = $client->health();
    echo "Status: " . ($health['status'] ?? 'unknown') . "\n";
    echo "Version: " . ($health['version'] ?? 'unknown') . "\n\n";

    echo "The API key is automatically used in all requests.\n";
    echo "No login step required.\n";

} catch (SowasitException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
