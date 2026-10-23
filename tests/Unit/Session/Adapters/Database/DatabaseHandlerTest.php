<?php

namespace Quantum\Tests\Unit\Session\Adapters\Database;

use Quantum\Session\Adapters\Database\DatabaseHandler;
use Quantum\Tests\Unit\AppTestCase;
use ReflectionMethod;

class DatabaseHandlerTest extends AppTestCase
{
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
