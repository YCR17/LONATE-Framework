<?php

use MiniLaravel\Http\Response;
use MiniLaravel\Support\Application;

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        return $value;
    }
}

if (!function_exists('app')) {
    function app($abstract = null)
    {
        $app = Application::getInstance();
        
        if ($abstract === null) {
            return $app;
        }
        
        return $app->make($abstract);
    }
}

if (!function_exists('view')) {
    function view($view, $data = [])
    {
        return Response::view($view, $data);
    }
}

if (!function_exists('response')) {
    function response($content = '', $statusCode = 200)
    {
        return new Response($content, $statusCode);
    }
}

if (!function_exists('json')) {
    function json($data, $statusCode = 200)
    {
        return Response::json($data, $statusCode);
    }
}

if (!function_exists('redirect')) {
    function redirect($url)
    {
        return Response::redirect($url);
    }
}

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die();
    }
}

if (!function_exists('dump')) {
    function dump(...$vars)
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
    }
}

if (!function_exists('config')) {
    function config($key, $default = null)
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);
        
        $configFile = app()->configPath() . "/{$file}.php";
        
        if (!file_exists($configFile)) {
            return $default;
        }
        
        $config = require $configFile;
        
        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                return $default;
            }
            $config = $config[$key];
        }
        
        return $config;
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url($path = '')
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return $protocol . '://' . $host . '/' . ltrim($path, '/');
    }
}

if (!function_exists('abort')) {
    function abort($statusCode, $message = '')
    {
        http_response_code($statusCode);
        echo $message;
        die();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('http')) {
    /**
     * Return a shared HTTP client instance (fluent API similar to Laravel's Http facade)
     * Usage: http()->post(...), Http::post(...)
     */
    function http(): \MiniLaravel\Http\Client
    {
        static $client = null;
        if ($client === null) {
            $client = new \MiniLaravel\Http\Client();
        }
        return $client;
    }
}