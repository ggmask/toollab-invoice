<?php

namespace App\Support;

class Config
{
    private static array $configs = [];
    private static bool $envLoaded = false;

    public static function loadEnv(string $path): void
    {
        if (self::$envLoaded || !file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Strip quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (getenv($key) === false) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        self::$envLoaded = true;
    }

    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$configs[$file])) {
            $filePath = __DIR__ . '/../../config/' . $file . '.php';
            if (file_exists($filePath)) {
                self::$configs[$file] = require $filePath;
            } else {
                return $default;
            }
        }

        $current = self::$configs[$file];
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }

    public static function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$configs[$file])) {
            $filePath = __DIR__ . '/../../config/' . $file . '.php';
            if (file_exists($filePath)) {
                self::$configs[$file] = require $filePath;
            } else {
                self::$configs[$file] = [];
            }
        }

        if (empty($parts)) {
            self::$configs[$file] = $value;
            return;
        }

        $current = &self::$configs[$file];
        while (count($parts) > 1) {
            $part = array_shift($parts);
            if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        $current[array_shift($parts)] = $value;
    }
}
