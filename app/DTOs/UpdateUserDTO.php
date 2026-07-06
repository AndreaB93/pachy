<?php
declare(strict_types=1);

namespace App\DTOs;

readonly class UpdateUserDTO
{
    public function __construct(
        public int     $id,
        public string  $name,
        public ?string $role = null,
    ) {}

    /** Factory from raw HTTP input array */
    public static function fromArray(int $id, array $data): self
    {
        return new self(
            id:   $id,
            name: trim($data['name'] ?? ''),
            role: $data['role'] ?? null,
        );
    }
}
