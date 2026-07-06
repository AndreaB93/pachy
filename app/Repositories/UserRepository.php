<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\{DB, RepositoryInterface};
use App\Schema\UserSchema as S;

class UserRepository implements RepositoryInterface
{
    public function findById(int $id): ?array
    {
        return DB::row(
            "SELECT " . S::columns([S::PASSWORD]) . " FROM " . S::TABLE . " WHERE " . S::ID . " = ?",
            [$id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return DB::row(
            "SELECT * FROM " . S::TABLE . " WHERE " . S::EMAIL . " = ?",
            [$email]
        );
    }

    public function findAll(array $filters = []): array
    {
        $where  = "WHERE 1=1";
        $params = [];

        if (isset($filters['role'])) {
            $where   .= " AND " . S::ROLE . " = ?";
            $params[] = $filters['role'];
        }
        if (isset($filters['active'])) {
            $where   .= " AND " . S::ACTIVE . " = ?";
            $params[] = (int)$filters['active'];
        }

        return DB::query(
            "SELECT " . S::columns([S::PASSWORD]) . " FROM " . S::TABLE . " $where ORDER BY " . S::CREATED . " DESC",
            $params
        );
    }

    public function create(array $data): int
    {
        DB::execute(
            "INSERT INTO " . S::TABLE . " (" . S::NAME . ", " . S::EMAIL . ", " . S::PASSWORD . ", " . S::ROLE . ")
             VALUES (?, ?, ?, ?)",
            [$data[S::NAME], $data[S::EMAIL], $data[S::PASSWORD], $data[S::ROLE]]
        );
        return (int)DB::lastId();
    }

    public function update(int $id, array $data): bool
    {
        $affected = DB::execute(
            "UPDATE " . S::TABLE . " SET " . S::NAME . " = ?, " . S::ROLE . " = ? WHERE " . S::ID . " = ?",
            [$data[S::NAME], $data[S::ROLE], $id]
        );
        return $affected > 0;
    }

    public function delete(int $id): bool
    {
        return DB::execute(
            "DELETE FROM " . S::TABLE . " WHERE " . S::ID . " = ?",
            [$id]
        ) > 0;
    }

    public function setActive(int $id, bool $active): bool
    {
        return DB::execute(
            "UPDATE " . S::TABLE . " SET " . S::ACTIVE . " = ? WHERE " . S::ID . " = ?",
            [(int)$active, $id]
        ) > 0;
    }
}
