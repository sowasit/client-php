<?php

namespace SoWasIt\Types;

class Tenant
{
    public string $id;
    public string $name;
    public string $type; // 'personal' | 'organization'
    public string $created_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->type = $data['type'] ?? 'personal';
        $this->created_at = $data['created_at'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'created_at' => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
