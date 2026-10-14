<?php

namespace Quantum\Tests\Unit\Console\Commands;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Quantum\Console\Commands\EnvCommand;
use Quantum\Tests\Unit\AppTestCase;

class EnvCommandTest extends AppTestCase
{
    private EnvCommand $command;

    private CommandTester $tester;

    public function setUp(): void
    {
        parent::setUp();

        $this->command = new EnvCommand();
        $this->tester = new CommandTester($this->command);
    }

    public function testCommandMetadata(): void
    {
        $this->assertSame('core:env', $this->command->getName());
        $this->assertSame('Generates new .env file', $this->command->getDescription());
        $this->assertSame('The command will generate new .env file from .env.example', $this->command->getHelp());
    }

    public function testCommandDoesNotRequireTheEnvironment(): void
    {
        $this->assertFalse($this->command->requiresEnvironment());
    }

    public function testExecAsksForConfirmationWhenTheBaseDirEnvFileExists(): void
    {
        $envFile = base_dir() . DS . '.env';
        $workingDirectory = getcwd();

        $this->assertFileDoesNotExist($envFile);

        try {
            $this->createFile($envFile, "ORIGINAL=1\n");

            // A different working directory shows that the command looks at the base dir and not at the current dir
            chdir(sys_get_temp_dir());

            $this->command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));
            $this->tester->setInputs(['no']);
            $this->tester->execute([]);

            $this->assertStringContainsString('Operation was canceled!', $this->tester->getDisplay());
            $this->assertSame("ORIGINAL=1\n", file_get_contents($envFile));
        } finally {
            chdir($workingDirectory);
            $this->removeFile($envFile);
        }
    }

    public function testExecOverwritesTheBaseDirEnvFileWithoutAskingWhenYesIsGiven(): void
    {
        $envFile = base_dir() . DS . '.env';

        $this->assertFileDoesNotExist($envFile);

        try {
            $this->createFile($envFile, "ORIGINAL=1\n");

            $this->tester->execute(['--yes' => true]);

            $this->assertStringContainsString('.env is successfully generated', $this->tester->getDisplay());
            $this->assertSame(file_get_contents(base_dir() . DS . '.env.example'), file_get_contents($envFile));
        } finally {
            $this->removeFile($envFile);
        }
    }

    public function testExecCreatesTheEnvFileWithoutAskingWhenThereIsNone(): void
    {
        $envFile = base_dir() . DS . '.env';

        $this->assertFileDoesNotExist($envFile);

        try {
            $this->tester->execute([]);

            $this->assertStringContainsString('.env is successfully generated', $this->tester->getDisplay());
            $this->assertFileExists($envFile);
            $this->assertSame(file_get_contents(base_dir() . DS . '.env.example'), file_get_contents($envFile));
        } finally {
            $this->removeFile($envFile);
        }
    }

    public function testExecShowsErrorWhenEnvExampleMissing(): void
    {
        $envExample = base_dir() . DS . '.env.example';
        $backup = $envExample . '.' . uniqid('bak', true);
        $existed = file_exists($envExample);

        if ($existed) {
            rename($envExample, $backup);
        }

        try {
            $this->tester->execute([]);

            $output = $this->tester->getDisplay();
            $this->assertStringContainsString('.env.example file not found', $output);
        } finally {
            if ($existed && file_exists($backup)) {
                rename($backup, $envExample);
            }
        }
    }
}
