<?php

namespace Quantum\Tests\Helpers;

use Symfony\Component\Console\Application;

/**
 * A Symfony Application for tests: its run() returns the exit code instead of calling exit(),
 * and exceptions of a command reach the caller instead of being printed.
 */
class NonExitingApplication extends Application
{
    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->setAutoExit(false);
        $this->setCatchExceptions(false);
    }
}
