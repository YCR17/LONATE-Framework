<?php

namespace Aksa\Support;

class ExceptionsConfigurator
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handler($class)
    {
        $this->app->setExceptionHandlerClass($class);
        return $this;
    }
}
