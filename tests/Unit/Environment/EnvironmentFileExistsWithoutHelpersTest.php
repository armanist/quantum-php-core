<?php

namespace Quantum\Tests\Unit\Environment;

use Quantum\Environment\Environment;
use Quantum\Tests\Unit\AppTestCase;

class EnvironmentFileExistsWithoutHelpersTest extends AppTestCase
{
    public function setUp(): void
    {
        // The app is not booted on purpose, so the helpers stay unloaded.
        $this->context = $this->createContext();
    }

    public function tearDown(): void
    {
        $this->clearAppContext();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFileExistsWorksBeforeTheHelpersAreLoaded(): void
    {
        $this->assertFalse(function_exists('base_dir'));

        $this->assertTrue((new Environment())->fileExists());
    }
}
