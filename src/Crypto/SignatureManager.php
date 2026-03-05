<?php

namespace SoWasIt\Crypto;

use SoWasIt\Exception\SowasitException;

class SignatureManager
{
    /**
     * Generate an ECDSA P-256 key pair.
     *
     * Returns an array with keys: privateKey (PEM), publicKey (PEM), fingerprint, keyId.
     */
    public function generateKeyPair(?string $passphrase = null): array
    {
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ];

        $keyResource = openssl_pkey_new($config);

        if ($keyResource === false) {
            throw new SowasitException('Failed to generate key pair: ' . openssl_error_string());
        }

        $exportOptions = $passphrase ? ['encrypt_key' => true, 'encrypt_key_cipher' => OPENSSL_CIPHER_AES_256_CBC] : [];
        $privateKeyPem = '';
        $exported = openssl_pkey_export($keyResource, $privateKeyPem, $passphrase, $exportOptions);

        if (!$exported) {
            throw new SowasitException('Failed to export private key: ' . openssl_error_string());
        }

        $details = openssl_pkey_get_details($keyResource);
        if ($details === false) {
            throw new SowasitException('Failed to get key details: ' . openssl_error_string());
        }

        $publicKeyPem = $details['key'];
        $fingerprint = hash('sha256', $publicKeyPem);
        $keyId = substr($fingerprint, 0, 16);

        if ($passphrase === null) {
            trigger_error('Warning: Private key is not encrypted. Consider using a passphrase for better security.', E_USER_WARNING);
        }

        return [
            'privateKey' => $privateKeyPem,
            'publicKey' => $publicKeyPem,
            'fingerprint' => $fingerprint,
            'keyId' => $keyId,
            'algorithm' => 'ECDSA-P256',
        ];
    }

    /**
     * Save a key pair to disk.
     * Private key is saved with mode 0600.
     */
    public function saveKeyPair(array $keyPair, string $outputDir = './keys', string $name = 'sowasit'): void
    {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $privateKeyPath = $outputDir . '/' . $name . '.private.pem';
        $publicKeyPath = $outputDir . '/' . $name . '.public.pem';
        $metadataPath = $outputDir . '/' . $name . '.metadata.json';

        file_put_contents($privateKeyPath, $keyPair['privateKey']);
        chmod($privateKeyPath, 0600);

        file_put_contents($publicKeyPath, $keyPair['publicKey']);

        file_put_contents($metadataPath, json_encode([
            'keyId' => $keyPair['keyId'],
            'fingerprint' => $keyPair['fingerprint'],
            'algorithm' => 'ECDSA-P256',
            'createdAt' => (new \DateTime())->format(\DateTime::ATOM),
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Sign content with a private key (ECDSA P-256, SHA-256).
     * Uses the same deterministic JSON serialization as @sowasit/signer (keys sorted recursively).
     *
     * @param array $content  The data to sign
     * @param string $privateKeyPem  PEM-encoded private key
     * @param string|null $passphrase  Optional passphrase for encrypted keys
     * @return array  ['signature' => string (base64), 'algorithm' => 'ECDSA-P256']
     */
    public function signContent(array $content, string $privateKeyPem, ?string $passphrase = null): array
    {
        $keyResource = openssl_pkey_get_private($privateKeyPem, $passphrase ?? '');

        if ($keyResource === false) {
            if ($passphrase !== null) {
                throw new SowasitException('Failed to load private key. Check your passphrase.');
            }
            throw new SowasitException('Failed to load private key: ' . openssl_error_string());
        }

        $dataString = self::deterministicJsonEncode($content);
        $rawSignature = '';
        $signed = openssl_sign($dataString, $rawSignature, $keyResource, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            throw new SowasitException('Failed to sign content: ' . openssl_error_string());
        }

        return [
            'signature' => base64_encode($rawSignature),
            'algorithm' => 'ECDSA-P256',
        ];
    }

    /**
     * Verify a signature (ECDSA P-256, SHA-256).
     * Uses the same deterministic JSON serialization as @sowasit/chain-verifier.
     *
     * @param array $content  The original data
     * @param string $signature  Base64-encoded signature
     * @param string $publicKeyPem  PEM-encoded public key
     * @return bool
     */
    public function verifySignature(array $content, string $signature, string $publicKeyPem): bool
    {
        $keyResource = openssl_pkey_get_public($publicKeyPem);

        if ($keyResource === false) {
            return false;
        }

        $dataString = self::deterministicJsonEncode($content);
        $rawSignature = base64_decode($signature, true);

        if ($rawSignature === false) {
            return false;
        }

        $result = openssl_verify($dataString, $rawSignature, $keyResource, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    /**
     * Compute SHA-256 fingerprint of a public key.
     */
    public function getFingerprint(string $publicKeyPem): string
    {
        return hash('sha256', $publicKeyPem);
    }

    /**
     * Deterministic JSON serialization — equivalent to json-stringify-deterministic (npm).
     * Recursively sorts object keys alphabetically. Arrays preserve order.
     * This ensures signing produces the same string as the JS/Node.js signer.
     */
    public static function deterministicJsonEncode(mixed $data): string
    {
        if (is_array($data)) {
            if (self::isAssoc($data)) {
                ksort($data);
                $parts = [];
                foreach ($data as $key => $value) {
                    $parts[] = json_encode((string) $key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        . ':'
                        . self::deterministicJsonEncode($value);
                }
                return '{' . implode(',', $parts) . '}';
            } else {
                $parts = [];
                foreach ($data as $value) {
                    $parts[] = self::deterministicJsonEncode($value);
                }
                return '[' . implode(',', $parts) . ']';
            }
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function isAssoc(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
