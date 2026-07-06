<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\{Auth, Request, Response};

class CsrfCheck
{
    public function handle(Request $request): void
    {
        $method = $request->method();
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $token = $request->body('_csrf')
            ?? $request->body('csrf_token')
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (!Auth::verifyCsrf((string)$token)) {
            http_response_code(419);
            Response::json(['error' => 'CSRF token mismatch.']);
        }
    }
}
