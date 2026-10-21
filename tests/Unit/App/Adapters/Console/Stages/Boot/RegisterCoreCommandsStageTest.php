<?php

namespace Quantum\Tests\Unit\App\Adapters\Console\Stages\Boot;

use Quantum\Tests\Unit\App\Adapters\Console\Stages\ConsoleStageTestCase;
use Quantum\App\Adapters\Console\Stages\Boot\RegisterCoreCommandsStage;

class RegisterCoreCommandsStageTest extends ConsoleStageTestCase
{
    public function testRegisterCoreCommandsStageAddsCommandsFromFrameworkDirectory(): void
    {
        $directory = framework_dir() . DS . 'Console' . DS . 'Commands';

        $this->withTemporaryCommand($directory, 'Quantum\Console\Commands', function (string $commandName): void {
            $context = $this->createConsoleContext();

            $this->assertFalse($context->getApplication()->has($commandName));

            (new RegisterCoreCommandsStage())->process($context);

            $this->assertTrue($context->getApplication()->has($commandName));
        });
    }

    public function testRegisterCoreCommandsStageIgnoresMissingDirectory(): void
    {
        $this->assertDirectoryDoesNotExist(framework_dir() . DS . 'Console' . DS . 'Commands');

        $context = $this->createConsoleContext();
        $commandCount = count($context->getApplication()->all());

        (new RegisterCoreCommandsStage())->process($context);

        $this->assertCount($commandCount, $context->getApplication()->all());
    }
}
