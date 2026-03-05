<?php

namespace SoWasIt\Types;

class ApiKey
{
    public string $id;
    public string $name;
    public string $key_hash;
    public array $permissions;
    public bool $is_active;
    public ?string $expires_at;
    public string $created_at;
    public ?string $last_used;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->key_hash = $data['key_hash'] ?? '';
        $this->permissions = $data['permissions'] ?? [];
        $this->is_active = $data['is_active'] ?? true;
        $this->expires_at = $data['expires_at'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
        $this->last_used = $data['last_used'] ?? null;
    }

    public function toArray(): array
    {
        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'key_hash' => $this->key_hash,
            'permissions' => $this->permissions,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];

        if ($this->expires_at !== null) {
            $result['expires_at'] = $this->expires_at;
        }

        if ($this->last_used !== null) {
            $result['last_used'] = $this->last_used;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
