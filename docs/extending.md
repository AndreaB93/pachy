# Extending pachy

## Adding a New Entity

1. **Migration** — `migrations/004_create_products.sql`
2. **Schema** — `app/Schema/ProductSchema.php`
3. **DTO(s)** — `app/DTOs/CreateProductDTO.php`
4. **Repository** — `app/Repositories/ProductRepository.php`
5. **Service** — `app/Services/ProductService.php`
6. **Controller** — `app/Controllers/ProductController.php`
7. **Routes** — add to `app/routes.php`
8. **Views** — add to `app/Views/pages/`
9. **Bindings** — register in `config/bindings.php`

## Adding a Cache Layer

Create `core/Cache.php` (file or APCu based):

```php
namespace Core;

class Cache
{
    public static function get(string $key, mixed $default = null): mixed { ... }
    public static function set(string $key, mixed $value, int $ttl = 3600): void { ... }
    public static function forget(string $key): void { ... }
}
```

Inject into Services that need caching:

```php
$users = Cache::get('all_users') ?? tap($this->users->findAll(), fn($u) => Cache::set('all_users', $u));
```

## Adding an Event System

Create `core/EventEmitter.php`:

```php
namespace Core;

class EventEmitter
{
    private static array $listeners = [];

    public static function on(string $event, callable $listener): void { ... }
    public static function emit(string $event, mixed $payload = null): void { ... }
}
```

Services emit events; Listeners subscribe (e.g., send welcome email on `user.registered`).

## Adding a Queue System

Extend `core/Job.php` with an abstract `QueueJob`. Replace `Scheduler::execute()` with a queue push. The worker reads from the queue table and runs jobs.

## API Versioning

```php
// app/routes.php
$router->get('/api/v2/users', [AuthBearer::class, [Api\V2\UserController::class, 'index']]);
```

New namespace: `App\Controllers\Api\V2\`.

## Multi-tenancy

Add `tenant_id` to schema constants. Create `TenantMiddleware` that reads tenant from subdomain or JWT claim and injects it into a request context. All repository queries include `WHERE tenant_id = ?`.
