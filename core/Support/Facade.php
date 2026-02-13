<?php

namespace Lonate\Core\Support;

use Lonate\Core\Foundation\Application;
use RuntimeException;

/**
 * Class Facade
 * 
 * Provides a static interface to classes available in the application's service container.
 */
abstract class Facade
{
    /**
     * The application instance.
     *
     * @var Application|null
     */
    protected static ?Application $app = null;

    /**
     * Set the application instance.
     *
     * @param Application $app
     */
    public static function setFacadeApplication(Application $app): void
    {
        static::$app = $app;
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Handle dynamic calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws RuntimeException
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot(): mixed
    {
        $name = static::getFacadeAccessor();
        return static::$app->make($name);
    }

    /**
     * Get the application instance behind all facades.
     *
     * @return Application|null
     */
    public static function getFacadeApplication(): ?Application
    {
        return static::$app;
    }
}
