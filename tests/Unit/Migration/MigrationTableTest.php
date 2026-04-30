<?php

namespace Quantum\Tests\Unit\Migration;

use Quantum\Database\Factories\TableFactory;
use Quantum\Migration\MigrationTable;
use Quantum\Database\Schemas\Table;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Database\Enums\Type;
use Mockery;

class MigrationTableTest extends AppTestCase
{
    public function testUpCreatesExpectedSchema(): void
    {
        $table = Mockery::mock(Table::class);
        $table->shouldReceive('addColumn')->once()->with('id', Type::INT, 11)->andReturnSelf();
        $table->shouldReceive('autoIncrement')->once()->andReturnSelf();
        $table->shouldReceive('addColumn')->once()->with('migration', Type::VARCHAR, 255)->andReturnSelf();
        $table->shouldReceive('addColumn')->once()->with('applied_at', Type::TIMESTAMP)->andReturnSelf();
        $table->shouldReceive('default')->once()->with('CURRENT_TIMESTAMP', false)->andReturnSelf();

        $tableFactory = Mockery::mock(TableFactory::class);
        $tableFactory->shouldReceive('create')->once()->with(MigrationTable::TABLE)->andReturn($table);

        $migrationTable = new MigrationTable();
        $migrationTable->up($tableFactory);

        $this->assertTrue(true);
    }

    public function testDownDropsMigrationTable(): void
    {
        $tableFactory = Mockery::mock(TableFactory::class);
        $tableFactory->shouldReceive('drop')->once()->with(MigrationTable::TABLE)->andReturn(true);

        $migrationTable = new MigrationTable();
        $migrationTable->down($tableFactory);

        $this->assertTrue(true);
    }
}
