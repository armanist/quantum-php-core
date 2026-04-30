<?php

namespace Quantum\Tests\Unit\Migration;

use Quantum\Migration\Exceptions\MigrationException;
use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Database\Database;
use Quantum\Migration\MigrationManager;
use Quantum\Loader\Setup;
use Mockery;

class MigrationManagerTest extends AppTestCase
{
    private string $migrationDir;
    /** @var array<int, string> */
    private array $existingMigrationFiles = [];

    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('database')) {
            config()->import(new Setup('config', 'database', true));
        }

        config()->set('database.default', 'sqlite');

        $this->migrationDir = base_dir() . DS . 'migrations';
        if (!is_dir($this->migrationDir)) {
            mkdir($this->migrationDir, 0777, true);
        }

        $files = glob($this->migrationDir . DS . '*.php');
        $this->existingMigrationFiles = is_array($files) ? $files : [];
    }

    public function tearDown(): void
    {
        $files = glob($this->migrationDir . DS . '*.php');
        $currentFiles = is_array($files) ? $files : [];
        $createdByTest = array_diff($currentFiles, $this->existingMigrationFiles);

        foreach ($createdByTest as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testConstructorThrowsWhenMigrationDirectoryIsMissing(): void
    {
        if (is_dir($this->migrationDir)) {
            @rmdir($this->migrationDir);
        }

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('The directory ' . $this->migrationDir . ' does not exists.');

        try {
            new MigrationManager();
        } finally {
            if (!is_dir($this->migrationDir)) {
                mkdir($this->migrationDir, 0777, true);
            }
        }
    }

    public function testGenerateMigrationThrowsForUnsupportedAction(): void
    {
        $manager = new MigrationManager();

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('The action `sync`, is not supported');

        $manager->generateMigration('users', 'sync');
    }

    public function testGenerateMigrationCreatesFileAndReturnsMigrationName(): void
    {
        $manager = new MigrationManager();

        $migrationName = $manager->generateMigration('Users', 'create');

        $this->assertMatchesRegularExpression('/^create_table_users_\d+$/', $migrationName);
        $this->assertFileExists($this->migrationDir . DS . $migrationName . '.php');
    }

    public function testApplyMigrationsThrowsForUnsupportedDriver(): void
    {
        $manager = new MigrationManager();

        $db = Mockery::mock(Database::class);
        $db->shouldReceive('getConfigs')->andReturn(['driver' => 'sqlserver']);
        $this->setPrivateProperty($manager, 'db', $db);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('The driver `sqlserver` is not supported.');

        $manager->applyMigrations(MigrationManager::UPGRADE);
    }

    public function testApplyMigrationsThrowsForWrongDirection(): void
    {
        $manager = new MigrationManager();

        $db = Mockery::mock(Database::class);
        $db->shouldReceive('getConfigs')->andReturn(['driver' => 'sqlite']);
        $this->setPrivateProperty($manager, 'db', $db);

        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('Migration direction can only be [up] or [down]');

        $manager->applyMigrations('sideways');
    }
}
