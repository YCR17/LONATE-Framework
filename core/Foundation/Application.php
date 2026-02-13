<?php

namespace Lonate\Core\Foundation;

use ReflectionClass;
use ReflectionException;
use Lonate\Core\Support\ServiceProvider;

/**
 * Class Application
 * 
 * The central Service Container and Application instance.
 * It manages dependency injection and singleton bindings.
 * 
 * @package Lonate\Core\Foundation
 */
class Application
{
    const VERSION = '1.0.0 (Hilirisasi Edition)';

    /** @var array<string, object|callable> */
    protected array $bindings = [];

    /** @var array<string, object> */
    protected array $instances = [];

    /** @var string */
    protected string $basePath;

    /**
     * Create a new application instance.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->bind(self::class, fn() => $this);
    }

    /**
     * Bind a service to the container.
     *
     * @param string $abstract
     * @param callable|object|null $concrete
     */
    public function bind(string $abstract, $concrete = null): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    /** @var array<string, bool> Track which bindings are singletons */
    protected array $singletons = [];

    /**
     * Register a shared binding in the container (Singleton).
     *
     * @param string $abstract
     * @param callable|object|null $concrete
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        $this->singletons[$abstract] = true;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @return mixed
     * @throws ReflectionException
     */
    public function make(string $abstract): mixed
    {
        // 1. Return existing resolved singleton if available
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 2. Resolve binding
        $concrete = $this->bindings[$abstract] ?? $abstract;

        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } else {
            // Simple auto-wiring for classes
            if (!class_exists($concrete)) {
                 // Return string if not a class (e.g. config path), or throw
                 if ($abstract === $concrete) {
                     throw new ReflectionException("Target class [$concrete] does not exist.");
                 }
                 return $concrete;
            }
            $object = $this->build($concrete);
        }

        // 3. Cache if it's a singleton
        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Instantiate a concrete instance with auto-wiring.
     * 
     * @param string $concrete
     * @return object
     * @throws ReflectionException
     */
    protected function build(string $concrete): object
    {
        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new ReflectionException("Class [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            } else {
                // For skeleton, we don't handle primitives yet
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new ReflectionException("Cannot resolve primitive dependency for [$concrete].");
                }
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function databasePath(string $path = ''): string
    {
        return $this->basePath('database') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /** @var ServiceProvider[] */
    protected array $serviceProviders = [];
    protected bool $booted = false;

    /**
     * Register all configured service providers.
     */
    public function registerConfiguredProviders(): void
    {
        $providers = config('app.providers', []);
        
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Lonate\Core\Support\ServiceProvider|string  $provider
     * @return \Lonate\Core\Support\ServiceProvider
     */
    public function register($provider): ServiceProvider
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        $this->serviceProviders[] = $provider;

        if ($this->booted) {
             $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }

        $this->booted = true;
    }

    protected function bootProvider(ServiceProvider $provider): void
    {
        if (method_exists($provider, 'boot')) {
            $provider->boot();
        }
    }
}
