<?php

use Lonate\Core\Foundation\Application;
use Lonate\Core\Http\Response;
use Lonate\Core\View\Factory;

if (!function_exists('app')) {
    /**
     * Get the application instance or resolve a binding.
     *
     * @param string|null $abstract
     * @return mixed
     */
    function app(string $abstract = null)
    {
        $instance = $GLOBALS['app'] ?? \Lonate\Core\Support\Facade::getFacadeApplication();
        
        if ($abstract) {
            return $instance->make($abstract);
        }
        
        return $instance;
    }
}

if (!function_exists('env')) {
    /**
     * Get the value of an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Handle boolean strings
        $lower = strtolower($value);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;
        if ($lower === '(empty)') return '';
        
        // Strip surrounding quotes
        if (preg_match('/^"(.*)"$/', $value, $m)) return $m[1];
        if (preg_match("/^'(.*)'$/", $value, $m)) return $m[1];
        
        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation.
     *
     * @param string $key e.g. 'database.default'
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        
        static $configs = [];
        
        if (!isset($configs[$file])) {
            $basePath = app()->basePath();
            $path = $basePath . "/config/{$file}.php";
            if (file_exists($path)) {
                $configs[$file] = require $path;
            } else {
                $configs[$file] = [];
            }
        }
        
        $value = $configs[$file];
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view template and return a Response.
     *
     * @param string $view Dot-notation view name
     * @param array $data Variables to pass to the view
     * @return Response
     */
    function view(string $view, array $data = []): Response
    {
        $factory = app(\Lonate\Core\View\Factory::class);
        return new Response($factory->make($view, $data));
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the base path of the application.
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the storage path.
     *
     * @param string $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return app()->storagePath($path);
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the config directory path.
     *
     * @param string $path
     * @return string
     */
    function config_path(string $path = ''): string
    {
        return app()->configPath($path);
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the database directory path.
     *
     * @param string $path
     * @return string
     */
    function database_path(string $path = ''): string
    {
        return app()->databasePath($path);
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response.
     *
     * @param string $url
     * @param int $status
     * @return Response
     */
    function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die. Debug helper.
     *
     * @param mixed ...$vars
     * @return never
     */
    function dd(...$vars): never
    {
        echo "<pre>";
        foreach ($vars as $var) {
            var_export($var);
            echo "\n";
        }
        echo "</pre>";
        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump without dying. Debug helper.
     *
     * @param mixed ...$vars
     * @return void
     */
    function dump(...$vars): void
    {
        echo "<pre>";
        foreach ($vars as $var) {
            var_export($var);
            echo "\n";
        }
        echo "</pre>";
    }
}
