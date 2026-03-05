<?php

namespace SoWasIt;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use SoWasIt\Exception\SowasitException;
use SoWasIt\Types\{Chain, Block, ApiKey};

class SowasitClient
{
    private string $baseUrl;
    private ?string $apiKey;
    private ?string $token;
    private Client $httpClient;
    private int $timeout;

    public function __construct(array $config)
    {
        if (empty($config['baseUrl'])) {
            throw new SowasitException('baseUrl is required in SowasitClient config');
        }

        $this->baseUrl = rtrim($config['baseUrl'], '/');
        $this->apiKey = $config['apiKey'] ?? null;
        $this->token = null;
        $this->timeout = $config['timeout'] ?? 30;

        $this->httpClient = new Client();
    }

    private function getHeaders(bool $preferApiKey = false): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($preferApiKey && $this->apiKey) {
            $headers['X-API-Key'] = $this->apiKey;
        } elseif ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        } elseif ($this->apiKey) {
            $headers['X-API-Key'] = $this->apiKey;
        }

        return $headers;
    }

    private function request(string $method, string $path, ?array $data = null, bool $preferApiKey = false): array
    {
        try {
            $options = [
                'headers' => $this->getHeaders($preferApiKey),
                'timeout' => $this->timeout,
            ];

            if ($data !== null) {
                $options['json'] = $data;
            }

            $response = $this->httpClient->request(
                $method,
                $this->baseUrl . $path,
                $options
            );

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $errorData = json_decode($response->getBody()->getContents(), true) ?? [];
                throw new SowasitException(
                    $errorData['message'] ?? $errorData['error'] ?? 'HTTP ' . $response->getStatusCode()
                );
            }
            throw new SowasitException($e->getMessage());
        }
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function clearToken(): void
    {
        $this->token = null;
    }

    public function loginWithApiKey(string $apiKey): array
    {
        $response = $this->request('POST', '/auth/api/login', [
            'api_key' => $apiKey,
        ], true);

        if (!empty($response['token'])) {
            $this->token = $response['token'];
        }

        return $response;
    }

    public function registerPublicKey(array $request): array
    {
        return $this->request('POST', '/keys/register', [
            'enrollment_token' => $request['enrollmentToken'],
            'public_key' => $request['publicKey'],
            'algorithm' => $request['algorithm'] ?? 'ECDSA-P256',
            'client_info' => $request['clientInfo'] ?? null,
        ]);
    }

    public function getPublicKeys(): array
    {
        return $this->request('GET', '/keys');
    }

    public function chains(): ChainsManager
    {
        return new ChainsManager($this);
    }

    public function blocks(): BlocksManager
    {
        return new BlocksManager($this);
    }

    public function apiKeys(): ApiKeysManager
    {
        return new ApiKeysManager($this);
    }

    public function health(): array
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/health');
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            throw new SowasitException('Health check failed: ' . $e->getMessage());
        }
    }

    public function performRequest(string $method, string $path, ?array $data = null, bool $preferApiKey = false): array
    {
        return $this->request($method, $path, $data, $preferApiKey);
    }
}

class ChainsManager
{
    private SowasitClient $client;

    public function __construct(SowasitClient $client)
    {
        $this->client = $client;
    }

    public function list(
        string $scope = 'tenant',
        ?string $type = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $params = [
            'scope' => $scope,
            'page' => $page,
            'limit' => $limit,
        ];

        if ($type !== null) {
            $params['type'] = $type;
        }

        $path = '/chains?' . http_build_query($params);
        $response = $this->client->performRequest('GET', $path);

        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }

    public function create(
        string $id,
        string $name,
        ?array $options = null
    ): Chain {
        $options = $options ?? [];
        $data = array_merge([
            'id' => $id,
            'name' => $name,
        ], $options);

        $response = $this->client->performRequest('POST', '/chains', $data);

        if (empty($response['chain'])) {
            throw new SowasitException('Failed to create chain');
        }

        return new Chain($response['chain']);
    }

    public function get(string $chainId): Chain
    {
        $response = $this->client->performRequest('GET', "/chains/$chainId");

        if (empty($response['data']) || is_array($response['data'])) {
            throw new SowasitException('Chain not found');
        }

        return new Chain($response['data']);
    }

    public function export(string $chainId): array
    {
        $response = $this->client->performRequest('GET', "/chains/$chainId/export");

        if (empty($response['data'])) {
            throw new SowasitException('Failed to export chain');
        }

        return $response['data'];
    }
}

class BlocksManager
{
    private SowasitClient $client;

    public function __construct(SowasitClient $client)
    {
        $this->client = $client;
    }

    public function list(
        string $chainId,
        int $limit = 50,
        int $offset = 0
    ): array {
        $params = [
            'limit' => $limit,
            'offset' => $offset,
        ];

        $path = "/chains/$chainId/blocks?" . http_build_query($params);
        $response = $this->client->performRequest('GET', $path);

        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }

    public function create(string $chainId, array $blockData): Block
    {
        $response = $this->client->performRequest('POST', "/chains/$chainId/blocks", [
            'data' => $blockData,
        ]);

        if (empty($response['block'])) {
            throw new SowasitException('Failed to create block');
        }

        return new Block($response['block']);
    }

    public function createSigned(string $chainId, array $content, string $privateKeyPem, string $publicKeyId, ?string $passphrase = null): Block
    {
        $signatureManager = new \SoWasIt\Crypto\SignatureManager();
        $result = $signatureManager->signContent($content, $privateKeyPem, $passphrase);

        $response = $this->client->performRequest('POST', "/chains/$chainId/blocks", [
            'content' => $content,
            'signature' => $result['signature'],
            'public_key_id' => $publicKeyId,
            'block_type' => 'data',
        ]);

        if (empty($response['block'])) {
            throw new SowasitException('Failed to create signed block');
        }

        return new Block($response['block']);
    }

    public function get(string $chainId, string $blockId): Block
    {
        $response = $this->client->performRequest('GET', "/chains/$chainId/blocks/$blockId");

        if (empty($response['data']) || is_array($response['data'])) {
            throw new SowasitException('Block not found');
        }

        return new Block($response['data']);
    }

    public function getLatest(string $chainId): Block
    {
        $response = $this->client->performRequest('GET', "/chains/$chainId/blocks/latest");

        if (empty($response['data']) || is_array($response['data'])) {
            throw new SowasitException('No blocks found in chain');
        }

        return new Block($response['data']);
    }
}

class ApiKeysManager
{
    private SowasitClient $client;

    public function __construct(SowasitClient $client)
    {
        $this->client = $client;
    }

    public function create(string $name, array $permissions = ['read', 'write'], ?int $expiresIn = null): string
    {
        $data = [
            'name' => $name,
            'permissions' => $permissions,
        ];

        if ($expiresIn !== null) {
            $data['expiresIn'] = $expiresIn;
        }

        $response = $this->client->performRequest('POST', '/api-keys', $data);

        if (empty($response['key'])) {
            throw new SowasitException('Failed to create API key');
        }

        return $response['key'];
    }

    public function list(): array
    {
        $response = $this->client->performRequest('GET', '/api-keys');
        return $response['data'] ?? [];
    }

    public function delete(string $id): bool
    {
        $response = $this->client->performRequest('DELETE', "/api-keys/$id");
        return $response['success'] ?? false;
    }
}
