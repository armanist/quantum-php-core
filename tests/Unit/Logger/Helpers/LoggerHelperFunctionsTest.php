<?php

namespace Quantum\Tests\Unit\Logger\Helpers;

use Quantum\Logger\Exceptions\LoggerException;
use Quantum\Logger\Adapters\MessageAdapter;
use Quantum\Logger\Adapters\SingleAdapter;
use Quantum\Logger\Adapters\DailyAdapter;
use Quantum\Logger\Enums\LoggerType;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Logger\Logger;

class LoggerHelperFunctionsTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('app.debug', true);
    }

    public function testLoggerHelperGetDefaultLoggerAdapter(): void
    {
        config()->set('app.debug', false);

        $logger = logger();

        $this->assertInstanceOf(Logger::class, $logger);

        $this->assertInstanceOf(SingleAdapter::class, $logger->getAdapter());
    }

    public function testLoggerHelperGetSingleLoggerAdapter(): void
    {
        config()->set('app.debug', false);

        $logger = logger(LoggerType::SINGLE);

        $this->assertInstanceOf(Logger::class, $logger);

        $this->assertInstanceOf(SingleAdapter::class, $logger->getAdapter());
    }

    public function testLoggerHelperGetDailyLoggerAdapter(): void
    {
        config()->set('app.debug', false);

        $logger = logger(LoggerType::DAILY);

        $this->assertInstanceOf(Logger::class, $logger);

        $this->assertInstanceOf(DailyAdapter::class, $logger->getAdapter());
    }

    public function testLoggerHelperGetMessageLoggerAdapter(): void
    {
        $logger = logger();

        $this->assertInstanceOf(Logger::class, $logger);

        $this->assertInstanceOf(MessageAdapter::class, $logger->getAdapter());
    }

    public function testLoggerHelperTryToGetMessageLoggerAdapterInNonDebugMode(): void
    {
        config()->set('app.debug', false);

        $this->expectException(LoggerException::class);

        $this->expectExceptionMessage('The adapter `message` is not supported');

        logger(LoggerType::MESSAGE);
    }

    public function testErrorHelper(): void
    {
        error('Fatal Error');

        $this->assertSame([['error' => 'Fatal Error']], logger()->getAdapter()->getMessages());
    }

    public function testWarningHelper(): void
    {
        warning('Warning!!');

        $this->assertSame([['warning' => 'Warning!!']], logger()->getAdapter()->getMessages());
    }

    public function testNoticeHelper(): void
    {
        notice('Simple Notice');

        $this->assertSame([['notice' => 'Simple Notice']], logger()->getAdapter()->getMessages());
    }

    public function testInfoHelper(): void
    {
        info('For your information');

        $this->assertSame([['info' => 'For your information']], logger()->getAdapter()->getMessages());
    }

    public function testDebugHelper(): void
    {
        debug('Debugging!!');

        $this->assertSame([['debug' => 'Debugging!!']], logger()->getAdapter()->getMessages());
    }
}
