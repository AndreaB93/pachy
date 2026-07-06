<?php
declare(strict_types=1);

namespace App\Services;

use App\DTOs\{CreateUserDTO, UpdateUserDTO};
use App\Repositories\UserRepository;
use App\Schema\UserSchema as S;
use Core\{DB, Validator};

class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Validator $validator,
    ) {}

    public function register(CreateUserDTO $dto): array
    {
        // Map DTO properties to DB column names for validation
        $this->validator->validate([
            S::NAME  => $dto->name,
            S::EMAIL => $dto->email,
            S::ROLE  => $dto->role,
        ], S::rules());

        if ($this->users->findByEmail($dto->email)) {
            throw new \DomainException('Email already registered.');
        }

        $id = $this->users->create([
            S::NAME     => $dto->name,
            S::EMAIL    => $dto->email,
            S::PASSWORD => password_hash($dto->password, PASSWORD_ARGON2ID),
            S::ROLE     => $dto->role,
        ]);

        return $this->users->findById($id);
    }

    public function authenticate(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user[S::PASSWORD])) {
            throw new \RuntimeException('Invalid credentials.', 401);
        }
        // Return user without password
        unset($user[S::PASSWORD]);
        return $user;
    }

    public function getById(int $id): array
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            throw new \RuntimeException('User not found.', 404);
        }
        return $user;
    }

    public function list(array $filters = []): array
    {
        return $this->users->findAll($filters);
    }

    public function update(UpdateUserDTO $dto): array
    {
        $data = [
            S::NAME => $dto->name,
            S::ROLE => $dto->role ?? $this->getById($dto->id)[S::ROLE],
        ];

        $this->users->update($dto->id, $data);
        return $this->getById($dto->id);
    }

    public function delete(int $id): void
    {
        $this->getById($id); // throws 404 if not found
        $this->users->delete($id);
    }
}
