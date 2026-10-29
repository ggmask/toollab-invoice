<?php

namespace App\Support;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        if (!headers_sent()) {
            $lifetime = (int)Config::get('app.session_lifetime', 604800);
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                       (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_name('toollab_sess');
            @session_start();
        } else {
            @session_start();
        }
        self::$started = true;

        // Initialize flash storage
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        if (!isset($_SESSION['_old_flash'])) {
            $_SESSION['_old_flash'] = [];
        }
    }

    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function regenerate(bool $destroyOld = true): void
    {
        self::start();
        session_regenerate_id($destroyOld);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        self::$started = false;
    }

    public static function flash(string $key, $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, $default = null)
    {
        self::start();
        if (isset($_SESSION['_flash'][$key])) {
            $val = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $val;
        }
        return $default;
    }

    public static function flashErrors(array $errors): void
    {
        self::flash('errors', $errors);
    }

    public static function flashSuccess(string $message): void
    {
        self::flash('success', $message);
    }

    public static function flashError(string $message): void
    {
        self::flash('error', $message);
    }
}
