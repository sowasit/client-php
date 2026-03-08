# SoWasIt PHP Client Library

Official PHP client for integrating SoWasIt blockchain into your applications. Create blocks when events happen, store immutable records of your app's actions, and let the SoWasIt dashboard handle verification and management.

**Perfect for:** Recording user actions, transaction logs, audit trails, IoT sensor data, or any immutable event tracking.

**Website:** [sowasit.io](https://sowasit.io)  
**Dashboard:** [sowasit.io/dashboard](https://sowasit.io/dashboard)


---
## Installation

### Step 0: Check Requirements (Optional)

If you're not sure whether your environment meets the requirements, run our diagnostic script:

#### On Linux/macOS:
```bash
curl -sS https://raw.githubusercontent.com/sowasit/client-php/main/check-requirements.sh | bash
# Or if you've cloned the repository:
./check-requirements.sh
```

#### On Windows:
```
# Download and run the batch file
curl -sS https://raw.githubusercontent.com/sowasit/client-php/main/check-requirements.cmd -o check-requirements.cmd
check-requirements.cmd
```

The script will verify:

✅ PHP version (7.4 or higher required)

✅ Required extensions (intl, curl, mbstring, json)

✅ Composer installation

🔧 Provide installation instructions if anything is missing



### Step 1: Install via Composer

```bash
composer require sowasit/client-php
```

> **Requires** PHP 7.4+ with `ext-openssl` (enabled by default on most PHP installations) and `php-intl`.

### Step 2: Create Your API Key

This library uses **API Key** authentication (stateless, backend-to-backend):

1. Go to [https://sowasit.io](https://sowasit.io)
2. Create an account and log in
3. In your dashboard, click **"API Keys"**
4. Click **"Create New API Key"**
5. Copy the key (it's shown only once!)

**Create your own chain:**

All users can create their own private chains in the dashboard:
- **Free plan**: 1 private chain included
- **Starter plan**: Up to 5 private chains
- **Pro plan**: Up to 20 private chains
- **Business plan**: Unlimited private chains

### Step 3: Create a `.env` File (Optional)

Create a file named `.env` in your project folder:

```
SOWASIT_API_URL=https://api.sowasit.io/v1
SOWASIT_API_KEY=live_xxxxx
```

---

## Quick Start

### Basic Authentication

```php
<?php

require 'vendor/autoload.php';

use SoWasIt\SowasitClient;

$client = new SowasitClient([
    'baseUrl' => 'https://api.sowasit.io/v1',
    'apiKey' => 'live_xxxxx',
]);
```

That's it! The API key is automatically included in all requests via the `X-API-Key` header. No separate login call needed.

---

## Usage Examples

### 1. Create a Chain

```php
$chain = $client->chains()->create(
    'my-chain-id',
    'My Chain Name',
    [
        'description' => 'A chain for recording events',
        'visibility' => 'private',  // or 'public'
        'type' => 'data',           // or 'anchoring'
    ]
);

echo "Chain created: " . $chain->id . " - " . $chain->name;
```

### 2. Create a Block

Record an event in the blockchain:

```php
$block = $client->blocks()->create('my-chain-id', [
    'event' => 'user_signup',
    'email' => 'john@example.com',
    'firstName' => 'John',
    'lastName' => 'Doe',
    'timestamp' => date('c'),
]);

echo "Block created: " . $block->id;
echo "Hash: " . $block->hash;
```

### 3. Retrieve a Chain

```php
$chain = $client->chains()->get('my-chain-id');

echo "Chain: " . $chain->name;
echo "Type: " . $chain->type;
echo "Visibility: " . $chain->visibility;
echo "Created: " . $chain->created_at;
```

### 4. Retrieve a Block

```php
$block = $client->blocks()->get('my-chain-id', 'block-id-here');

echo "Block ID: " . $block->id;
echo "Data: " . json_encode($block->data);
echo "Hash: " . $block->hash;
```

### 5. Export Chain to JSON

Get all blocks and metadata from a chain:

```php
$export = $client->chains()->export('my-chain-id');

echo "Total blocks: " . $export['stats']['total_blocks'];
echo "Chain: " . json_encode($export['chain']);
echo "Blocks: " . json_encode($export['blocks']);

// Save to file
file_put_contents(
    'chain-export.json',
    json_encode($export, JSON_PRETTY_PRINT)
);
```

### 6. List Chains

```php
$chains = $client->chains()->list(
    'tenant',  // scope: 'tenant', 'public', or 'all'
    'data',    // optional type filter
    1,         // page
    20         // limit per page
);

foreach ($chains as $chain) {
    echo "- " . $chain['name'] . " (ID: " . $chain['id'] . ")";
}
```

### 7. List Blocks

```php
$blocks = $client->blocks()->list(
    'my-chain-id',
    50,   // limit
    0     // offset
);

foreach ($blocks as $block) {
    echo "- Block " . $block['id'] . " at " . $block['created_at'];
}
```

### 8. Get Latest Block

```php
$block = $client->blocks()->getLatest('my-chain-id');

echo "Latest block: " . $block->id;
echo "Created: " . $block->created_at;
```

---

## Advanced Usage

### Using Environment Variables

Create a `.env` file:

```
SOWASIT_API_URL=https://api.sowasit.io/v1
SOWASIT_API_KEY=live_xxxxx
```

Use in your code:

```php
$client = new SowasitClient([
    'baseUrl' => $_ENV['SOWASIT_API_URL'] ?? 'https://api.sowasit.io/v1',
    'apiKey' => $_ENV['SOWASIT_API_KEY'],
    'timeout' => 30,
]);

$client->loginWithApiKey($_ENV['SOWASIT_API_KEY']);
```

### Health Check

Check if the API is healthy:

```php
$health = $client->health();

echo "Status: " . $health['status'];
echo "Version: " . $health['version'];
```

---

## Real-World Examples

### Example 1: Record User Signups

```php
<?php

require 'vendor/autoload.php';

use SoWasIt\SowasitClient;

$client = new SowasitClient([
    'baseUrl' => $_ENV['SOWASIT_API_URL'],
    'apiKey' => $_ENV['SOWASIT_API_KEY'],
]);

$client->loginWithApiKey($_ENV['SOWASIT_API_KEY']);

function handleUserSignup($email, $firstName, $lastName) {
    global $client;
    
    try {
        // Your normal signup logic
        echo "User signed up: $email\n";

        // Record in blockchain
        $block = $client->blocks()->create('public-events', [
            'event' => 'user_signup',
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'timestamp' => date('c'),
        ]);

        echo "✅ Recorded in blockchain: " . $block->id;
        return true;
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage();
        return false;
    }
}

handleUserSignup('john@example.com', 'John', 'Doe');
```

### Example 2: Record Payment Transactions

```php
<?php

require 'vendor/autoload.php';

use SoWasIt\SowasitClient;

$client = new SowasitClient([
    'baseUrl' => $_ENV['SOWASIT_API_URL'],
    'apiKey' => $_ENV['SOWASIT_API_KEY'],
]);

$client->loginWithApiKey($_ENV['SOWASIT_API_KEY']);

function processAndRecordPayment($userId, $amount, $currency = 'USD') {
    global $client;
    
    try {
        // Process payment
        $paymentId = generatePaymentId();
        processPaymentWithGateway($userId, $amount, $currency);

        // Record in blockchain
        $block = $client->blocks()->create('transactions', [
            'type' => 'payment',
            'userId' => $userId,
            'amount' => $amount,
            'currency' => $currency,
            'paymentId' => $paymentId,
            'status' => 'completed',
            'timestamp' => date('c'),
        ]);

        echo "✅ Payment recorded: " . $block->id;
        return $paymentId;
    } catch (Exception $e) {
        echo "❌ Payment error: " . $e->getMessage();
        throw $e;
    }
}

function generatePaymentId() {
    return 'PAY-' . time() . '-' . rand(1000, 9999);
}

function processPaymentWithGateway($userId, $amount, $currency) {
    // Your payment processor logic here
}

processAndRecordPayment('user-123', 99.99, 'USD');
```

### Example 3: Export Audit Trail

```php
<?php

require 'vendor/autoload.php';

use SoWasIt\SowasitClient;

$client = new SowasitClient([
    'baseUrl' => $_ENV['SOWASIT_API_URL'],
    'apiKey' => $_ENV['SOWASIT_API_KEY'],
]);

$client->loginWithApiKey($_ENV['SOWASIT_API_KEY']);

function generateAuditReport($chainId) {
    global $client;
    
    try {
        // Export chain
        $export = $client->chains()->export($chainId);

        // Prepare report
        $report = [
            'generated_at' => date('c'),
            'chain_id' => $chainId,
            'total_blocks' => $export['stats']['total_blocks'],
            'period' => [
                'start' => $export['stats']['first_block_created'],
                'end' => $export['stats']['last_block_created'],
            ],
            'blocks' => [],
        ];

        // Add block summaries
        foreach ($export['blocks'] as $block) {
            $report['blocks'][] = [
                'id' => $block['id'],
                'hash' => $block['hash'],
                'created_at' => $block['created_at'],
                'data' => $block['data'],
            ];
        }

        // Save as JSON
        $filename = 'audit-' . date('Y-m-d-H-i-s') . '.json';
        file_put_contents(
            $filename,
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        echo "✅ Audit report generated: " . $filename;
        echo "   Total events recorded: " . $report['total_blocks'];

        return $filename;
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage();
        throw $e;
    }
}

generateAuditReport('audit-trail');
```

---

## Cryptographic Signatures (Partners)

For partners who need to sign blocks with their own ECDSA private key, use `SignatureManager`. This guarantees non-repudiation — only the holder of the private key can produce valid signatures.

### 1. Generate and save a key pair

```php
<?php

require 'vendor/autoload.php';

use SoWasIt\Crypto\SignatureManager;

$signer = new SignatureManager();

$keyPair = $signer->generateKeyPair('your-secret-passphrase');

$signer->saveKeyPair($keyPair, './keys', 'my-company');

echo "Key ID: " . $keyPair['keyId'] . "\n";
echo "Fingerprint: " . $keyPair['fingerprint'] . "\n";
```

Files created in `./keys/`:
- `my-company.private.pem` (mode 0600 — keep this SECRET)
- `my-company.public.pem`
- `my-company.metadata.json`

### 2. Register the public key with SoWasIt

After generating your key, register the public key using an enrollment token from the chain owner:

```php
$client = new SowasitClient([
    'baseUrl' => 'https://api.sowasit.io/v1',
    'apiKey' => 'live_xxxxx',
]);

$response = $client->registerPublicKey([
    'enrollmentToken' => 'your_enrollment_token',
    'publicKey' => $keyPair['publicKey'],
    'algorithm' => 'ECDSA-P256',
    'clientInfo' => [
        'name' => 'Your Company',
        'siret' => '12345678901234',
        'contact' => 'admin@company.com',
    ],
]);

echo "Key registered. ID: " . $response['data']['key_id'] . "\n";
echo "Status: " . $response['data']['status'] . "\n";
```

### 3. Create a signed block

```php
$privateKeyPem = file_get_contents('./keys/my-company.private.pem');

$content = [
    'event' => 'product_shipped',
    'order_id' => 'ORD-12345',
    'timestamp' => (new DateTime())->format(DateTime::ATOM),
];

$block = $client->blocks()->createSigned(
    'my-chain-id',
    $content,
    $privateKeyPem,
    'your_key_id',       // returned by registerPublicKey
    'your-secret-passphrase'
);

echo "Signed block created: " . $block->id . "\n";
echo "Hash: " . $block->hash . "\n";
```

### 4. Sign and verify locally

```php
use SoWasIt\Crypto\SignatureManager;

$signer = new SignatureManager();

$content = ['order_id' => 'ORD-001', 'amount' => 150.00];
$privateKeyPem = file_get_contents('./keys/my-company.private.pem');
$publicKeyPem = file_get_contents('./keys/my-company.public.pem');

$result = $signer->signContent($content, $privateKeyPem, 'your-passphrase');
echo "Signature: " . $result['signature'] . "\n";

$valid = $signer->verifySignature($content, $result['signature'], $publicKeyPem);
echo $valid ? "✅ Valid signature\n" : "❌ Invalid signature\n";
```

> **Compatibility**: The PHP signing implementation uses the same algorithm and deterministic JSON serialization as `@sowasit/signer` (Node.js). Signatures produced in PHP can be verified by `@sowasit/chain-verifier` and vice versa.

---

## API Reference

### SowasitClient

#### Constructor

```php
$client = new SowasitClient(array $config)
```

**Config options:**
- `baseUrl` (string, required): The API base URL
- `apiKey` (string, optional): Your API key for authentication. When provided, it's used in the `X-API-Key` header for all requests
- `timeout` (int, optional): Request timeout in seconds (default: 30)

#### Methods

- `loginWithApiKey(string $apiKey): array` - Authenticate with API key, stores the returned token for subsequent calls
- `chains(): ChainsManager` - Access chain operations
- `blocks(): BlocksManager` - Access block operations
- `apiKeys(): ApiKeysManager` - Manage dashboard API keys
- `registerPublicKey(array $request): array` - Register a partner public key using an enrollment token
- `getPublicKeys(): array` - List registered public keys
- `health(): array` - Check API health status

### ChainsManager

```php
$chainsManager = $client->chains()
```

#### Methods

- `list(?string $scope = 'tenant', ?string $type = null, int $page = 1, int $limit = 20): array`
- `create(string $id, string $name, ?array $options = null): Chain`
- `get(string $chainId): Chain`
- `export(string $chainId): array`

### BlocksManager

```php
$blocksManager = $client->blocks()
```

#### Methods

- `list(string $chainId, int $limit = 50, int $offset = 0): array`
- `create(string $chainId, array $data): Block`
- `createSigned(string $chainId, array $content, string $privateKeyPem, string $publicKeyId, ?string $passphrase = null): Block`
- `get(string $chainId, string $blockId): Block`
- `getLatest(string $chainId): Block`

### ApiKeysManager

```php
$apiKeysManager = $client->apiKeys()
```

#### Methods

- `create(string $name, array $permissions = ['read', 'write'], ?int $expiresIn = null): string`
- `list(): array`
- `delete(string $id): bool`

### SignatureManager

```php
use SoWasIt\Crypto\SignatureManager;
$signer = new SignatureManager();
```

#### Methods

- `generateKeyPair(?string $passphrase = null): array` - Generate an ECDSA P-256 key pair
- `saveKeyPair(array $keyPair, string $outputDir = './keys', string $name = 'sowasit'): void`
- `signContent(array $content, string $privateKeyPem, ?string $passphrase = null): array` - Returns `['signature' => string, 'algorithm' => 'ECDSA-P256']`
- `verifySignature(array $content, string $signature, string $publicKeyPem): bool`
- `getFingerprint(string $publicKeyPem): string`
- `static deterministicJsonEncode(mixed $data): string` - Same output as `json-stringify-deterministic` (npm)

---

## Type Definitions

### Chain

```php
class Chain {
    public string $id;
    public string $name;
    public ?string $description;
    public string $type;           // 'data' | 'anchoring'
    public string $visibility;     // 'private' | 'public'
    public string $tenant_id;
    public string $created_at;
    public string $updated_at;
    public ?string $anchoring_id;
}
```

### Block

```php
class Block {
    public string $id;
    public string $chain_id;
    public array $data;
    public string $previous_hash;
    public string $hash;
    public string $created_at;
}
```

### User

```php
class User {
    public string $id;
    public string $email;
    public string $firstName;
    public string $lastName;
    public string $role;
    public bool $active;
    public string $created_at;
}
```

### ApiKey

```php
class ApiKey {
    public string $id;
    public string $name;
    public string $key_hash;
    public array $permissions;
    public bool $is_active;
    public ?string $expires_at;
    public string $created_at;
    public ?string $last_used;
}
```

### Tenant

```php
class Tenant {
    public string $id;
    public string $name;
    public string $type;           // 'personal' | 'organization'
    public string $created_at;
}
```

---

## Error Handling

All errors throw `SowasitException`:

```php
<?php

use SoWasIt\SowasitClient;
use SoWasIt\Exception\SowasitException;

$client = new SowasitClient([
    'baseUrl' => 'https://api.sowasit.io/v1',
    'apiKey' => 'live_xxxxx',
]);

try {
    $block = $client->blocks()->create('my-chain', [
        'event' => 'test',
    ]);
} catch (SowasitException $e) {
    echo "Error: " . $e->getMessage();
    // Handle error appropriately
}
```

---

## Examples

See the `examples/` directory for complete working examples:

- **`01-basic-auth.php`** - Authentication with API key
- **`02-chains-operations.php`** - Create, list, get, and export chains
- **`03-blocks-operations.php`** - Create, list, and retrieve blocks
- **`04-complete-workflow.php`** - Full end-to-end workflow

To run examples:

```bash
cd examples
php 01-basic-auth.php
php 02-chains-operations.php
php 03-blocks-operations.php
php 04-complete-workflow.php
```

---

## License

MIT License - See LICENSE file for details

---

## Support

For support, documentation, and more information:
- Website: [sowasit.io](https://sowasit.io)
- Dashboard: [sowasit.io](https://sowasit.io)
- Issues: Report problems on GitHub
