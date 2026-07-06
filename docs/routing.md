# Routing

## Defining Routes

Routes are defined in `app/routes.php`. The `$router` variable is injected from `public/index.php`.

```php
$router->get('/path',    [MiddlewareA::class, [Controller::class, 'method']]);
$router->post('/path',   [[Controller::class, 'method']]);
$router->put('/path',    [...]);
$router->delete('/path', [...]);
```

## Handler Format

```
[Middleware1::class, Middleware2::class, [Controller::class, 'method']]
```

- Any number of middleware (including zero) before the controller tuple.
- Middleware run in order; each can abort the request.
- The **last element** must be `[ControllerClass::class, 'methodName']`.

## Route Parameters

```php
$router->get('/users/{id:\d+}', [[UserController::class, 'show']]);

// In controller:
$id = $request->param('id'); // string, cast as needed
```

## Middleware Chain

Each middleware implements:

```php
class AuthSession
{
    public function handle(Request $request): void
    {
        Auth::requireSession(); // exits on failure
    }
}
```

## Available Middleware

| Class | Purpose |
|---|---|
| `AuthSession` | Require valid session |
| `AuthBearer` | Require valid JWT Bearer token |
| `CsrfCheck` | Verify CSRF token on mutating requests |
| `ValidateJson` | Require `Content-Type: application/json` |
| `RateLimit` | File-based IP rate limiting (60 req/min default) |

## 404 and 405

- Unknown routes → `errors/404` view (HTTP 404)
- Wrong method → JSON `{ "error": "Method Not Allowed" }` (HTTP 405)
