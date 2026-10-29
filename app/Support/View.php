<?php

namespace App\Support;

class View
{
    private static array $shared = [];

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = 'app'): string
    {
        $mergedData = array_merge(self::$shared, $data);
        extract($mergedData);

        // Flash message convenience
        $flashSuccess = Session::getFlash('success');
        $flashError = Session::getFlash('error');
        $flashErrors = Session::getFlash('errors', []);

        $viewFile = __DIR__ . '/../Views/' . str_replace('.', '/', $template) . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View template not found: {$template} ({$viewFile})");
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout not found: {$layout} ({$layoutFile})");
        }

        ob_start();
        include $layoutFile;
        return ob_get_clean();
    }
}
