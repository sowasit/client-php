<?php

namespace SoWasIt\Types;

class User
{
    public string $id;
    public string $email;
    public string $firstName;
    public string $lastName;
    public string $role;
    public bool $active;
    public string $created_at;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->firstName = $data['firstName'] ?? '';
        $this->lastName = $data['lastName'] ?? '';
        $this->role = $data['role'] ?? '';
        $this->active = $data['active'] ?? true;
        $this->created_at = $data['created_at'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'role' => $this->role,
            'active' => $this->active,
            'created_at' => $this->created_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
