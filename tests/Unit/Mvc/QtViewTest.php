<?php

namespace Quantum\Tests\Unit\Mvc;

use Quantum\Renderer\Factories\RendererFactory;
use Quantum\Exceptions\ViewException;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Factory\ViewFactory;
use Quantum\Router\Router;


class QtViewTest extends AppTestCase
{

    private $view;

    public function setUp(): void
    {
        parent::setUp();

        Router::setCurrentRoute([
            "route" => "test",
            "method" => "GET",
            "controller" => "SomeController",
            "action" => "test",
            "module" => "Test"
        ]);

        $this->view = ViewFactory::getInstance();
    }

    public function tearDown(): void
    {
        $this->view->setLayout(null);

        $this->view->flushParams();
    }

    public function testSetGetLayout()
    {
        $this->assertNull($this->view->getLayout());

        $this->view->setLayout('layout');

        $this->assertNotNull($this->view->getLayout());

        $this->assertIsString($this->view->getLayout());
    }

    public function testSetGetParams()
    {
        $this->assertEmpty($this->view->getParams());

        $this->assertNull($this->view->getParam('firstname'));

        $this->view->setParam('firstname', 'John');

        $this->assertEquals('John', $this->view->getParam('firstname'));

        $this->view->setParams(['lastname' => 'Doe', 'age' => 35]);

        $this->assertEquals('Doe', $this->view->getParam('lastname'));

        $this->assertEquals(35, $this->view->getParam('age'));

        $this->assertNotEmpty($this->view->getParams());
    }

    public function testRenderWithoutLayout()
    {
        $this->expectException(ViewException::class);

        $this->expectExceptionMessage('layout_not_set');

        $this->view->render('index');
    }

    public function testRenderWithLayout()
    {
        $this->view->setLayout('layout');

        $renderedView = $this->view->render('index');

        $this->assertIsString($renderedView);

        $this->assertEquals('<html>' . PHP_EOL . '<head></head>' . PHP_EOL . '<body>' . PHP_EOL . '<p>Hello World, this is rendered html view</p></body>' . PHP_EOL . '</html>' . PHP_EOL, $renderedView);
    }

    public function testRenderWithData()
    {
        $this->view->setLayout('layout');

        $this->view->render('index', ['name' => 'Lorem Ipsum']);

        $this->assertEquals('<p>Hello Lorem Ipsum, this is rendered html view</p>', $this->view->getView());

        $this->view->setParam('name', 'dolor sit amet');

        $this->view->render('index');

        $this->assertEquals('<p>Hello dolor sit amet, this is rendered html view</p>', $this->view->getView());
    }

    public function testRenderPartial()
    {
        $this->assertIsString($this->view->renderPartial('partial'));

        $this->assertEquals('<p>Hello World, this is rendered partial html view</p>', $this->view->renderPartial('partial'));

        $this->assertEquals('<p>Hello Tester, this is rendered partial html view</p>', $this->view->renderPartial('partial', ['name' => 'Tester']));
    }

    public function testRenderViewWithTwig(): void
    {
        $this->setPrivateProperty(RendererFactory::class, 'instances', []);

        config()->set('view.default', 'twig');
        config()->set('view.twig', ['autoescape' => false]);

        $this->view->setLayout('layout.twig');

        $renderedView = $this->view->render('index.twig', ['name' => 'Tester']);

        $this->assertIsString($renderedView);

        $renderedView = str_replace("\n", PHP_EOL, $renderedView);

        $this->assertEquals('<html>' . PHP_EOL . '<head></head>' . PHP_EOL . '<body>' . PHP_EOL . '<p>Hello Tester, this is rendered twig view</p>' . PHP_EOL . '</body>' . PHP_EOL . '</html>' . PHP_EOL, $renderedView);
    }
}