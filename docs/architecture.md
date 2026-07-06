# Architecture

## Layer Diagram

```
HTTP Request
     │
     ▼
public/index.php  (Front Controller)
     │
     ▼
core/Router.php   (fast-route dispatcher)
     │
     ├── Middleware chain (Auth, CSRF, RateLimit, ValidateJson)
     │
     ▼
app/Controllers/  (thin HTTP layer — no business logic)
     │
     ▼
app/Services/     (business logic, transaction boundaries)
     │
     ▼
app/Repositories/ (all SQL, one per entity)
     │
     ▼
core/DB.php       (PDO wrapper)
     │
     ▼
MySQL / MariaDB / SQLite
```

## Invariants

| Rule | Where enforced |
|---|---|
| No SQL outside Repositories | Convention + code review |
| No HTTP globals in Services | Services receive DTOs only |
| No business logic in Controllers | Controllers call Service, return Response |
| No ORM | PDO + Repository pattern only |
| Typed boundaries | DTOs between Controller → Service |
| All output escaped | `View::e()` in every template |

## Data Flow (Web request)

1. `index.php` loads `bootstrap.php` → DB connects, session starts
2. `Router::dispatch()` matches the route
3. Middleware chain runs (auth check, CSRF, etc.)
4. Controller instantiates from `$container`, calls Service
5. Service validates DTO, calls Repository, returns array
6. Controller calls `Response::view()` or `Response::json()`

## Data Flow (API request)

Same, but:
- `AuthBearer` middleware validates JWT
- Controller returns `Response::json()`
- No session involved
