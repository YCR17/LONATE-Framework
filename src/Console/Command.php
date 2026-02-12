<?php

namespace Aksa\Console;

abstract class Command
{
    protected $app;
    protected $signature = '';
    protected $description = '';

    public function __construct($app)
    {
        $this->app = $app;
    }

    abstract public function handle($args = []);

    // Output helpers (simple ANSI colors compatible with terminals)
    protected function line($text = '')
    {
        echo $text . PHP_EOL;
    }

    protected function info($text)
    {
        echo "\033[32m" . $text . "\033[0m" . PHP_EOL;
    }

    protected function error($text)
    {
        echo "\033[31m" . $text . "\033[0m" . PHP_EOL;
    }

    protected function comment($text)
    {
        echo "\033[33m" . $text . "\033[0m" . PHP_EOL;
    }

    protected function ask($question, $default = null)
    {
        echo $question . ' ';
        $answer = trim(fgets(STDIN));
        return $answer === '' ? $default : $answer;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
