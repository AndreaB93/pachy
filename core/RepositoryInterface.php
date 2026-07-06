<?php
declare(strict_types=1);

namespace Core;

interface RepositoryInterface
{
    public function findById(int $id): ?array;
    public function findAll(array $filters = []): array;
    public function create(array $data): int; // returns new ID

    #[\NoDiscard]
    public function update(int $id, array $data): bool;

    #[\NoDiscard]
    public function delete(int $id): bool;
}
