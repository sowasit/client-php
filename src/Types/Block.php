<?php

namespace SoWasIt\Types;

class Block
{
    public string $id;
    public string $chain_id;
    public array $data;
    public string $previous_hash;
    public string $hash;
    public string $created_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? '';
        $this->chain_id = $data['chain_id'] ?? '';
        $this->data = $data['data'] ?? [];
        $this->previous_hash = $data['previous_hash'] ?? '';
        $this->hash = $data['hash'] ?? '';
        $this->created_at = $data['created_at'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'chain_id' => $this->chain_id,
            'data' => $this->data,
            'previous_hash' => $this->previous_hash,
            'hash' => $this->hash,
            'created_at' => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
