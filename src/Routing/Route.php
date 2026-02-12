<?php

namespace Aksa\Routing;

class Route
{
    protected static $router;

    public static function setRouter($router)
    {
        static::$router = $router;
    }

    public static function get($uri, $action)
    {
        return static::$router->get($uri, $action);
    }

    public static function post($uri, $action)
    {
        return static::$router->post($uri, $action);
    }

    public static function put($uri, $action)
    {
        return static::$router->put($uri, $action);
    }

    public static function delete($uri, $action)
    {
        return static::$router->delete($uri, $action);
    }

    public static function patch($uri, $action)
    {
        return static::$router->patch($uri, $action);
    }

    public static function middleware($middleware)
    {
        return static::$router->middleware($middleware);
    }

    public static function prefix($prefix)
    {
        return static::$router->prefix($prefix);
    }

    public static function group(array $attributes, \Closure $callback)
    {
        return static::$router->group($attributes, $callback);
    }
}
