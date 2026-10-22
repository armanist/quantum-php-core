<?php

namespace Quantum\Tests\Unit\Mailer\Adapters;

use Quantum\Mailer\Adapters\SendinblueAdapter;
use Quantum\Mailer\Contracts\MailerInterface;
use Quantum\Mailer\Adapters\SmtpAdapter;
use Quantum\Mailer\Traits\MailerTrait;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Config\Setup;

class MailerAdapterTraitTest extends AppTestCase
{
    private SmtpAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        if (!config()->has('mailer')) {
            config()->import(new Setup('config', 'mailer'));
        }

        $this->adapter = new SmtpAdapter(config()->get('mailer.smtp'));
    }

    public function testMailerTraitSetGetFrom(): void
    {
        $this->adapter->setFrom('johndoe@gmail.com', 'John Doe');

        $from = $this->adapter->getFrom();

        $this->assertIsArray($from);

        $this->assertEquals('johndoe@gmail.com', $from['email']);

        $this->assertEquals('John Doe', $from['name']);
    }

    public function testMailerTraitSetGetAddresses(): void
    {
        $this->adapter->setAddress('johndoe@gmail.com', 'John Doe');

        $addresses = $this->adapter->getAddresses();

        $this->assertIsArray($addresses);

        $this->assertIsArray($addresses[0]);

        $this->assertEquals('johndoe@gmail.com', $addresses[0]['email']);

        $this->assertEquals('John Doe', $addresses[0]['name']);
    }

    public function testMailerTraitSetGetSubject(): void
    {
        $this->adapter->setSubject('Lorem ipsum');

        $this->assertIsString($this->adapter->getSubject());

        $this->assertEquals('Lorem ipsum', $this->adapter->getSubject());
    }

    public function testMailerTraitSetGetTemplate(): void
    {

        $templatePath = base_dir() . DS . 'shared' . DS . 'views' . DS . 'email' . DS . 'template';

        $this->adapter->setTemplate($templatePath);

        $this->assertIsString($this->adapter->getTemplate());

        $this->assertEquals($templatePath, $this->adapter->getTemplate());
    }

    public function testSetGetBody(): void
    {
        $this->adapter->setBody('Lorem ipsum dolor sit amet');

        $this->assertIsString($this->adapter->getBody());

        $this->assertEquals('Lorem ipsum dolor sit amet', $this->adapter->getBody());

        $this->adapter->setBody(['Lorem ipsum', 'dolor sit amet']);

        $this->assertIsArray($this->adapter->getBody());

        $this->assertEquals('Lorem ipsum', $this->adapter->getBody()[0]);
    }

    public function testMailerTraitGetMessageId(): void
    {
        $adapter = new SendinblueAdapter(['api_key' => 'xxx11122233']);

        $this->assertIsString($adapter->getMessageId());
    }

    public function testMailerTraitGetTransportLogIsEmptyBeforeASend(): void
    {
        $this->assertSame([], $this->adapter->getTransportLog());
    }

    public function testMailerTraitSendKeepsTheTransportErrorsOfAFailedSendAndLogsThem(): void
    {
        config()->set('app.debug', true);
        config()->set('mailer.mail_trap', false);

        $adapter = new class () implements MailerInterface {
            use MailerTrait;

            protected function prepare(): void
            {
            }

            protected function sendEmail(): bool
            {
                return false;
            }

            protected function getTransportErrors(): array
            {
                return ['Connection refused'];
            }
        };

        $this->assertFalse($adapter->send());

        $this->assertSame(['Connection refused'], $adapter->getTransportLog());
        $this->assertSame([['warning' => 'Connection refused']], logger()->getAdapter()->getMessages());
    }
}
