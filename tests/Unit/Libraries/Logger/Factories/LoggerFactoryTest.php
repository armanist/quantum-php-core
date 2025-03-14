<?php

namespace Quantum\Tests\Unit\Libraries\Logger\Factories;

use Quantum\Libraries\Logger\Contracts\ReportableInterface;
use Quantum\Libraries\Logger\Exceptions\LoggerException;
use Quantum\Libraries\Logger\Adapters\MessageAdapter;
use Quantum\Libraries\Logger\Factories\LoggerFactory;
use Quantum\Libraries\Logger\Adapters\SingleAdapter;
use Quantum\Libraries\Logger\Adapters\DailyAdapter;
use Quantum\Libraries\Logger\Logger;
use Quantum\Tests\Unit\AppTestCase;

class LoggerFactoryTest extends AppTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->setPrivateProperty(LoggerFactory::class, 'instance', null);
    }

    public function testLoggerFactoryInstance()
    {
        $logger = LoggerFactory::get();

        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testLoggerFactoryDailyAdapter()
    {
        config()->set('debug', false);

        config()->set('logging.default', 'daily');
        config()->set('logging.daily', ['path' => logs_dir()]);

        $logger = LoggerFactory::get();

        $this->assertInstanceOf(DailyAdapter::class, $logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $logger->getAdapter());
    }

    public function testLoggerFactorySingleAdapter()
    {
        config()->set('debug', false);

        config()->set('logging.default', 'single');
        config()->set('logging.single', ['path' => '1.log']);

        $logger = LoggerFactory::get();

        $this->assertInstanceOf(SingleAdapter::class, $logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $logger->getAdapter());
    }

    public function testLoggerFactoryMessageAdapter()
    {
        $logger = LoggerFactory::get();

        $this->assertInstanceOf(MessageAdapter::class, $logger->getAdapter());

        $this->assertInstanceOf(ReportableInterface::class, $logger->getAdapter());
    }

    public function testLoggerFactoryInvalidTypeAdapter()
    {
        config()->set('debug', false);

        config()->set('logging.default', 'invalid');

        $this->expectException(LoggerException::class);

        $this->expectExceptionMessage('The adapter `invalid` is not supported`');

        LoggerFactory::get();
    }

    public function testLoggerFactoryReturnsSameInstance()
    {
        $logger1 = LoggerFactory::get();
        $logger2 = LoggerFactory::get();

        $this->assertSame($logger1, $logger2);
    }
}