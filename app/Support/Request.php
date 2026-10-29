<?php

namespace App\Support;

class Request
{
    private string $method;
    private string $path;
    private array $queryParams;
    private array $postData;
    private array $serverData;
    private ?array $jsonData = null;

    public function __construct()
    {
        $this->serverData = $_SERVER;
        $this->method = strtoupper($_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->queryParams = $_GET;
        $this->postData = $_POST;
        $this->path = $this->determinePath();
    }

    private function determinePath(): string
    {
        $uri = parse_url($this->serverData['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptName = $this->serverData['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);

        // Normalize slashes
        $baseDir = str_replace('\\', '/', $baseDir);
        if ($baseDir !== '/' && str_starts_with($uri, $baseDir)) {
            $uri = substr($uri, strlen($baseDir));
        }

        $uri = '/' . ltrim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    public function get(string $key, $default = null)
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function post(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->postData;
        }
        return $this->postData[$key] ?? $default;
    }

    public function input(?string $key = null, $default = null)
    {
        $all = array_merge($this->queryParams, $this->postData, $this->json());
        if ($key === null) {
            return $all;
        }
        return $all[$key] ?? $default;
    }

    public function json(?string $key = null, $default = null)
    {
        if ($this->jsonData === null) {
            $content = file_get_contents('php://input');
            $decoded = json_decode($content, true);
            $this->jsonData = is_array($decoded) ? $decoded : [];
        }

        if ($key === null) {
            return $this->jsonData;
        }
        return $this->jsonData[$key] ?? $default;
    }

    public function header(string $name, $default = null): ?string
    {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->serverData[$headerKey] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest' ||
               str_contains($this->header('Accept', ''), 'application/json');
    }

    public function ip(): string
    {
        return $this->serverData['HTTP_X_FORWARDED_FOR'] ??
               $this->serverData['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function baseUrl(): string
    {
        $scriptName = $this->serverData['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $protocol = (!empty($this->serverData['HTTPS']) && $this->serverData['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $this->serverData['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host . $baseDir;
    }
}
