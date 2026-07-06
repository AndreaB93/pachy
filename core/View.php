<?php
declare(strict_types=1);

namespace Core;

class View
{
    private static string $viewPath = '';

    public static function setPath(string $path): void
    {
        self::$viewPath = rtrim($path, '/\\');
    }

    /** @internal Set by layout() during rendering */
    private static ?string $pendingLayout = null;
    private static array   $pendingLayoutData = [];

    public static function render(string $template, array $data = []): void
    {
        if (self::$viewPath === '') {
            self::$viewPath = dirname(__DIR__) . '/app/Views';
        }

        $file = self::$viewPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: $template ($file)");
        }

        self::$pendingLayout     = null;
        self::$pendingLayoutData = [];

        ob_start();
        extract($data, EXTR_SKIP);
        require $file;
        $content = ob_get_clean();

        // If the template declared a layout, wrap content inside it
        if (self::$pendingLayout !== null) {
            $layoutData            = array_merge($data, self::$pendingLayoutData, ['content' => $content]);
            self::$pendingLayout   = null;
            self::render(self::$pendingLayout, $layoutData);
        } else {
            echo $content;
        }
    }

    /**
     * Declare a layout from inside a page template.
     * Usage: <?php View::layout('layouts/main', ['title' => 'Home']) ?>
     */
    public static function layout(string $template, array $data = []): void
    {
        self::$pendingLayout     = $template;
        self::$pendingLayoutData = $data;
    }

    /** Escape for HTML output — always use in templates */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
