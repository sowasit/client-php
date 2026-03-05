<?php

require __DIR__ . '/../vendor/autoload.php';

use SoWasIt\SowasitClient;
use SoWasIt\Exception\SowasitException;

$apiKey = 'sk_live_xxxxx';

$client = new SowasitClient([
    'baseUrl' => 'http://localhost:3001',
    'apiKey' => $apiKey,
]);

try {
    echo "🚀 SoWasIt PHP Client - Complete Workflow\n";
    echo "=========================================\n\n";

    echo "Step 1: Authenticate with API Key\n";
    $loginResponse = $client->loginWithApiKey($apiKey);
    
    if ($loginResponse['success'] ?? false) {
        echo "✅ Authenticated\n";
        echo "   Tenant ID: " . ($loginResponse['tenant_id'] ?? 'N/A') . "\n";
        echo "   Permissions: " . implode(', ', $loginResponse['permissions'] ?? []) . "\n\n";
    } else {
        throw new SowasitException('Authentication failed');
    }

    echo "Step 2: Create a Chain\n";
    $chain = $client->chains()->create('events-' . time(), 'User Events Chain', [
        'description' => 'Recording user activity events',
        'visibility' => 'private',
        'type' => 'data',
    ]);
    echo "✅ Chain created: " . $chain->name . " (ID: " . $chain->id . ")\n\n";

    echo "Step 3: Create Multiple Blocks\n";
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
        echo "✅ Block " . count($blockIds) . " created: " . $event['type'] . " (" . $block->id . ")\n";
    }
    echo "\n";

    echo "Step 4: Retrieve Chain Data\n";
    $chainData = $client->chains()->export($chain->id);
    echo "✅ Chain export retrieved\n";
    echo "   Total blocks: " . $chainData['stats']['total_blocks'] . "\n";
    echo "   First block: " . $chainData['stats']['first_block_created'] . "\n";
    echo "   Last block: " . $chainData['stats']['last_block_created'] . "\n\n";

    echo "Step 5: Retrieve Specific Block\n";
    $blockDetails = $client->blocks()->get($chain->id, $blockIds[0]);
    echo "✅ Block retrieved: " . $blockIds[0] . "\n";
    echo "   Data: " . json_encode($blockDetails->data) . "\n";
    echo "   Hash: " . substr($blockDetails->hash, 0, 16) . "...\n\n";

    echo "Step 6: List All Blocks in Chain\n";
    $blocks = $client->blocks()->list($chain->id, 100, 0);
    echo "✅ Listed " . count($blocks) . " blocks\n";
    foreach ($blocks as $b) {
        echo "   - " . $b['id'] . " at " . $b['created_at'] . "\n";
    }
    echo "\n";

    echo "Step 7: Get Latest Block\n";
    $latestBlock = $client->blocks()->getLatest($chain->id);
    echo "✅ Latest block: " . $latestBlock->id . "\n";
    echo "   Created: " . $latestBlock->created_at . "\n";
    echo "   Hash: " . substr($latestBlock->hash, 0, 16) . "...\n\n";

    echo "✅ All operations completed successfully!\n";

} catch (SowasitException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
    exit(1);
}
