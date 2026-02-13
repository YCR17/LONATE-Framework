<?php

namespace Lonate\Core\Facades;

use Lonate\Core\Support\Facade;

class Policy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Lonate\Core\Legitimacy\Engine::class;
    }
}
