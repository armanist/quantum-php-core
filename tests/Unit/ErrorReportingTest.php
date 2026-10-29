<?php

namespace Quantum\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ErrorReportingTest extends TestCase
{
    public function testTestsReportEveryError(): void
    {
        $this->assertSame(E_ALL, error_reporting() & E_ALL);
    }
}
