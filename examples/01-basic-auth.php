<?php

require __DIR__ . '/../vendor/autoload.php';

use SoWasIt\SowasitClient;
use SoWasIt\Exception\SowasitException;

$client = new SowasitClient([
    'baseUrl' => 'http://localhost:3001',
    'apiKey' => 'sk_live_xxxxx',
]);

try {
    echo "🔐 Authenticating with API key...\n";
    $response = $client->loginWithApiKey('sk_live_xxxxx');
    
    if ($response['success'] ?? false) {
        echo "✅ Authenticated successfully\n";
        echo "   Tenant ID: " . ($response['tenant_id'] ?? 'N/A') . "\n";
        echo "   Permissions: " . implode(', ', $response['permissions'] ?? []) . "\n";
    } else {
        echo "❌ Authentication failed\n";
    }
} catch (SowasitException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
