<?php

namespace Quantum\Tests\Unit\Http;

use Quantum\Session\Factories\SessionFactory;
use Quantum\Http\Enums\ContentType;
use Quantum\Tests\Unit\AppTestCase;
use Throwable;

class ResponseTest extends AppTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        request()->create('GET', 'http://example.com');
    }

    public function tearDown(): void
    {
        response()->flush();

        server()->flush();
    }

    public function testResponseContentType(): void
    {
        $response = response();

        $this->assertEquals(ContentType::HTML, $response->getContentType());

        $response->setContentType(ContentType::JSON);

        $this->assertEquals(ContentType::JSON, $response->getContentType());
    }

    public function testResponseRedirect(): void
    {
        $response = response();

        $this->assertFalse($response->hasHeader('Location'));

        try {
            $response->redirect('/');
        } catch (Throwable $e) {

        }

        $this->assertTrue($response->hasHeader('Location'));

        $this->assertEquals('/', $response->getHeader('Location'));

        $this->assertEquals(302, $response->getStatusCode());

        try {
            $response->redirect('/home', 301);
        } catch (Throwable $e) {

        }

        $this->assertEquals('/home', $response->getHeader('Location'));

        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testResponseBackRedirectsToReferrer(): void
    {
        $referrer = 'https://example.com/page';

        server()->set('HTTP_REFERER', $referrer);

        $response = response()->back();

        $this->assertEquals($referrer, $response->getHeader('Location'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testResponseBackFallsBackToBaseUrl(): void
    {
        $response = response()->back();

        $this->assertEquals(base_url(), $response->getHeader('Location'));

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testResponseBackFallsBackToBaseUrlWithEmptyReferrer(): void
    {
        server()->set('HTTP_REFERER', '');

        $response = response()->back();

        $this->assertEquals(base_url(), $response->getHeader('Location'));
    }

    public function testResponseBackWithStatusCode(): void
    {
        $response = response()->back([], 301);

        $this->assertEquals(base_url(), $response->getHeader('Location'));

        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testResponseBackWithOldData(): void
    {
        $session = SessionFactory::get();

        $referrer = 'https://example.com/signup';

        server()->set('HTTP_REFERER', $referrer);

        $response = response()->back(['firstname' => 'Josh', 'lastname' => 'Doe']);

        $this->assertEquals($referrer, $response->getHeader('Location'));

        $this->assertEquals('Josh', old('firstname'));

        $this->assertEquals('Doe', old('lastname'));

        $session->flush();
    }

    public function testResponseBackWithoutDataKeepsOldData(): void
    {
        $session = SessionFactory::get();

        response()->back(['firstname' => 'Josh']);

        response()->back();

        $this->assertEquals('Josh', old('firstname'));

        $session->flush();
    }

}
