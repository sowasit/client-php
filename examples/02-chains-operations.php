<?php

require __DIR__ . '/../vendor/autoload.php';

use SoWasIt\SowasitClient;
use SoWasIt\Exception\SowasitException;

$client = new SowasitClient([
    'baseUrl' => 'http://localhost:3001',
    'apiKey' => 'sk_live_xxxxx',
]);

try {
    $client->loginWithApiKey('sk_live_xxxxx');

    echo "=== Creating a Chain ===\n";
    $chain = $client->chains()->create('my-chain-001', 'My First Chain', [
        'description' => 'A chain for recording user events',
        'visibility' => 'private',
        'type' => 'data',
    ]);
    
    echo "✅ Chain created!\n";
    echo "Chain ID: " . $chain->id . "\n";
    echo "Chain Name: " . $chain->name . "\n";
    echo "Created at: " . $chain->created_at . "\n\n";

    echo "=== Listing Chains ===\n";
    $chains = $client->chains()->list('tenant', 'data', 1, 10);
    
    echo "Found " . count($chains) . " chains\n";
    foreach ($chains as $c) {
        echo "- " . $c['name'] . " (ID: " . $c['id'] . ")\n";
    }
    echo "\n";

    echo "=== Getting Chain Details ===\n";
    $chainDetails = $client->chains()->get('public-events');
    
    echo "Chain: " . $chainDetails->name . "\n";
    echo "Type: " . $chainDetails->type . "\n";
    echo "Visibility: " . $chainDetails->visibility . "\n\n";

    echo "=== Exporting Chain ===\n";
    $export = $client->chains()->export('public-events');
    
    echo "Total blocks: " . $export['stats']['total_blocks'] . "\n";
    echo "First block: " . $export['stats']['first_block_created'] . "\n";
    echo "Last block: " . $export['stats']['last_block_created'] . "\n";

} catch (SowasitException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
