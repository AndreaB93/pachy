<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\{Auth, Request};

class AuthBearer
{
    public function handle(Request $request): void
    {
        Auth::requireBearer();
    }
}
