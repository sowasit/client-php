<?php

require __DIR__ . '/../vendor/autoload.php';

use SoWasIt\SowasitClient;
use SoWasIt\Crypto\SignatureManager;
use SoWasIt\Exception\SowasitException;

$signer = new SignatureManager();

// Step 1: Generate a key pair (do this once, save the files securely)
echo "=== Step 1: Generate Key Pair ===\n";
$keyPair = $signer->generateKeyPair('your-secret-passphrase');
$signer->saveKeyPair($keyPair, './keys', 'my-company');
echo "Key ID: " . $keyPair['keyId'] . "\n";
echo "Fingerprint: " . $keyPair['fingerprint'] . "\n\n";

$client = new SowasitClient([
    'baseUrl' => 'https://api.sowasit.io',
    'apiKey' => getenv('SOWASIT_API_KEY') ?: 'sk_live_xxxxx',
]);

try {
    // Step 2: Register the public key (do this once per key pair)
    echo "=== Step 2: Register Public Key ===\n";
    $response = $client->registerPublicKey([
        'enrollmentToken' => 'your_enrollment_token_from_chain_owner',
        'publicKey' => $keyPair['publicKey'],
        'algorithm' => 'ECDSA-P256',
        'clientInfo' => [
            'name' => 'Your Company',
            'siret' => '12345678901234',
            'contact' => 'admin@company.com',
        ],
    ]);
    $publicKeyId = $response['data']['key_id'];
    echo "Key registered. ID: " . $publicKeyId . "\n";
    echo "Status: " . $response['data']['status'] . "\n\n";

    // Step 3: Create a signed block
    echo "=== Step 3: Create Signed Block ===\n";
    $privateKeyPem = file_get_contents('./keys/my-company.private.pem');

    $block = $client->blocks()->createSigned(
        'your-chain-id',
        [
            'event' => 'contract_signed',
            'contract_id' => 'CTR-2026-001',
            'party' => 'Your Company',
            'timestamp' => date('c'),
        ],
        $privateKeyPem,
        $publicKeyId,
        'your-secret-passphrase'
    );
    echo "Signed block created: " . $block->id . "\n";
    echo "Hash: " . $block->hash . "\n\n";

    // Optional: Sign and verify locally without an API call
    echo "=== Local Sign / Verify ===\n";
    $content = ['order_id' => 'ORD-001', 'amount' => 150.00];
    $publicKeyPem = file_get_contents('./keys/my-company.public.pem');

    $result = $signer->signContent($content, $privateKeyPem, 'your-secret-passphrase');
    echo "Signature: " . substr($result['signature'], 0, 32) . "...\n";

    $valid = $signer->verifySignature($content, $result['signature'], $publicKeyPem);
    echo "Verification: " . ($valid ? "valid" : "INVALID") . "\n";

} catch (SowasitException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
