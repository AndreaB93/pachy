<?php
declare(strict_types=1);

namespace Core;

class Response
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function view(string $template, array $data = [], int $status = 200): never
    {
        http_response_code($status);
        View::render($template, $data);
        exit;
    }

    public static function htmx(string $partial, array $data = []): never
    {
        View::render('partials/' . $partial, $data);
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }

    public static function htmxRedirect(string $url): never
    {
        header("HX-Redirect: $url");
        exit;
    }

    /**
     * Auto-detect response type from request headers.
     * Renders HTMX partial, JSON, or full page automatically.
     */
    public static function auto(string $template, array $data = [], int $status = 200): never
    {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            self::json($data, $status);
        }
        if (!empty($_SERVER['HTTP_HX_REQUEST'])) {
            self::htmx($template, $data);
        }
        self::view($template, $data, $status);
    }
}
