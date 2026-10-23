<?php

namespace Quantum\Tests\Unit\Database\Adapters\Idiorm;

use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\App\Enums\AppType;

class IdiormDbalTest extends IdiormDbalTestCase
{
    public function testIdiormConstructor(): void
    {
        $userModel = new IdiormDbal('users');

        $this->assertInstanceOf(IdiormDbal::class, $userModel);
    }

    public function testIdiormGetTable(): void
    {
        $userModel = new IdiormDbal('users');

        $this->assertEquals('users', $userModel->getTable());
    }

    public function testIdiormLogsQueriesInTheWebAppInDebugMode(): void
    {
        config()->set('app.debug', true);

        $this->assertTrue($this->getBaseConfig()['logging']);
    }

    public function testIdiormLogsQueriesInTheConsoleInDebugMode(): void
    {
        $this->useAppType(AppType::CONSOLE);

        config()->set('app.debug', true);

        $this->assertTrue($this->getBaseConfig()['logging']);
    }

    public function testIdiormLogsNoQueriesOutsideDebugMode(): void
    {
        config()->set('app.debug', false);

        $this->assertFalse($this->getBaseConfig()['logging']);
    }

    public function testIdiormLogsNoQueriesWhenDebugIsTheTextFalseFromEnv(): void
    {
        config()->set('app.debug', 'false');

        $this->assertFalse($this->getBaseConfig()['logging']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getBaseConfig(): array
    {
        return $this->invokePrivateMethod(new IdiormDbal('users'), 'getBaseConfig', ['sqlite', ['database' => ':memory:']]);
    }
}
