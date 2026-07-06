# Authentication

pachy supports two auth modes that coexist on the same codebase.

## Session Auth (Web / HTMX)

Used for browser-based pages. Sessions are started automatically in `bootstrap.php`.

```php
// Login
Auth::loginSession($user); // regenerates session ID automatically

// Require auth in middleware
Auth::requireSession();          // any role
Auth::requireSession('admin');   // specific role

// Get current user
$user = Auth::sessionUser(); // ['id' => ..., 'role' => ...]

// Logout
Auth::logout();
```

**Session cookie settings:** `HttpOnly`, `SameSite=Lax`.

## JWT Auth (REST API)

Used for stateless API endpoints. Token is passed as `Authorization: Bearer <token>`.

```php
// Generate token after login
$token = Auth::generateToken($user);
// Returns JWT string, expires in JWT_TTL seconds (default 3600)

// Require valid bearer token
$payload = Auth::requireBearer();
// Returns decoded JWT payload as object: $payload->sub, $payload->role

// Check role
if ($payload->role !== 'admin') { ... }
```

**JWT secret** must be set in `.env` as `JWT_SECRET`. Never hardcode it.

## Auto-detect Mode

```php
// Detects API vs web from Accept header and /api/ URI prefix
$user = Auth::require();          // auto
$user = Auth::require('admin');   // auto + role check
```

## CSRF Protection

Required for all state-changing web requests (POST/PUT/DELETE via forms or HTMX).

```php
// In controller — generate token for form
$csrf = Auth::csrfToken();

// In template
<input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

// CsrfCheck middleware verifies automatically
// Or manually:
if (!Auth::verifyCsrf($request->body('_csrf'))) { abort(419); }
```

## Dual-mode Routes

```php
// Web route — session auth
$router->get('/dashboard', [Middleware\AuthSession::class, [DashboardController::class, 'index']]);

// API route — JWT auth
$router->get('/api/dashboard', [Middleware\AuthBearer::class, [Api\DashboardController::class, 'index']]);
```
