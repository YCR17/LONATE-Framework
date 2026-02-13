<?php

namespace Lonate\Core\Providers;

use Lonate\Core\Support\ServiceProvider;
use Lonate\Core\Http\Router;
use Lonate\Core\Http\Kernel;

class LonateServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Core Singletons
        $this->app->singleton(Router::class);
        $this->app->singleton(Kernel::class);
        $this->app->singleton(\Lonate\Core\Database\Manager::class);
        $this->app->singleton(\Lonate\Core\Legitimacy\Engine::class);
        $this->app->singleton(\Lonate\Core\Asset\Manager::class);
        $this->app->singleton(\Lonate\Core\Trade\Grant::class);
        $this->app->singleton(\Lonate\Core\Trade\Auction::class);
        $this->app->singleton(\Lonate\Core\Trade\Accountability::class);
        $this->app->singleton(\Lonate\Core\Console\Kernel::class);
        $this->app->singleton(\Lonate\Core\View\Factory::class);
        $this->app->singleton(\Lonate\Core\Exceptions\Handler::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
