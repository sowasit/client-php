<?php

namespace SoWasIt\Types;

class Chain
{
    public string $id;
    public string $name;
    public ?string $description;
    public string $type; // 'data' | 'anchoring'
    public string $visibility; // 'private' | 'public'
    public string $tenant_id;
    public string $created_at;
    public string $updated_at;
    public ?string $anchoring_id;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? null;
        $this->type = $data['type'] ?? 'data';
        $this->visibility = $data['visibility'] ?? 'private';
        $this->tenant_id = $data['tenant_id'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
        $this->anchoring_id = $data['anchoring_id'] ?? null;
    }

    public function toArray(): array
    {
        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'visibility' => $this->visibility,
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->description !== null) {
            $result['description'] = $this->description;
        }

        if ($this->anchoring_id !== null) {
            $result['anchoring_id'] = $this->anchoring_id;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
