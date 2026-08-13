<?php

namespace Quantum\Tests\Unit\HttpClient\Factories;

use Quantum\HttpClient\Factories\HttpClientFactory;
use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\HttpClient\HttpClient;

class HttpClientFactoryTest extends AppTestCase
{
    public function testHttpClientFactoryCreatesSingleRequest(): void
    {
        $httpClient1 = HttpClientFactory::createRequest('https://example.com');
        $httpClient2 = HttpClientFactory::createRequest('https://example.org');

        $this->assertInstanceOf(HttpClient::class, $httpClient1);
        $this->assertInstanceOf(CurlAdapter::class, $httpClient1->getAdapter());
        $this->assertNotSame($httpClient1, $httpClient2);
    }

    public function testHttpClientFactoryCreatesMultiRequest(): void
    {
        $httpClient1 = HttpClientFactory::createMultiRequest();
        $httpClient2 = HttpClientFactory::createMultiRequest();

        $this->assertInstanceOf(HttpClient::class, $httpClient1);
        $this->assertTrue($httpClient1->isMultiRequest());
        $this->assertInstanceOf(MultiCurlAdapter::class, $httpClient1->getAdapter());
        $this->assertNotSame($httpClient1, $httpClient2);
    }

    public function testHttpClientFactoryCreatesExecutableNativeMultiRequest(): void
    {
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';

        $httpClient = HttpClientFactory::createMultiRequest()
            ->addGet($this->fileUrl($fixturePath))
            ->start();

        $response = $httpClient->getResponse();

        $this->assertSame(file_get_contents($fixturePath), reset($response)['body']);
    }

    public function testHttpClientFactoryCreatesAsyncMultiRequest(): void
    {
        $success = static function (): void {
        };

        $error = static function (): void {
        };

        $httpClient1 = HttpClientFactory::createAsyncMultiRequest($success, $error);
        $httpClient2 = HttpClientFactory::createAsyncMultiRequest($success, $error);

        $this->assertInstanceOf(HttpClient::class, $httpClient1);
        $this->assertTrue($httpClient1->isMultiRequest());
        $this->assertInstanceOf(MultiCurlAdapter::class, $httpClient1->getAdapter());
        $this->assertNotSame($httpClient1, $httpClient2);
    }

    private function fileUrl(string $path): string
    {
        return 'file:///' . str_replace('\\', '/', $path);
    }
}
