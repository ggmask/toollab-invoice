<?php

use App\Support\Config;
use App\Support\Csrf;
use App\Support\Currency;
use App\Support\Session;

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(Config::get('app.url', ''), '/');
        if (empty($base) && isset($_SERVER['HTTP_HOST'])) {
            $base = (new \App\Support\Request())->baseUrl();
        }
        $path = ltrim($path, '/');
        return $path === '' ? $base : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url(ltrim($path, '/'));
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('format_currency')) {
    function format_currency(float|int|string|null $amount, bool $withSymbol = true): string
    {
        return Currency::format($amount ?? 0, $withSymbol);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd M Y'): string
    {
        if (!$date || $date === '0000-00-00') {
            return '-';
        }
        $ts = strtotime($date);
        return $ts ? date($format, $ts) : '-';
    }
}

if (!function_exists('amount_in_words')) {
    function amount_in_words(float|int|string $amount): string
    {
        return \App\Support\NumberToWords::convertIndianCurrency($amount);
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        $oldInputs = Session::get('_old_input', []);
        return $oldInputs[$key] ?? $default;
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return Session::get('user');
    }
}

if (!function_exists('active_business')) {
    function active_business(): ?array
    {
        return Session::get('business');
    }
}
