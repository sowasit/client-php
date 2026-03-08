<?php

require __DIR__ . '/../vendor/autoload.php';

use SoWasIt\SowasitClient;
use SoWasIt\Exception\SowasitException;

$client = new SowasitClient([
    'baseUrl' => 'https://api.sowasit.io/v1',
    'apiKey' => getenv('SOWASIT_API_KEY') ?: 'sk_live_xxxxx',
]);

try {
    echo "=== Creating a Chain ===\n";
    $chain = $client->chains()->create('my-chain-001', 'My First Chain', [
        'description' => 'A chain for recording user events',
        'visibility' => 'private',
        'type' => 'data',
    ]);
    echo "Chain created: " . $chain->name . " (ID: " . $chain->id . ")\n\n";

    echo "=== Listing Chains ===\n";
    $chains = $client->chains()->list('tenant', 'data', 1, 10);
    echo "Found " . count($chains) . " chains\n";
    foreach ($chains as $c) {
        echo "- " . $c['name'] . " (ID: " . $c['id'] . ")\n";
    }
    echo "\n";

    echo "=== Getting Chain Details ===\n";
    $chainDetails = $client->chains()->get($chain->id);
    echo "Name: " . $chainDetails->name . "\n";
    echo "Type: " . $chainDetails->type . "\n";
    echo "Visibility: " . $chainDetails->visibility . "\n\n";

    echo "=== Exporting Chain ===\n";
    $export = $client->chains()->export($chain->id);
    echo "Total blocks: " . ($export['stats']['total_blocks'] ?? 0) . "\n";

} catch (SowasitException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
