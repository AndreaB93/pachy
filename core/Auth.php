<?php
declare(strict_types=1);

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
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
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
    // CSRF
    // -------------------------

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
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
            'exp'  => time() + (int)($_ENV['JWT_TTL'] ?? 3600),
        ];
        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    public static function requireBearer(): object
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            http_response_code(401);
            Response::json(['error' => 'Missing token']);
        }
        try {
            return JWT::decode(substr($header, 7), new Key($_ENV['JWT_SECRET'], 'HS256'));
        } catch (\Throwable) {
            http_response_code(401);
            Response::json(['error' => 'Invalid or expired token']);
        }
    }

    // -------------------------
    // AUTO-DETECT
    // -------------------------

    /**
     * Returns user context (array for session, object for JWT).
     * Detects mode from Accept header and request URI.
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
