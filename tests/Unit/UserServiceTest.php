<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\CreateUserDTO;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Schema\UserSchema as S;
use Core\Validator;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    // ─── register ────────────────────────────────────────────────────────────

    public function test_register_throws_on_duplicate_email(): void
    {
        $fakeRepo = new class extends UserRepository {
            public function findByEmail(string $email): ?array
            {
                return ['id' => 1, 'email' => $email]; // simulates existing user
            }
            public function create(array $data): int { return 0; }
        };

        $service = new UserService($fakeRepo, new Validator());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email already registered.');

        $service->register(CreateUserDTO::fromArray([
            'name'     => 'Test User',
            'email'    => 'exists@test.com',
            'password' => 'secret123',
        ]));
    }

    public function test_register_returns_user_without_password(): void
    {
        $fakeRepo = new class extends UserRepository {
            public function findByEmail(string $email): ?array { return null; }
            public function create(array $data): int { return 42; }
            public function findById(int $id): ?array
            {
                return ['id' => 42, 'full_name' => 'Test User', 'email' => 'new@test.com', 'role' => 'user'];
            }
        };

        $service = new UserService($fakeRepo, new Validator());

        $user = $service->register(CreateUserDTO::fromArray([
            'name'     => 'Test User',
            'email'    => 'new@test.com',
            'password' => 'secret123',
        ]));

        $this->assertSame(42, $user['id']);
        $this->assertArrayNotHasKey(S::PASSWORD, $user);
    }

    public function test_register_throws_on_invalid_data(): void
    {
        $fakeRepo = new class extends UserRepository {
            public function findByEmail(string $email): ?array { return null; }
            public function create(array $data): int { return 1; }
        };

        $service = new UserService($fakeRepo, new Validator());

        $this->expectException(\InvalidArgumentException::class);

        $service->register(CreateUserDTO::fromArray([
            'name'     => '',   // required field empty
            'email'    => 'not-an-email',
            'password' => '',
        ]));
    }

    // ─── authenticate ─────────────────────────────────────────────────────────

    public function test_authenticate_throws_on_wrong_password(): void
    {
        $hash = password_hash('correct_password', PASSWORD_ARGON2ID);

        $fakeRepo = new class($hash) extends UserRepository {
            public function __construct(private string $hash) {}
            public function findByEmail(string $email): ?array
            {
                return ['id' => 1, 'email' => $email, S::PASSWORD => $this->hash, 'role' => 'user'];
            }
        };

        $service = new UserService($fakeRepo, new Validator());

        $this->expectException(\RuntimeException::class);
        $service->authenticate('user@test.com', 'wrong_password');
    }

    public function test_authenticate_returns_user_on_correct_password(): void
    {
        $hash = password_hash('correct_password', PASSWORD_ARGON2ID);

        $fakeRepo = new class($hash) extends UserRepository {
            public function __construct(private string $hash) {}
            public function findByEmail(string $email): ?array
            {
                return ['id' => 5, 'email' => $email, S::PASSWORD => $this->hash, 'role' => 'admin'];
            }
        };

        $service = new UserService($fakeRepo, new Validator());
        $user    = $service->authenticate('user@test.com', 'correct_password');

        $this->assertSame(5, $user['id']);
        $this->assertArrayNotHasKey(S::PASSWORD, $user);
    }

    // ─── getById ──────────────────────────────────────────────────────────────

    public function test_get_by_id_throws_when_not_found(): void
    {
        $fakeRepo = new class extends UserRepository {
            public function findById(int $id): ?array { return null; }
        };

        $service = new UserService($fakeRepo, new Validator());

        $this->expectException(\RuntimeException::class);
        $service->getById(999);
    }
}
