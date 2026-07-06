# PRD — pachy: Minimal PHP 8.5 Architecture Framework

**Version:** 1.0  
**Status:** Draft  
**Target:** AI Agents  
**License:** MIT

---

## 1. Vision & Goals

pachy is not a framework in the traditional sense. It is a **set of architectural rules, conventions, and lightweight core components** that bring structure to PHP 8.5 vanilla projects without introducing framework lock-in, heavy dependencies, or hidden magic.

### Core Philosophy

- **Explicit over implicit.** Every behavior is readable in the source.
- **SQL is not the enemy.** PDO with Repository pattern beats any ORM for mid-size projects.
- **One entry point, clean layers.** Front Controller → Router → Middleware → Controller → Service → Repository → DB.
- **AI-friendly codebase.** Clear naming, typed DTOs, and isolated layers allow AI agents to navigate and extend the code without framework-specific knowledge.
- **Shared hosting first.** No queue workers, no Redis, no Docker required. Runs on Hostinger/SiteGround out of the box.
- **Composable auth.** Session-based for HTMX pages, Bearer JWT for REST APIs, coexisting on the same codebase.

### Non-Goals

- This is **not** a full framework (no ORM, no Blade, no Artisan).
- This is **not** designed for microservices or distributed systems.
- This is **not** a replacement for Laravel in large teams with complex onboarding needs.

---

## 1.1 PHP 8.5 Feature Adoption

pachy explicitly adopts the following PHP 8.5 features where they improve safety or reduce boilerplate without harming readability. Features that conflict with the "explicit over implicit" principle are intentionally left as optional tools for application code, not baked into the core.

| Feature | Adopted in core? | Where / Why |
|---|---|---|
| `clone with` | Yes | DTOs — derive a modified copy of a `readonly` DTO without rebuilding it (e.g. partial updates) |
| `Uri\Rfc3986\Uri` | Yes | `core/Router.php` — native, RFC-compliant path/query parsing instead of manual `strtok()`/`parse_url()` |
| `array_first()` / `array_last()` | Yes | `core/DB.php` — cleaner extraction of first row in `row()`/`value()` |
| `#[\NoDiscard]` | Yes | `DB::transaction()`, `DB::execute()`, Repository `update()`/`delete()` — prevents silently ignoring failed operations |
| Pipe operator `|>` | No (core), optional (app) | Left to application code for transformation chains; not imposed as a core convention to keep onboarding friction low |

### `clone with` in DTOs

```php
readonly class UpdateUserDTO
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $role,
    ) {}
}

// Derive a variant without rebuilding the whole object
$promoted = clone $dto with { role: 'admin' };
```

This is the most relevant 8.5 addition for the Service layer: previously, modifying one field of a `readonly` DTO required a full reconstruction; now it's a one-line derivation, which keeps DTOs fully immutable while still cheap to adapt.

---

## 2. Target Context

| Attribute | Value |
|---|---|
| PHP version | 8.5 (minimum 8.2 for `readonly` classes) |
| Deployment | Shared hosting (Apache/Nginx + PHP-FPM) |
| Composer | Yes — for autoloading and optional micro-libraries |
| Frontend | PHP templates + HTMX (optional vanilla JS) |
| API | REST JSON endpoints on same codebase |
| Auth | Session (web) + Bearer JWT (API) |
| DB | MySQL / MariaDB / SQLite via PDO |
| Cron | Shared hosting crontab + internal scheduler |

---

## 3. Composer & Autoloading

### 3.1 `composer.json`

```json
{
  "name": "vendor/pachy-app",
  "description": "pachy application",
  "type": "project",
  "require": {
    "php": ">=8.2",
    "nikic/fast-route": "^1.3",
    "vlucas/phpdotenv": "^5.6",
    "firebase/php-jwt": "^6.10"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0"
  },
  "autoload": {
    "psr-4": {
      "App\\": "app/",
      "Core\\": "core/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  },
  "scripts": {
    "post-autoload-dump": [
      "php -r \"copy('.env.example', '.env');\" 2>/dev/null || true"
    ]
  }
}
```

### 3.2 Allowed External Libraries

The following micro-libraries are the **only** approved Composer dependencies for the core. Everything else is built vanilla.

| Library | Purpose | Why allowed |
|---|---|---|
| `nikic/fast-route` | HTTP routing | ~200 lines, zero deps, battle-tested |
| `vlucas/phpdotenv` | Environment variables | Standard, stable, minimal |
| `firebase/php-jwt` | JWT encode/decode | Maintained by Google/Firebase |
| `phpunit/phpunit` | Testing (dev only) | Industry standard |

No ORM. No full-stack framework. No frontend build tools.

---

## 4. Directory Structure

```
/
├── public/                    # Web root (point Apache/Nginx here)
│   └── index.php              # Front Controller — single entry point
│
├── app/                       # Application code (PSR-4: App\)
│   ├── Controllers/           # HTTP layer — thin, no business logic
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   └── Api/               # API-specific controllers (optional separation)
│   ├── Services/              # Business logic layer
│   ├── Repositories/          # All SQL lives here, one per entity
│   ├── DTOs/                  # Data Transfer Objects (readonly classes)
│   ├── Schema/                # DB field name constants per entity
│   ├── Middleware/            # Auth, CSRF, RateLimit, ValidateJson
│   └── Views/                 # PHP template files
│       ├── layouts/
│       ├── pages/
│       └── partials/          # HTMX partial responses
│
├── core/                      # Framework core (PSR-4: Core\)
│   ├── DB.php                 # PDO wrapper
│   ├── Router.php             # Wraps fast-route
│   ├── Auth.php               # Session + JWT dual auth
│   ├── Request.php            # HTTP input abstraction
│   ├── Response.php           # HTML / JSON / HTMX response factory
│   ├── Scheduler.php          # Cron job orchestrator
│   ├── Job.php                # Abstract base for CLI jobs
│   ├── View.php               # Template renderer
│   ├── Validator.php          # Input validation
│   └── Container.php          # Minimal DI container
│
├── cli/                       # CLI entry points
│   ├── scheduler.php          # Single cron target (runs every minute)
│   └── jobs/                  # Concrete job implementations
│       ├── SendDailyReport.php
│       ├── CleanExpiredSessions.php
│       └── ProcessImportQueue.php
│
├── migrations/                # Versioned SQL files (plain .sql)
│   ├── 001_create_users.sql
│   └── 002_create_orders.sql
│
├── config/                    # Static config arrays
│   ├── app.php
│   ├── database.php
│   └── auth.php
│
├── storage/                   # Runtime storage (gitignored)
│   ├── logs/
│   ├── cache/
│   └── locks/                 # Cron lock files
│
├── tests/
│   ├── Unit/
│   └── Integration/
│
├── .env
├── .env.example
├── bootstrap.php              # App bootstrapper (loaded by all entry points)
└── composer.json
```

---

## 5. Core Components

### 5.1 `core/DB.php` — PDO Wrapper

**Responsibilities:**
- Single PDO connection, lazy-initialized.
- Prepared statements always — zero string concatenation with user input.
- Transaction helper with automatic rollback on exception.
- Batch insert helper for performance.

**Interface:**

```php
namespace Core;

class DB
{
    public static function connect(array $config): void;

    /** Returns all rows as associative arrays */
    public static function query(string $sql, array $params = []): array;

    /** Returns first row or null — uses array_first() (PHP 8.5) internally */
    public static function row(string $sql, array $params = []): ?array;

    /** Returns single scalar value or null */
    public static function value(string $sql, array $params = []): mixed;

    /** INSERT / UPDATE / DELETE — returns affected row count */
    #[\NoDiscard]
    public static function execute(string $sql, array $params = []): int;

    /** Returns last inserted ID */
    public static function lastId(): string;

    /** Runs callable in a transaction; auto-rollback on Throwable */
    #[\NoDiscard]
    public static function transaction(callable $fn): mixed;

    /**
     * Bulk insert with chunking.
     * $rows: array of arrays with identical keys.
     * $chunkSize: rows per INSERT statement (default 500).
     */
    public static function insertBatch(string $table, array $rows, int $chunkSize = 500): void;

    /** Expose raw PDO for edge cases (use sparingly) */
    public static function pdo(): \PDO;
}
```

Internally, `row()` and `value()` use PHP 8.5's `array_first()` instead of `$rows[0] ?? null`, both for clarity and to make intent explicit:

```php
public static function row(string $sql, array $params = []): ?array
{
    return array_first(self::query($sql, $params));
}
```

`#[\NoDiscard]` on `execute()` and `transaction()` means calling them without using the return value (or without it flowing into a conditional/throw) triggers a PHP 8.5 warning — this catches silently-ignored failed writes during development.

**Rules:**
- `ATTR_EMULATE_PREPARES = false` always.
- `ATTR_ERRMODE = ERRMODE_EXCEPTION` always.
- `ATTR_DEFAULT_FETCH_MODE = FETCH_ASSOC` always.
- charset: `utf8mb4`.
- Never call `DB::pdo()` outside of `core/` directory.

---

### 5.2 Schema Classes (`app/Schema/`)

**Purpose:** Centralize all DB table and column name constants. A renamed column requires changing **one line**.

**Convention:** One class per DB table, named `{Entity}Schema`.

```php
namespace App\Schema;

final class UserSchema
{
    const TABLE    = 'users';
    const ID       = 'id';
    const NAME     = 'full_name';
    const EMAIL    = 'email';
    const PASSWORD = 'password_hash';
    const ROLE     = 'role';
    const ACTIVE   = 'is_active';
    const CREATED  = 'created_at';

    /** Returns all selectable columns as comma-separated string */
    public static function columns(array $exclude = []): string
    {
        $all = [self::ID, self::NAME, self::EMAIL, self::ROLE, self::ACTIVE, self::CREATED];
        return implode(', ', array_diff($all, $exclude));
    }

    /** Validation rules, usable by Validator */
    public static function rules(): array
    {
        return [
            self::NAME  => 'required|string|max:255',
            self::EMAIL => 'required|email|max:255',
            self::ROLE  => 'required|in:admin,user,viewer',
        ];
    }
}
```

**Rules:**
- No SQL strings inside Schema classes.
- Schema classes are `final` and contain only constants and static methods.
- Every table must have a corresponding Schema class.

---

### 5.3 DTOs (`app/DTOs/`)

**Purpose:** Typed, immutable data carriers between layers. No arrays passed between Controller → Service → Repository.

**Convention:** Named `{Action}{Entity}DTO`. All DTOs are `readonly`.

```php
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

readonly class UpdateUserDTO
{
    public function __construct(
        public int     $id,
        public string  $name,
        public ?string $role = null,
    ) {}

    public static function fromArray(int $id, array $data): self
    {
        return new self(
            id:   $id,
            name: trim($data['name'] ?? ''),
            role: $data['role'] ?? null,
        );
    }
}
```

**Rules:**
- DTOs are `readonly` classes (PHP 8.2+).
- DTOs have no dependencies and no side effects.
- DTOs expose a static `fromArray()` factory for HTTP input.
- DTOs never touch the database directly.
- DTOs carry only data — no methods that do work.

---

### 5.4 Repositories (`app/Repositories/`)

**Purpose:** All SQL for a given entity lives in one place. Controllers and Services never write SQL.

**Convention:** Named `{Entity}Repository`. Implements a common interface.

```php
namespace Core;

interface RepositoryInterface
{
    public function findById(int $id): ?array;
    public function findAll(array $filters = []): array;
    public function create(array $data): int;   // returns new ID

    #[\NoDiscard]
    public function update(int $id, array $data): bool;

    #[\NoDiscard]
    public function delete(int $id): bool;
}
```

```php
namespace App\Repositories;

use Core\DB;
use App\Schema\UserSchema as S;

class UserRepository implements \Core\RepositoryInterface
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
            $params[] = (int) $filters['active'];
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
        return (int) DB::lastId();
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
        return DB::execute("DELETE FROM " . S::TABLE . " WHERE " . S::ID . " = ?", [$id]) > 0;
    }
}
```

**Rules:**
- Repositories receive and return plain arrays or primitives — no domain objects.
- Never call a Repository from another Repository; use a Service.
- No business logic inside Repositories (no `if email already exists → throw`).
- Password columns are excluded from SELECT by default in `columns()`.

---

### 5.5 Services (`app/Services/`)

**Purpose:** Business logic layer. Orchestrates Repositories, validates DTOs, handles transactions.

```php
namespace App\Services;

use App\DTOs\CreateUserDTO;
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
        $this->validator->validate((array) $dto, S::rules());

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
        return $user;
    }
}
```

**Rules:**
- Services receive DTOs, return arrays or scalars.
- Services own transaction boundaries (`DB::transaction()`).
- Services throw domain exceptions — controllers catch and convert to HTTP responses.
- Services never access `$_POST`, `$_GET`, or any HTTP globals.

---

### 5.6 `core/Auth.php` — Dual Authentication

**Purpose:** Session auth for HTMX/web pages, Bearer JWT for REST API. Auto-detect or explicit.

```php
namespace Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth
{
    // -------------------------
    // SESSION — for web/HTMX
    // -------------------------

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
        }
    }

    public static function loginSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_at'] = time();
    }

    public static function requireSession(?string $role = null): void
    {
        if (empty($_SESSION['user_id'])) {
            self::redirectUnauthorized();
        }
        if ($role !== null && ($_SESSION['user_role'] ?? '') !== $role) {
            self::denyForbidden();
        }
    }

    public static function sessionUser(): ?array
    {
        return isset($_SESSION['user_id'])
            ? ['id' => $_SESSION['user_id'], 'role' => $_SESSION['user_role']]
            : null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    // -------------------------
    // JWT — for REST API
    // -------------------------

    public static function generateToken(array $user): string
    {
        $payload = [
            'sub'  => $user['id'],
            'role' => $user['role'],
            'iat'  => time(),
            'exp'  => time() + (int) ($_ENV['JWT_TTL'] ?? 3600),
        ];
        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    public static function requireBearer(): object
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            http_response_code(401);
            Response::json(['error' => 'Missing token']);
            exit;
        }
        try {
            return JWT::decode(substr($header, 7), new Key($_ENV['JWT_SECRET'], 'HS256'));
        } catch (\Throwable) {
            http_response_code(401);
            Response::json(['error' => 'Invalid or expired token']);
            exit;
        }
    }

    // -------------------------
    // AUTO-DETECT
    // -------------------------

    /**
     * Returns user context (array for session, object for JWT).
     * Detects mode from Accept header and HX-Request.
     */
    public static function require(?string $role = null): array|object
    {
        if (self::isApiRequest()) {
            $token = self::requireBearer();
            if ($role !== null && ($token->role ?? '') !== $role) {
                self::denyForbidden();
            }
            return $token;
        }

        self::requireSession($role);
        return self::sessionUser();
    }

    public static function isApiRequest(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }

    // -------------------------
    // Helpers
    // -------------------------

    private static function redirectUnauthorized(): never
    {
        if (!empty($_SERVER['HTTP_HX_REQUEST'])) {
            header('HX-Redirect: /login');
        } else {
            header('Location: /login');
        }
        exit;
    }

    private static function denyForbidden(): never
    {
        http_response_code(403);
        Response::json(['error' => 'Forbidden']);
        exit;
    }
}
```

**Rules:**
- Session cookie: `HttpOnly`, `SameSite=Lax`.
- Password hashing: `PASSWORD_ARGON2ID` always (PHP 8.x default preferred).
- JWT secret via `.env` — never hardcoded.
- `session_regenerate_id(true)` on every login — prevents session fixation.
- CSRF token required for state-changing web requests (POST/PUT/DELETE via HTMX).

---

### 5.7 `core/Response.php` — Strategy-based Response

**Purpose:** Single interface for HTML page, HTMX partial, and JSON API responses.

```php
namespace Core;

class Response
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function view(string $template, array $data = [], int $status = 200): never
    {
        http_response_code($status);
        View::render($template, $data);
        exit;
    }

    public static function htmx(string $partial, array $data = []): never
    {
        View::render('partials/' . $partial, $data);
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }

    public static function htmxRedirect(string $url): never
    {
        header("HX-Redirect: $url");
        exit;
    }

    /**
     * Auto-detect response type from request headers.
     * Renders HTMX partial, JSON, or full page automatically.
     */
    public static function auto(string $template, array $data = [], int $status = 200): never
    {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            self::json($data, $status);
        }
        if (!empty($_SERVER['HTTP_HX_REQUEST'])) {
            self::htmx($template, $data);
        }
        self::view($template, $data, $status);
    }
}
```

---

### 5.8 `core/Request.php` — HTTP Input Abstraction

**Purpose:** Safe, typed access to HTTP input. Never read `$_POST`, `$_GET`, `$_FILES` directly in controllers.

```php
namespace Core;

class Request
{
    private array $body;
    private array $query;
    private array $params = []; // route params injected by Router

    public function __construct()
    {
        $this->query = $_GET;
        $this->body  = match(true) {
            str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
                => json_decode(file_get_contents('php://input'), true) ?? [],
            default => $_POST,
        };
    }

    public function body(string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->body : ($this->body[$key] ?? $default);
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function isHtmx(): bool
    {
        return !empty($_SERVER['HTTP_HX_REQUEST']);
    }

    public function expectsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'];
    }
}
```

---

### 5.9 `core/Validator.php` — Input Validation

**Purpose:** Validate arrays (from DTOs or raw input) against rule strings. Lightweight, no dependencies.

**Supported rules:** `required`, `string`, `int`, `float`, `email`, `min:n`, `max:n`, `in:a,b,c`, `nullable`, `regex:pattern`.

```php
namespace Core;

class Validator
{
    /** @throws \InvalidArgumentException with field errors */
    public function validate(array $data, array $rules): void
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value      = $data[$field] ?? null;
            $fieldRules = explode('|', $ruleString);
            foreach ($fieldRules as $rule) {
                $error = $this->applyRule($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        if ($errors) {
            throw new \InvalidArgumentException(json_encode($errors));
        }
    }

    private function applyRule(string $field, mixed $value, string $rule): ?string
    {
        [$ruleName, $param] = [...explode(':', $rule, 2), null];

        return match($ruleName) {
            'required' => (empty($value) && $value !== '0') ? "$field is required." : null,
            'email'    => $value && !filter_var($value, FILTER_VALIDATE_EMAIL) ? "$field must be a valid email." : null,
            'min'      => $value !== null && strlen((string)$value) < (int)$param ? "$field must be at least $param chars." : null,
            'max'      => $value !== null && strlen((string)$value) > (int)$param ? "$field must be at most $param chars." : null,
            'in'       => $value !== null && !in_array($value, explode(',', $param)) ? "$field must be one of: $param." : null,
            'int'      => $value !== null && !is_numeric($value) ? "$field must be an integer." : null,
            default    => null,
        };
    }
}
```

---

### 5.10 `core/Container.php` — Minimal DI Container

**Purpose:** Resolve Service and Repository dependencies without a heavy IoC container.

```php
namespace Core;

class Container
{
    private array $bindings  = [];
    private array $instances = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bind($abstract, function() use ($abstract, $factory) {
            $this->instances[$abstract] ??= $factory($this);
            return $this->instances[$abstract];
        });
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }
        throw new \RuntimeException("No binding for: $abstract");
    }
}
```

Wired in `bootstrap.php`:

```php
$container = new Container();

$container->singleton(Validator::class, fn() => new Validator());
$container->singleton(UserRepository::class, fn() => new UserRepository());
$container->singleton(UserService::class, fn(Container $c) => new UserService(
    $c->make(UserRepository::class),
    $c->make(Validator::class),
));
```

---

## 6. Routing

### 6.1 `core/Router.php`

Wraps `nikic/fast-route`. Supports middleware chains per route.

```php
namespace Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [$method, $path, $handler];
    }

    public function get(string $path, array $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, array $handler): void   { $this->add('POST', $path, $handler); }
    public function put(string $path, array $handler): void    { $this->add('PUT', $path, $handler); }
    public function delete(string $path, array $handler): void { $this->add('DELETE', $path, $handler); }

    public function dispatch(string $method, string $rawUri): void
    {
        // PHP 8.5: native RFC 3986 URI parsing instead of manual strtok()/parse_url()
        $uri  = new \Uri\Rfc3986\Uri($rawUri);
        $path = $uri->getPath();

        $routes = $this->routes;
        $dispatcher = simpleDispatcher(function(RouteCollector $r) use ($routes) {
            foreach ($routes as [$m, $p, $h]) {
                $r->addRoute($m, $p, $h);
            }
        });

        $result = $dispatcher->dispatch($method, $path);

        match($result[0]) {
            Dispatcher::FOUND => $this->handle($result[1], $result[2]),
            Dispatcher::NOT_FOUND => (function() {
                http_response_code(404);
                Response::view('errors/404');
            })(),
            Dispatcher::METHOD_NOT_ALLOWED => (function() {
                http_response_code(405);
                Response::json(['error' => 'Method Not Allowed']);
            })(),
        };
    }

    private function handle(array $handler, array $routeParams): void
    {
        $request = new Request();
        $request->setParams($routeParams);

        // Handler format: [Middleware1::class, Middleware2::class, [Controller::class, 'method']]
        $controllerDef = array_pop($handler);
        $middlewares   = $handler;

        foreach ($middlewares as $middleware) {
            (new $middleware)->handle($request);
        }

        [$controllerClass, $method] = $controllerDef;
        (new $controllerClass)->$method($request);
    }
}
```

### 6.2 Routes File (`app/routes.php`)

```php
// Web routes (session auth)
$router->get('/',              [Middleware\AuthSession::class, [HomeController::class, 'index']]);
$router->get('/orders',        [Middleware\AuthSession::class, [OrderController::class, 'index']]);
$router->post('/orders',       [Middleware\AuthSession::class, Middleware\CsrfCheck::class, [OrderController::class, 'store']]);
$router->get('/orders/{id}',   [Middleware\AuthSession::class, [OrderController::class, 'show']]);

// Auth routes (no auth required)
$router->get('/login',  [[AuthController::class, 'showLogin']]);
$router->post('/login', [[AuthController::class, 'login']]);
$router->get('/logout', [[AuthController::class, 'logout']]);

// REST API routes (JWT auth)
$router->get('/api/orders',      [Middleware\AuthBearer::class, [Api\OrderController::class, 'index']]);
$router->post('/api/orders',     [Middleware\AuthBearer::class, Middleware\ValidateJson::class, [Api\OrderController::class, 'store']]);
$router->get('/api/orders/{id}', [Middleware\AuthBearer::class, [Api\OrderController::class, 'show']]);
```

---

## 7. Middleware

Each middleware is a class with a `handle(Request $request): void` method. Exits on failure.

**Required core middleware:**

| Class | Purpose |
|---|---|
| `Middleware\AuthSession` | Require valid session; HTMX-aware redirect |
| `Middleware\AuthBearer` | Require valid JWT Bearer token |
| `Middleware\CsrfCheck` | Verify CSRF token on state-changing requests |
| `Middleware\ValidateJson` | Ensure Content-Type is application/json |
| `Middleware\RateLimit` | Simple file/DB-based rate limiting |

---

## 8. Scheduler & Batch Jobs

### 8.1 `core/Scheduler.php`

One crontab entry runs every minute. The Scheduler decides which jobs are due.

```php
namespace Core;

class Scheduler
{
    private array $jobs = [];

    public function everyMinute(string $jobClass): self  { return $this->register($jobClass, '* * * * *'); }
    public function everyMinutes(int $n, string $c): self { return $this->register($c, "*/$n * * * *"); }
    public function hourly(string $jobClass): self        { return $this->register($jobClass, '0 * * * *'); }
    public function daily(string $time, string $jobClass): self
    {
        [$h, $m] = explode(':', $time);
        return $this->register($jobClass, "$m $h * * *");
    }
    public function weekly(string $day, string $jobClass): self
    {
        $days = ['SUN'=>0,'MON'=>1,'TUE'=>2,'WED'=>3,'THU'=>4,'FRI'=>5,'SAT'=>6];
        return $this->register($jobClass, "0 0 * * " . $days[$day]);
    }

    private function register(string $jobClass, string $cron): self
    {
        $this->jobs[] = ['class' => $jobClass, 'cron' => $cron];
        return $this;
    }

    public function run(): void
    {
        $now = new \DateTime();
        foreach ($this->jobs as $job) {
            if ($this->isDue($job['cron'], $now)) {
                $this->execute($job['class']);
            }
        }
    }

    private function isDue(string $cron, \DateTime $now): bool
    {
        [$min, $hour, $dom, $month, $dow] = explode(' ', $cron);
        return $this->matches($min,   (int)$now->format('i'))
            && $this->matches($hour,  (int)$now->format('G'))
            && $this->matches($dom,   (int)$now->format('j'))
            && $this->matches($month, (int)$now->format('n'))
            && $this->matches($dow,   (int)$now->format('w'));
    }

    private function matches(string $expr, int $value): bool
    {
        if ($expr === '*') return true;
        if (is_numeric($expr)) return (int)$expr === $value;
        if (str_starts_with($expr, '*/')) return $value % (int)substr($expr, 2) === 0;
        return false;
    }

    private function execute(string $jobClass): void
    {
        $lockFile = __DIR__ . '/../storage/locks/' . md5($jobClass) . '.lock';
        if (file_exists($lockFile) && (time() - filemtime($lockFile) < 3600)) {
            return; // still running from previous invocation
        }
        file_put_contents($lockFile, getmypid());
        try {
            (new $jobClass)->run();
        } finally {
            @unlink($lockFile);
        }
    }
}
```

### 8.2 `core/Job.php` — Abstract Base Job

```php
namespace Core;

abstract class Job
{
    abstract public function run(): void;

    /** Chunked processing with resumable state */
    protected function processChunked(
        string $stateKey,
        int    $chunkSize,
        callable $fetchChunk,
        callable $processRow,
    ): void {
        $offset = (int)(DB::value(
            "SELECT value FROM job_state WHERE job_key = ?", [$stateKey]
        ) ?? 0);

        do {
            $rows = $fetchChunk($offset, $chunkSize);
            foreach ($rows as $row) {
                $processRow($row);
            }
            $offset += count($rows);

            DB::execute(
                "INSERT INTO job_state (job_key, value, updated_at) VALUES (?,?,NOW())
                 ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()",
                [$stateKey, $offset]
            );

            gc_collect_cycles();
        } while (count($rows) === $chunkSize);

        // Clear state on completion
        DB::execute("DELETE FROM job_state WHERE job_key = ?", [$stateKey]);
    }

    protected function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . get_class($this) . ': ' . $message . PHP_EOL;
        file_put_contents(__DIR__ . '/../storage/logs/jobs.log', $line, FILE_APPEND);
    }
}
```

### 8.3 Concrete Job Example

```php
namespace App\Jobs;

use Core\{Job, DB};
use App\Schema\UserSchema as S;
use App\Services\ReportService;

class SendDailyReport extends Job
{
    public function run(): void
    {
        $this->log('Starting daily report dispatch.');

        $users = DB::query(
            "SELECT " . S::ID . ", " . S::EMAIL . ", " . S::NAME . " FROM " . S::TABLE .
            " WHERE " . S::ACTIVE . " = ? AND daily_report = 1",
            [1]
        );

        $service = new ReportService();
        foreach ($users as $user) {
            $service->sendTo($user);
            $this->log("Report sent to {$user[S::EMAIL]}");
        }
    }
}
```

### 8.4 `cli/scheduler.php` — The single crontab target

```php
<?php
if (php_sapi_name() !== 'cli') exit(1);

require_once __DIR__ . '/../bootstrap.php';

use Core\Scheduler;
use App\Jobs\{SendDailyReport, CleanExpiredSessions, ProcessImportQueue};

$scheduler = new Scheduler();
$scheduler
    ->daily('07:00', SendDailyReport::class)
    ->everyMinutes(5, ProcessImportQueue::class)
    ->daily('02:00', CleanExpiredSessions::class);

$scheduler->run();
```

**Crontab on shared hosting:**
```bash
* * * * * php /home/user/public_html/cli/scheduler.php >> /home/user/logs/scheduler.log 2>&1
```

### 8.5 Migration `job_state` table

```sql
-- migrations/003_create_job_state.sql
CREATE TABLE IF NOT EXISTS job_state (
    job_key    VARCHAR(255) PRIMARY KEY,
    value      BIGINT       NOT NULL DEFAULT 0,
    updated_at DATETIME     NOT NULL
);
```

---

## 9. Migrations

No migration framework. Plain versioned SQL files, applied in order.

**Convention:** `{NNN}_{description}.sql` — three-digit zero-padded sequence number.

A minimal CLI runner:

```php
// cli/migrate.php
if (php_sapi_name() !== 'cli') exit(1);
require_once __DIR__ . '/../bootstrap.php';

DB::execute("CREATE TABLE IF NOT EXISTS migrations (
    filename VARCHAR(255) PRIMARY KEY,
    applied_at DATETIME NOT NULL
)");

$applied = array_column(DB::query("SELECT filename FROM migrations"), 'filename');
$files   = glob(__DIR__ . '/../migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied)) continue;

    DB::execute(file_get_contents($file));
    DB::execute("INSERT INTO migrations (filename, applied_at) VALUES (?, NOW())", [$name]);
    echo "Applied: $name\n";
}
echo "Done.\n";
```

---

## 10. `public/index.php` — Front Controller

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\{AuthController, HomeController, OrderController};
use App\Controllers\Api;
use App\Middleware;
use Core\Router;

$router = new Router();
require_once __DIR__ . '/../app/routes.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI']
);
```

---

## 11. `bootstrap.php`

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Core\{DB, Auth, Container};

// 1. Environment
(Dotenv::createImmutable(__DIR__))->load();

// 2. Error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] === 'true' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/storage/logs/php-errors.log');

// 3. Database
DB::connect([
    'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'   => $_ENV['DB_HOST'],
    'name'   => $_ENV['DB_NAME'],
    'user'   => $_ENV['DB_USER'],
    'pass'   => $_ENV['DB_PASS'],
]);

// 4. Session (web context only)
if (php_sapi_name() !== 'cli') {
    Auth::startSession();
}

// 5. Dependency Container
$container = new Container();
require_once __DIR__ . '/config/bindings.php'; // registers singletons
```

---

## 12. Configuration

### `.env.example`

```ini
APP_NAME=pachy App
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=app_db
DB_USER=app_user
DB_PASS=secret

JWT_SECRET=change-me-to-a-long-random-string
JWT_TTL=3600

MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USER=
MAIL_PASS=
MAIL_FROM=noreply@yourdomain.com
```

### `config/app.php`

```php
return [
    'name'     => $_ENV['APP_NAME'] ?? 'App',
    'debug'    => $_ENV['APP_DEBUG'] === 'true',
    'timezone' => 'Europe/Rome',
    'locale'   => 'it_IT',
];
```

---

## 13. Security Checklist

The following must be enforced by convention, not by the framework.

| Rule | Mechanism |
|---|---|
| SQL injection prevention | PDO prepared statements always; zero concatenation of user input |
| XSS prevention | `htmlspecialchars()` in all view output via `View::e()` helper |
| CSRF protection | Token per session, verified by `CsrfCheck` middleware on POST/PUT/DELETE |
| Session fixation | `session_regenerate_id(true)` on every login |
| Password hashing | `password_hash($pass, PASSWORD_ARGON2ID)` always |
| Sensitive data in logs | Never log passwords, tokens, or full credit card data |
| `.env` exposure | `.env` must be above web root or denied by `.htaccess` |
| Directory listing | Disabled via `.htaccess` or Nginx config |
| Error display | `display_errors=Off` in production; log to file |
| Rate limiting | `RateLimit` middleware on auth endpoints |

---

## 14. View Layer

PHP templates only. No templating engine.

```php
// core/View.php
namespace Core;

class View
{
    private static string $viewPath;

    public static function setPath(string $path): void { self::$viewPath = rtrim($path, '/'); }

    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require self::$viewPath . '/' . $template . '.php';
    }

    /** Escape for HTML output — always use in templates */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
```

In templates:
```php
<!-- app/Views/pages/orders.php -->
<?php use Core\View; ?>
<?php $this->layout('layouts/main', ['title' => 'Orders']) ?>

<div id="order-list">
    <?php foreach ($orders as $order): ?>
        <div><?= View::e($order['reference']) ?></div>
    <?php endforeach ?>
</div>
```

---

## 15. Testing Strategy

PHPUnit only. No mocking framework required for unit tests on Services.

**Unit tests:** Test Services with mocked Repositories (manual fakes, no Mockery needed).

```php
// tests/Unit/UserServiceTest.php
class UserServiceTest extends TestCase
{
    public function test_register_throws_on_duplicate_email(): void
    {
        $fakeRepo = new class extends UserRepository {
            public function findByEmail(string $email): ?array { return ['id' => 1]; }
            public function create(array $data): int { return 0; }
        };

        $service = new UserService($fakeRepo, new Validator());
        $this->expectException(\DomainException::class);
        $service->register(CreateUserDTO::fromArray([
            'name' => 'Test', 'email' => 'exists@test.com', 'password' => 'secret'
        ]));
    }
}
```

**Integration tests:** Use a dedicated SQLite test database, reset between tests.

---

## 16. Extensibility Points

The architecture is designed to grow in specific directions without breaking existing code:

| Future need | Extension point |
|---|---|
| Queue system (async jobs) | Add `QueueJob` abstract extending `Job`; replace `Scheduler::execute()` with queue push |
| Cache layer | Add `core/Cache.php` (file or APCu); inject into Services where needed |
| Event system | Add `core/EventEmitter.php`; Services emit, Listeners subscribe |
| API versioning | Add `/api/v2/` route prefix; new controller namespace |
| Multi-tenancy | Add `tenant_id` to Schema constants; middleware injects tenant context |
| WebSockets | Separate long-running process (ReactPHP); pachy handles REST, WS runs alongside |

---

## 17. Documentation

The implementation must include a `/docs` directory with the following Markdown files:

| File | Content |
|---|---|
| `docs/README.md` | Quick start, installation, first route |
| `docs/architecture.md` | Layer diagram, data flow, invariants |
| `docs/db.md` | DB wrapper usage, Schema classes, Repository pattern |
| `docs/auth.md` | Session auth, JWT auth, dual-mode, CSRF |
| `docs/dto.md` | DTO conventions, fromArray pattern, validation |
| `docs/routing.md` | Route definition, middleware chain, parameters |
| `docs/scheduler.md` | Cron setup, job implementation, batch/chunking |
| `docs/security.md` | Security checklist, PDO safety, XSS, CSRF |
| `docs/extending.md` | How to add cache, events, queue, new entities |

---

## 18. Implementation Order for Claude Code

1. `composer.json` + directory scaffold
2. `bootstrap.php` + `.env.example` + `config/`
3. `core/DB.php`
4. `core/View.php` + `core/Response.php` + `core/Request.php`
5. `core/Validator.php`
6. `core/Auth.php`
7. `core/Router.php` + `public/index.php`
8. `core/Container.php` + `config/bindings.php`
9. `core/Scheduler.php` + `core/Job.php` + `cli/scheduler.php`
10. `cli/migrate.php` + `migrations/` examples
11. `app/Schema/UserSchema.php` (reference implementation)
12. `app/DTOs/` (CreateUserDTO, UpdateUserDTO as reference)
13. `app/Repositories/UserRepository.php` (reference implementation)
14. `app/Services/UserService.php` (reference implementation)
15. `app/Controllers/AuthController.php` (web, reference)
16. `app/Controllers/Api/UserController.php` (API, reference)
17. `app/Middleware/` (AuthSession, AuthBearer, CsrfCheck, ValidateJson, RateLimit)
18. `app/Views/` (layouts/main, pages/home, partials/example, errors/404)
19. `app/routes.php`
20. `tests/Unit/UserServiceTest.php`
21. `docs/` — all Markdown files

---

*pachy — less framework, more architecture.*
