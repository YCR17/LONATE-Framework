<?php

namespace MiniLaravel\Support;

class MiddlewareRegistrar
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function alias($name, $class = null)
    {
        // Accept alias('name', 'Class') or alias(['name' => 'Class', ...])
        if (is_array($name)) {
            foreach ($name as $a => $c) {
                $this->app->registerMiddlewareAlias($a, $c);
            }
            return $this;
        }

        $this->app->registerMiddlewareAlias($name, $class);
        return $this;
    }

    public function group($name, array $middlewares)
    {
        $this->app->registerMiddlewareGroup($name, $middlewares);
        return $this;
    }
}
