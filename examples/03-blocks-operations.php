<?php

require __DIR__ . '/../vendor/autoload.php';

use SoWasIt\SowasitClient;
use SoWasIt\Exception\SowasitException;

$client = new SowasitClient([
    'baseUrl' => 'https://api.sowasit.io/v1',
    'apiKey' => getenv('SOWASIT_API_KEY') ?: 'sk_live_xxxxx',
]);

$chainId = 'your-chain-id';

try {
    echo "=== Creating a Block ===\n";
    $block = $client->blocks()->create($chainId, [
        'event' => 'user_signup',
        'email' => 'john@example.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'timestamp' => date('c'),
    ]);
    echo "Block created: " . $block->id . "\n";
    echo "Hash: " . $block->hash . "\n\n";

    echo "=== Listing Blocks ===\n";
    $blocks = $client->blocks()->list($chainId, 50, 0);
    echo "Found " . count($blocks) . " blocks\n";
    foreach (array_slice($blocks, 0, 5) as $b) {
        echo "- " . $b['id'] . " at " . $b['created_at'] . "\n";
    }
    echo "\n";

    echo "=== Getting Block Details ===\n";
    $blockDetails = $client->blocks()->get($chainId, $block->id);
    echo "Block ID: " . $blockDetails->id . "\n";
    echo "Data: " . json_encode($blockDetails->data) . "\n";
    echo "Hash: " . $blockDetails->hash . "\n\n";

    echo "=== Getting Latest Block ===\n";
    $latestBlock = $client->blocks()->getLatest($chainId);
    echo "Latest Block ID: " . $latestBlock->id . "\n";
    echo "Hash: " . $latestBlock->hash . "\n";

} catch (SowasitException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
