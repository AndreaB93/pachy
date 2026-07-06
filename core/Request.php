<?php
declare(strict_types=1);

namespace Core;

class Request
{
    private array $body;
    private array $query;
    private array $params = []; // route params injected by Router

    public function __construct()
    {
        $this->query = $_GET;
        $this->body  = match(true) {
            str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
                => json_decode(file_get_contents('php://input'), true) ?? [],
            default => $_POST,
        };
    }

    public function body(string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->body : ($this->body[$key] ?? $default);
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function isHtmx(): bool
    {
        return !empty($_SERVER['HTTP_HX_REQUEST']);
    }

    public function expectsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
