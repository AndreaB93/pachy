# Security Checklist

## SQL Injection

✅ PDO prepared statements always — zero string concatenation with user input.

```php
// ✅ Safe
DB::row("SELECT * FROM users WHERE email = ?", [$email]);

// ❌ Never do this
DB::row("SELECT * FROM users WHERE email = '$email'");
```

## XSS Prevention

✅ Use `View::e()` for every value output in templates.

```php
<?= View::e($user['name']) ?>   // ✅ safe
<?= $user['name'] ?>            // ❌ never raw output
```

## CSRF Protection

✅ `CsrfCheck` middleware on all state-changing routes. Tokens are per-session.

```php
// Generate in controller
$csrf = Auth::csrfToken();

// Embed in form
<input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

// For HTMX — include as header:
// hx-headers='{"X-CSRF-TOKEN": "..."}'
```

## Session Fixation

✅ `session_regenerate_id(true)` called on every login via `Auth::loginSession()`.

## Password Hashing

✅ `password_hash($pass, PASSWORD_ARGON2ID)` always.
✅ `password_verify()` for checking — never compare hashes directly.

## Secrets

- `JWT_SECRET` must be in `.env`, never in source code.
- `.env` must be **above web root** or denied by `.htaccess`.
- Never commit `.env` to version control.

## Error Display

```ini
; .env production
APP_DEBUG=false
```

`display_errors` is off in production. Errors are logged to `storage/logs/php-errors.log`.

## Rate Limiting

Use `RateLimit` middleware on auth endpoints:

```php
$router->post('/login', [RateLimit::class, [AuthController::class, 'login']]);
```

Default: 60 requests per minute per IP.

## Directory Listing

`.htaccess` in `public/` includes `Options -Indexes`.

## Sensitive Data in Logs

- Never log passwords, JWT secrets, or full card numbers.
- `UserRepository` excludes `password_hash` from all default SELECTs via `S::columns([S::PASSWORD])`.
