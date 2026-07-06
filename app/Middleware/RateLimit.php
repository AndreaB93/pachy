<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\{Request, Response};

/**
 * Simple file-based rate limiter.
 * Default: 60 requests per minute per IP.
 */
class RateLimit
{
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests   = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(Request $request): void
    {
        $ip      = $request->ip();
        $key     = md5('rate_limit_' . $ip);
        $file    = dirname(__DIR__, 2) . '/storage/locks/' . $key . '.rate';
        $now     = time();

        $data = ['count' => 0, 'window_start' => $now];

        if (file_exists($file)) {
            $stored = json_decode(file_get_contents($file), true);
            if ($stored && ($now - $stored['window_start']) < $this->windowSeconds) {
                $data = $stored;
            }
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);

        if ($data['count'] > $this->maxRequests) {
            http_response_code(429);
            header('Retry-After: ' . ($this->windowSeconds - ($now - $data['window_start'])));
            Response::json(['error' => 'Too Many Requests. Please slow down.']);
        }
    }
}
