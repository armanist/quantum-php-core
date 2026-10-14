<?php

namespace Quantum\Tests\Unit\App\Stages\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Quantum\App\Stages\Console\RunCommandStage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Application;
use Quantum\App\Enums\ExitCode;
use Quantum\Console\CliCommand;
use RuntimeException;
use Exception;

class RunCommandStageTest extends ConsoleStageTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Symfony stores the verbosity of an earlier `--quiet` run in the process environment,
        // which would silence the output of every later run in the same test process.
        putenv('SHELL_VERBOSITY');
        unset($_ENV['SHELL_VERBOSITY'], $_SERVER['SHELL_VERBOSITY']);
    }

    public function testRunCommandStageStoresSuccessExitCode(): void
    {
        $context = $this->createConsoleContext(['command' => 'run:ok'], $this->createApplication('run:ok', 0));

        (new RunCommandStage())->process($context);

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
    }

    public function testRunCommandStageStoresNonZeroExitCode(): void
    {
        $context = $this->createConsoleContext(['command' => 'run:fail'], $this->createApplication('run:fail', 3));

        (new RunCommandStage())->process($context);

        $this->assertSame(3, $context->getExitCode());
    }

    public function testRunCommandStageRunsTheCommandWithTheContextOutput(): void
    {
        $context = $this->createConsoleContext(['command' => 'run:ok'], $this->createApplication('run:ok', 0));

        (new RunCommandStage())->process($context);

        $this->assertStringContainsString('hello from run:ok', $context->getOutput()->fetch());
    }

    public function testRunCommandStageRejectsUnknownCommand(): void
    {
        $context = $this->createConsoleContext(['command' => 'unknown:command'], $this->createApplication('run:ok', 0));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `unknown:command` is not defined');

        (new RunCommandStage())->process($context);
    }

    public function testRunCommandStageRejectsMissingCommandName(): void
    {
        $context = $this->createConsoleContext([], $this->createApplication('run:ok', 0));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `` is not defined');

        (new RunCommandStage())->process($context);
    }

    public function testRunCommandStageRunsNothingWhenTheCommandNameIsMissing(): void
    {
        $context = $this->createConsoleContext([], $this->createApplication('run:fail', 3));

        try {
            (new RunCommandStage())->process($context);
        } catch (Exception $e) {
            // expected, the command name is missing
        }

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
        $this->assertSame('', $context->getOutput()->fetch());
    }

    public function testRunCommandStageRejectsUnknownCommandWhenTheBootFailed(): void
    {
        $context = $this->createConsoleContext(['command' => 'unknown:command'], $this->createApplication('run:ok', 0), new RuntimeException('The boot failed'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Command `unknown:command` is not defined');

        (new RunCommandStage())->process($context);
    }

    public function testRunCommandStageRejectsACommandThatRequiresTheEnvironmentWhenTheBootFailed(): void
    {
        $failure = new RuntimeException('The boot failed');
        $context = $this->createConsoleContext(['command' => 'needs:env'], $this->createCliApplication('needs:env', true), $failure);

        try {
            (new RunCommandStage())->process($context);

            $this->fail('The command must not run after a failed boot.');
        } catch (RuntimeException $e) {
            $this->assertSame($failure, $e);
        }

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
        $this->assertSame('', $context->getOutput()->fetch());
    }

    public function testRunCommandStageRunsACommandThatDoesNotRequireTheEnvironmentWhenTheBootFailed(): void
    {
        $context = $this->createConsoleContext(['command' => 'opt:out'], $this->createCliApplication('opt:out', false), new RuntimeException('The boot failed'));

        (new RunCommandStage())->process($context);

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
        $this->assertStringContainsString('ran opt:out', $context->getOutput()->fetch());
    }

    public function testRunCommandStageRunsAPlainSymfonyCommandWhenTheBootFailed(): void
    {
        $context = $this->createConsoleContext(['command' => 'run:ok'], $this->createApplication('run:ok', 0), new RuntimeException('The boot failed'));

        (new RunCommandStage())->process($context);

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
        $this->assertStringContainsString('hello from run:ok', $context->getOutput()->fetch());
    }

    public function testRunCommandStageRunsACommandThatRequiresTheEnvironmentWhenTheBootSucceeded(): void
    {
        $context = $this->createConsoleContext(['command' => 'needs:env'], $this->createCliApplication('needs:env', true));

        (new RunCommandStage())->process($context);

        $this->assertSame(ExitCode::SUCCESS, $context->getExitCode());
        $this->assertStringContainsString('ran needs:env', $context->getOutput()->fetch());
    }

    /**
     * An application with one CliCommand that does or does not require the environment
     */
    private function createCliApplication(string $commandName, bool $requiresEnvironment): Application
    {
        $application = new Application();

        $application->add(new class ($commandName, $requiresEnvironment) extends CliCommand {
            public function __construct(string $commandName, bool $requiresEnvironment)
            {
                $this->name = $commandName;
                $this->requiresEnvironment = $requiresEnvironment;

                parent::__construct();
            }

            public function exec(): void
            {
                $this->output('ran ' . $this->name);
            }
        });

        return $application;
    }

    private function createApplication(string $commandName, int $exitCode): Application
    {
        $application = new Application();

        $application->add(
            (new Command($commandName))->setCode(function (InputInterface $input, OutputInterface $output) use ($commandName, $exitCode): int {
                $output->writeln('hello from ' . $commandName);

                return $exitCode;
            })
        );

        return $application;
    }
}
