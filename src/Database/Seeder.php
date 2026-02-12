<?php

namespace Aksa\Database;

class Seeder
{
    public function call($class)
    {
        $path = dirname(__DIR__, 2) . '/database/seeders/' . $class . '.php';
        if (file_exists($path)) {
            require_once $path;
            if (class_exists($class)) {
                $instance = new $class;
                if (method_exists($instance, 'run')) {
                    $instance->run();
                }
            }
        }
    }
}
