<?php

namespace Quantum\Tests\Unit\App\Helpers;

use Quantum\Tests\Unit\AppTestCase;
use Quantum\App\Enums\AppType;

class AppHelperTest extends AppTestCase
{
    public function testIsWebAppInTheWebApp(): void
    {
        $this->useAppType(AppType::WEB);

        $this->assertTrue(is_web_app());
        $this->assertFalse(is_console_app());
    }

    public function testIsConsoleAppInTheConsole(): void
    {
        $this->useAppType(AppType::CONSOLE);

        $this->assertTrue(is_console_app());
        $this->assertFalse(is_web_app());
    }
}
