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

// ═══════════════════════════════════════════════════
//  HTTP HELPERS
// ═══════════════════════════════════════════════════

if (!function_exists('response')) {
    /**
     * Create an HTTP response.
     */
    function response(mixed $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('request')) {
    /**
     * Get the current Request instance or an input value.
     */
    function request(?string $key = null, mixed $default = null): mixed
    {
        $req = app(\Lonate\Core\Http\Request::class);
        if ($key !== null) {
            return $req->input($key, $default);
        }
        return $req;
    }
}

if (!function_exists('url')) {
    /**
     * Generate an absolute URL.
     */
    function url(?string $path = null): string
    {
        $scheme = ($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = "{$scheme}://{$host}";
        
        if ($path === null) return $base;
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('back')) {
    /**
     * Redirect to the previous URL.
     */
    function back(int $status = 302, array $headers = []): Response
    {
        $url = $_SERVER['HTTP_REFERER'] ?? '/';
        return redirect($url, $status);
    }
}

if (!function_exists('abort')) {
    /**
     * Throw an HTTP exception.
     */
    function abort(int $code, string $message = '', array $headers = []): never
    {
        throw new \Lonate\Core\Http\Exceptions\HttpException($code, $message, $headers);
    }
}

if (!function_exists('abort_if')) {
    /**
     * Throw an HTTP exception if condition is true.
     */
    function abort_if(bool $condition, int $code, string $message = ''): void
    {
        if ($condition) {
            abort($code, $message);
        }
    }
}

if (!function_exists('abort_unless')) {
    /**
     * Throw an HTTP exception unless condition is true.
     */
    function abort_unless(bool $condition, int $code, string $message = ''): void
    {
        if (!$condition) {
            abort($code, $message);
        }
    }
}

// ═══════════════════════════════════════════════════
//  COLLECTION & DATA HELPERS
// ═══════════════════════════════════════════════════

if (!function_exists('collect')) {
    /**
     * Create a new Collection instance.
     */
    function collect(array $items = []): \Lonate\Core\Support\Collection
    {
        return new \Lonate\Core\Support\Collection($items);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array/object using dot notation.
     */
    function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) return $target;

        $keys = is_int($key) ? [$key] : explode('.', $key);

        foreach ($keys as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}

if (!function_exists('data_set')) {
    /**
     * Set an item in an array using dot notation.
     */
    function data_set(array &$target, string $key, mixed $value): array
    {
        $keys = explode('.', $key);

        $current = &$target;
        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $current[$segment] = $value;
            } else {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }

        return $target;
    }
}

if (!function_exists('array_flatten')) {
    /**
     * Flatten a multi-dimensional array into a single level.
     */
    function array_flatten(array $array): array
    {
        $result = [];
        array_walk_recursive($array, function ($item) use (&$result) {
            $result[] = $item;
        });
        return $result;
    }
}

// ═══════════════════════════════════════════════════
//  VALUE / TYPE HELPERS
// ═══════════════════════════════════════════════════

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     * If a Closure, execute it.
     */
    function value(mixed $value, ...$args): mixed
    {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     */
    function blank(mixed $value): bool
    {
        if (is_null($value)) return true;
        if (is_string($value)) return trim($value) === '';
        if (is_array($value)) return empty($value);
        if ($value instanceof \Countable) return count($value) === 0;
        return false;
    }
}

if (!function_exists('filled')) {
    /**
     * Determine if a value is "filled" (opposite of blank).
     */
    function filled(mixed $value): bool
    {
        return !blank($value);
    }
}

if (!function_exists('optional')) {
    /**
     * Provide access to optional objects.
     * Returns a null-safe object wrapper.
     */
    function optional(mixed $value): mixed
    {
        if (is_null($value)) {
            return new class {
                public function __get($name) { return null; }
                public function __call($method, $args) { return null; }
                public function __isset($name) { return false; }
                public function __toString() { return ''; }
            };
        }
        return $value;
    }
}

if (!function_exists('now')) {
    /**
     * Get the current date/time as a string.
     */
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}

if (!function_exists('today')) {
    /**
     * Get today's date.
     */
    function today(string $format = 'Y-m-d'): string
    {
        return date($format);
    }
}

// ═══════════════════════════════════════════════════
//  FUNCTIONAL HELPERS
// ═══════════════════════════════════════════════════

if (!function_exists('retry')) {
    /**
     * Retry a callback a given number of times.
     */
    function retry(int $times, callable $callback, int $sleepMs = 0, ?callable $when = null): mixed
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $times) {
            $attempts++;
            try {
                return $callback($attempts);
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($when && !$when($e)) throw $e;
                if ($attempts < $times && $sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        }

        throw $lastException;
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     */
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if ($callback) {
            $callback($value);
        }
        return $value;
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value, optionally passed through a callback.
     */
    function with(mixed $value, ?callable $callback = null): mixed
    {
        return $callback ? $callback($value) : $value;
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class basename (without namespace).
     */
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

// ═══════════════════════════════════════════════════
//  PATH HELPERS
// ═══════════════════════════════════════════════════

if (!function_exists('resource_path')) {
    /**
     * Get the resources directory path.
     */
    function resource_path(string $path = ''): string
    {
        return base_path('resources' . ($path ? "/{$path}" : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the public directory path.
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? "/{$path}" : ''));
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the application directory path.
     */
    function app_path(string $path = ''): string
    {
        return base_path('app' . ($path ? "/{$path}" : ''));
    }
}

// ═══════════════════════════════════════════════════
//  STRING HELPERS
// ═══════════════════════════════════════════════════

if (!function_exists('str')) {
    /**
     * Create a Stringable instance for fluent string operations.
     */
    function str(?string $string = null): \Lonate\Core\Support\Stringable
    {
        return new \Lonate\Core\Support\Stringable($string ?? '');
    }
}

if (!function_exists('e')) {
    /**
     * Encode HTML entities.
     */
    function e(?string $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}
