<?php

namespace Aksa\Console\Commands;

use Aksa\Console\Command;

class ServeCommand extends Command
{
    protected $description = 'Serve the application on the PHP development server';

    public function handle($args = [])
    {
        $host = '127.0.0.1';
        $port = '8000';

        // parse options --host= and --port=
        foreach ($args as $a) {
            if (strpos($a, '--host=') === 0) {
                $host = substr($a, 7);
            }
            if (strpos($a, '--port=') === 0) {
                $port = substr($a, 7);
            }
        }

        $this->info("Starting development server: http://{$host}:{$port}");
        $this->comment('Press Ctrl-C to stop the server');

        // Use passthru so user sees server output
        $cmd = PHP_BINARY . " -S {$host}:{$port} -t public";

        // On Windows use proc_open to inherit streams; passthru works too but we'll use proc_open for portability
        $descriptors = [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR
        ];

        // exec the server (this will block)
        passthru($cmd);
    }
}
