<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\{Request, Response};

class ValidateJson
{
    public function handle(Request $request): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!str_contains($contentType, 'application/json')) {
            http_response_code(415);
            Response::json(['error' => 'Content-Type must be application/json.']);
        }
    }
}
