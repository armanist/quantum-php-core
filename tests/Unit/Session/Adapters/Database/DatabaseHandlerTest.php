<?php

namespace Quantum\Tests\Unit\Session\Adapters\Database;

use Quantum\Session\Adapters\Database\DatabaseHandler;
use Quantum\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Tests\Unit\Session\TestCaseHelper;
use Quantum\Database\Contracts\DbalInterface;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Tests\Unit\AppTestCase;
use ReflectionMethod;
use Mockery;

class DatabaseHandlerTest extends AppTestCase
{
    use TestCaseHelper;

    public function setUp(): void
    {
        parent::setUp();

        IdiormDbal::connect(['driver' => 'sqlite', 'database' => ':memory:']);

        $this->_createSessionsTable();
    }

    public function tearDown(): void
    {
        ModelFactory::createDynamicModel('sessions')->deleteMany();

        parent::tearDown();
    }

    public function testDatabaseHandlerGcDeletesTheExpiredSessionsAndKeepsTheOthers(): void
    {
        $handler = new DatabaseHandler(ModelFactory::createDynamicModel('sessions')->getOrmInstance());

        $handler->write('expired', 'old data');
        $handler->write('active', 'new data');

        IdiormDbal::execute('UPDATE sessions SET ttl = ? WHERE session_id = ?', [time() - 7200, 'expired']);

        $this->assertSame(0, $handler->gc(3600));

        $this->assertSame([['session_id' => 'active']], IdiormDbal::query('SELECT session_id FROM sessions'));
    }

    public function testDatabaseHandlerGcReturnsFalseWhenTheSessionsCannotBeDeleted(): void
    {
        $model = Mockery::mock(DbalInterface::class);
        $model->shouldReceive('criteria')->with('ttl', '<', Mockery::type('int'))->andReturnSelf();
        $model->shouldReceive('deleteMany')->andReturn(false);

        $this->assertFalse((new DatabaseHandler($model))->gc(3600));
    }

    /**
     * @dataProvider interfaceMethodProvider
     */
    public function testDatabaseHandlerDeclaresTheReturnTypeOfItsInterfaceMethods(string $method): void
    {
        $this->assertTrue((new ReflectionMethod(DatabaseHandler::class, $method))->hasReturnType());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function interfaceMethodProvider(): array
    {
        return [
            'open' => ['open'],
            'close' => ['close'],
            'read' => ['read'],
            'write' => ['write'],
            'destroy' => ['destroy'],
            'gc' => ['gc'],
        ];
    }
}
