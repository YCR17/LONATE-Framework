<?php

namespace Lonate\Core\Facades;

use Lonate\Core\Support\Facade;

class Asset extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Lonate\Core\Asset\Manager::class;
    }
}
