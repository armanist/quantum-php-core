<?php

namespace Quantum\Tests\Unit\Database\Adapters\Idiorm;

use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Tracer\ErrorHandler;
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

    public function testIdiormRunsAQueryWithANullParameterWhileDeprecationsAreReported(): void
    {
        IdiormDbal::execute('CREATE TABLE nullable_values (id INTEGER PRIMARY KEY, name TEXT)');

        (new ErrorHandler())->setup(logger());

        $oldLevel = error_reporting(E_ALL);

        try {
            IdiormDbal::execute('INSERT INTO nullable_values (name) VALUES (?)', [null]);

            $this->assertSame([['name' => null]], IdiormDbal::query('SELECT name FROM nullable_values'));

            $warnings = array_column(logger()->getAdapter()->getMessages(), 'warning');

            $this->assertNotEmpty(array_filter($warnings, fn ($message) => str_contains((string) $message, 'PDO::quote()')));
        } finally {
            error_reporting($oldLevel);
            restore_error_handler();
            restore_exception_handler();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getBaseConfig(): array
    {
        return $this->invokePrivateMethod(new IdiormDbal('users'), 'getBaseConfig', ['sqlite', ['database' => ':memory:']]);
    }
}
