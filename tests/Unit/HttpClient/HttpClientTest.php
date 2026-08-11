<?php

namespace Quantum\Tests\Unit\HttpClient;

use Quantum\HttpClient\Exceptions\HttpClientException;
use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\HttpClient\HttpClient;
use Quantum\Tests\Unit\AppTestCase;
use Mockery;

class HttpClientTest extends AppTestCase
{
    private HttpClient $httpClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->httpClient = new HttpClient();
    }

    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testHttpClientGetSetMethod(): void
    {
        $this->assertNull($this->httpClient->getAdapter());

        $this->assertEquals('GET', $this->httpClient->getMethod());

        $this->httpClient->setMethod('POST');

        $this->assertEquals('POST', $this->httpClient->getMethod());

        $this->expectException(HttpClientException::class);

        $this->httpClient->setMethod('NOPE');
    }

    public function testHttpClientGetSetData(): void
    {
        $this->assertNull($this->httpClient->getData());

        $data = ['a' => 1];

        $this->httpClient->setData($data);

        $this->assertSame($data, $this->httpClient->getData());
    }

    public function testHttpClientIsMultiRequest(): void
    {
        $this->httpClient->createRequest('https://example.com');

        $this->assertFalse($this->httpClient->isMultiRequest());

        $this->assertInstanceOf(CurlAdapter::class, $this->httpClient->getAdapter());

        $this->httpClient->createMultiRequest();

        $this->assertTrue($this->httpClient->isMultiRequest());

        $this->assertInstanceOf(MultiCurlAdapter::class, $this->httpClient->getAdapter());
    }

    public function testHttpClientRequestNotCreated(): void
    {
        $this->expectException(HttpClientException::class);

        $this->httpClient->start();
    }

    public function testHttpClientReturnsEmptyResponseAndErrorsBeforeRequestCreated(): void
    {
        $this->assertSame([], $this->httpClient->getResponse());

        $this->assertSame([], $this->httpClient->getErrors());
    }

    public function testHttpClientEnsureSingleRequestThrowsOnMulti(): void
    {
        $this->httpClient->createMultiRequest();

        $this->expectException(HttpClientException::class);

        $this->httpClient->getRequestHeaders();
    }

    public function testHttpClientSingleRequestResponseFlow(): void
    {
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';

        $this->httpClient
            ->createRequest($this->fileUrl($fixturePath))
            ->start();

        $this->assertSame([], $this->httpClient->getResponseHeaders());
        $this->assertSame([], $this->httpClient->getResponseCookies());
        $this->assertSame(file_get_contents($fixturePath), $this->httpClient->getResponseBody());
    }

    public function testHttpClientNativeSingleRequestResponseFlow(): void
    {
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';

        $this->httpClient
            ->createRequest($this->fileUrl($fixturePath))
            ->start();

        $this->assertSame([], $this->httpClient->getErrors());
        $this->assertSame(file_get_contents($fixturePath), $this->httpClient->getResponseBody());
        $this->assertSame(file_get_contents($fixturePath), $this->httpClient->getResponse()['body']);
    }

    public function testHttpClientPostRequestWithData(): void
    {
        $this->httpClient
            ->createRequest($this->fileUrl(PROJECT_ROOT . DS . 'app.conf'))
            ->setMethod('POST')
            ->setData(['x' => 1]);

        $this->assertSame('POST', $this->httpClient->getMethod());
        $this->assertSame(['x' => 1], $this->httpClient->getData());
    }

    public function testHttpClientSingleRequestError(): void
    {
        $this->httpClient
            ->createRequest($this->fileUrl(PROJECT_ROOT . DS . 'missing.conf'))
            ->start();

        $errors = $this->httpClient->getErrors();

        $this->assertNotSame(0, $errors['code']);
        $this->assertNotEmpty($errors['message']);
    }

    public function testHttpClientMultiRequestResponseStructure(): void
    {
        $this->httpClient
            ->createMultiRequest()
            ->addGet($this->fileUrl(PROJECT_ROOT . DS . 'app.conf'))
            ->start();

        $response = $this->httpClient->getResponse();
        $id = array_key_first($response);

        $this->assertArrayHasKey('headers', $response[$id]);
        $this->assertArrayHasKey('cookies', $response[$id]);
        $this->assertArrayHasKey('body', $response[$id]);
    }

    public function testHttpClientNativeMultiRequestResponseFlow(): void
    {
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';

        $this->httpClient
            ->createMultiRequest()
            ->addGet($this->fileUrl($fixturePath))
            ->addGet($this->fileUrl($fixturePath))
            ->start();

        $response = $this->httpClient->getResponse();

        $this->assertCount(2, $response);
        $this->assertSame([], $this->httpClient->getErrors());

        foreach ($response as $item) {
            $this->assertSame([], $item['headers']);
            $this->assertSame([], $item['cookies']);
            $this->assertSame(file_get_contents($fixturePath), $item['body']);
        }
    }

    public function testHttpClientMultiRequestAggregatesErrors(): void
    {
        $this->httpClient
            ->createMultiRequest()
            ->addGet($this->fileUrl(PROJECT_ROOT . DS . 'missing-one.conf'))
            ->addGet($this->fileUrl(PROJECT_ROOT . DS . 'missing-two.conf'))
            ->start();

        $errors = $this->httpClient->getErrors();

        $this->assertCount(2, $errors);
        foreach ($errors as $error) {
            $this->assertNotSame(0, $error['code']);
            $this->assertNotEmpty($error['message']);
        }
    }

    public function testHttpClientCreateAsyncMultiRequestRegistersCallbacks(): void
    {
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';
        $successWrapped = null;
        $errorWrapped = null;
        $success = function (CurlAdapter $instance) use (&$successWrapped): void {
            $successWrapped = $instance;
        };
        $error = function (CurlAdapter $instance) use (&$errorWrapped): void {
            $errorWrapped = $instance;
        };

        $this->httpClient
            ->createAsyncMultiRequest($success, $error)
            ->addGet($this->fileUrl($fixturePath))
            ->start();

        $this->assertTrue($this->httpClient->isMultiRequest());

        $this->assertInstanceOf(MultiCurlAdapter::class, $this->httpClient->getAdapter());

        $this->assertInstanceOf(CurlAdapter::class, $successWrapped);

        $this->assertNull($errorWrapped);
        $this->assertSame(file_get_contents($fixturePath), $successWrapped->getResponse());
    }

    public function testHttpClientInfoAndUrl(): void
    {
        $this->httpClient
            ->createRequest($this->fileUrl(PROJECT_ROOT . DS . 'app.conf'))
            ->start();

        $this->assertIsArray($this->httpClient->info());
        $this->assertSame($this->fileUrl(PROJECT_ROOT . DS . 'app.conf'), $this->httpClient->url());
    }

    public function testHttpClientPassesZeroInfoOption(): void
    {
        $this->httpClient->createRequest($this->fileUrl(PROJECT_ROOT . DS . 'app.conf'));

        $this->assertFalse($this->httpClient->info(0));
    }

    private function fileUrl(string $path): string
    {
        return 'file:///' . str_replace('\\', '/', $path);
    }
}
