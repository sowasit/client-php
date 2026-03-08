<?php

require __DIR__ . '/../vendor/autoload.php';

use SoWasIt\SowasitClient;
use SoWasIt\Exception\SowasitException;

$client = new SowasitClient([
    'baseUrl' => 'https://api.sowasit.io/v1',
    'apiKey' => getenv('SOWASIT_API_KEY') ?: 'sk_live_xxxxx',
]);

try {
    echo "SoWasIt PHP Client - Complete Workflow\n";
    echo "=======================================\n\n";

    echo "Step 1: Create a Chain\n";
    $chain = $client->chains()->create('events-' . time(), 'User Events Chain', [
        'description' => 'Recording user activity events',
        'visibility' => 'private',
        'type' => 'data',
    ]);
    echo "Chain created: " . $chain->name . " (ID: " . $chain->id . ")\n\n";

    echo "Step 2: Create Multiple Blocks\n";
    $blockIds = [];

    $events = [
        ['type' => 'user_signup', 'email' => 'alice@example.com', 'name' => 'Alice'],
        ['type' => 'user_login', 'email' => 'alice@example.com', 'ip' => '192.168.1.1'],
        ['type' => 'purchase', 'email' => 'alice@example.com', 'amount' => 99.99, 'product' => 'Premium Plan'],
        ['type' => 'user_logout', 'email' => 'alice@example.com', 'session_duration' => 3600],
    ];

    foreach ($events as $event) {
        $block = $client->blocks()->create($chain->id, array_merge($event, [
            'timestamp' => date('c'),
        ]));
        $blockIds[] = $block->id;
        echo "Block " . count($blockIds) . " created: " . $event['type'] . " (" . $block->id . ")\n";
    }
    echo "\n";

    echo "Step 3: Export Chain Data\n";
    $chainData = $client->chains()->export($chain->id);
    echo "Total blocks: " . ($chainData['stats']['total_blocks'] ?? 0) . "\n\n";

    echo "Step 4: Retrieve Specific Block\n";
    $blockDetails = $client->blocks()->get($chain->id, $blockIds[0]);
    echo "Block: " . $blockIds[0] . "\n";
    echo "Data: " . json_encode($blockDetails->data) . "\n";
    echo "Hash: " . substr($blockDetails->hash, 0, 16) . "...\n\n";

    echo "Step 5: Get Latest Block\n";
    $latestBlock = $client->blocks()->getLatest($chain->id);
    echo "Latest block: " . $latestBlock->id . "\n";
    echo "Hash: " . substr($latestBlock->hash, 0, 16) . "...\n\n";

    echo "All operations completed successfully!\n";

} catch (SowasitException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
    exit(1);
}
