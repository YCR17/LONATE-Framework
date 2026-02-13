<?php

namespace Lonate\Core\Console;

/**
 * Class Command
 * 
 * Base class for all Artisan console commands.
 *
 * @package Lonate\Core\Console
 */
class Command
{
    /** @var string Name used to invoke the command */
    protected string $name = '';

    /** @var string Description of what the command does */
    protected string $description = '';

    /** @var array Arguments passed to the command */
    protected array $arguments = [];

    /** @var array Options (--key=value) passed to the command */
    protected array $options = [];

    /**
     * Execute the command logic.
     * Override this method in subclasses.
     *
     * @return int Exit code (0=success)
     */
    public function handle(): int
    {
        return 0;
    }

    /**
     * Set the arguments and options for this command invocation.
     *
     * @param array $argv Raw argv array
     * @return void
     */
    public function setInput(array $argv): void
    {
        $this->arguments = [];
        $this->options = [];

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $this->options[$parts[0]] = $parts[1] ?? true;
            } else {
                $this->arguments[] = $arg;
            }
        }
    }

    /**
     * Get the value of a positional argument.
     *
     * @param int $index
     * @param mixed $default
     * @return mixed
     */
    public function argument(int $index = 0, mixed $default = null): mixed
    {
        return $this->arguments[$index] ?? $default;
    }

    /**
     * Get the value of an option.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Print a line of output.
     *
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    /**
     * Print a plain line (no color).
     *
     * @param string $message
     * @return void
     */
    public function line(string $message): void
    {
        echo "{$message}\n";
    }

    /**
     * Print a warning line.
     *
     * @param string $message
     * @return void
     */
    public function warn(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }

    /**
     * Print an error line.
     *
     * @param string $message
     * @return void
     */
    public function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
