<?php
declare(strict_types=1);

namespace App\DTOs;

readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role = 'user',
    ) {}

    /** Factory from raw HTTP input array */
    public static function fromArray(array $data): self
    {
        return new self(
            name:     trim($data['name'] ?? ''),
            email:    strtolower(trim($data['email'] ?? '')),
            password: $data['password'] ?? '',
            role:     $data['role'] ?? 'user',
        );
    }
}
