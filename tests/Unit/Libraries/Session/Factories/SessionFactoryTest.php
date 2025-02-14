<?php

namespace Quantum\Tests\Unit\Libraries\Session\Factories;

use Quantum\Libraries\Session\Adapters\Database\DatabaseSessionAdapter;
use Quantum\Libraries\Session\Adapters\Native\NativeSessionAdapter;
use Quantum\Libraries\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Libraries\Session\Exceptions\SessionException;
use Quantum\Libraries\Session\Factories\SessionFactory;
use Quantum\Tests\Unit\Libraries\Session\TestCaseHelper;
use Quantum\Libraries\Database\Database;
use Quantum\Libraries\Session\Session;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Loader\Setup;

class SessionFactoryTest extends AppTestCase
{

    use TestCaseHelper;

    public function setUp(): void
    {
        parent::setUp();

        $this->setPrivateProperty(SessionFactory::class, 'instance', null);

        IdiormDbal::connect(['driver' => 'sqlite', 'database' => ':memory:']);

        $this->_createSessionsTable();

        if (!config()->has('session')) {
            config()->import(new Setup('config', 'session'));
        }
    }

    public function tearDown(): void
    {
        session_write_close();
        Database::getInstance()->getOrm('sessions')->deleteMany();
    }

    public function testSessionFactoryInstance()
    {
        $session = SessionFactory::get();

        $this->assertInstanceOf(Session::class, $session);
    }

    public function testSessionFactoryNativeSessionAdapter()
    {
        $session = SessionFactory::get();

        $this->assertInstanceOf(NativeSessionAdapter::class, $session->getAdapter());
    }

    public function testSessionFactoryDatabaseAdapter()
    {
        config()->set('session.current', 'database');

        $session = SessionFactory::get();

        $this->assertInstanceOf(DatabaseSessionAdapter::class, $session->getAdapter());
    }

    public function testSessionSubsequentRequests()
    {
        config()->set('session.current', 'database');

        $session = SessionFactory::get();

        $this->assertEmpty($session->all());

        $session->set('data', 'Data saved in persistent storage');

        $this->assertNotEmpty($session->all());

        $this->assertIsArray($session->all());

        $this->assertEquals('Data saved in persistent storage', $session->get('data'));

        session_write_close();
        unset($session);

        $this->assertEquals(PHP_SESSION_NONE, session_status());

        @session_start();

        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());

        $session = SessionFactory::get();

        $this->assertNotEmpty($session->all());

        $this->assertIsArray($session->all());

        $this->assertEquals('Data saved in persistent storage', $session->get('data'));
    }

    public function testMailerFactoryInvalidTypeAdapter()
    {
        config()->set('session.current', 'invalid');

        $this->expectException(SessionException::class);

        $this->expectExceptionMessage('The adapter `invalid` is not supported`');

        SessionFactory::get();
    }

    public function testMailerFactoryReturnsSameInstance()
    {
        $session1 = SessionFactory::get();
        $session2 = SessionFactory::get();

        $this->assertSame($session1, $session2);
    }
}