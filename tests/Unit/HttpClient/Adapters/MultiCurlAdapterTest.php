<?php

namespace Quantum\Tests\Unit\HttpClient\Adapters;

use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\Tests\Unit\AppTestCase;
use Curl\MultiCurl;
use Curl\Curl;
use Mockery;

class MultiCurlAdapterTest extends AppTestCase
{
    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testMultiCurlAdapterQueuesNativeRequests(): void
    {
        $adapter = new MultiCurlAdapter();

        $getRequest = $adapter->addGet('https://example.com?existing=yes', ['a' => 1]);
        $postRequest = $adapter->addPost('https://example.org', 'payload', true);

        $this->assertInstanceOf(CurlAdapter::class, $getRequest);
        $this->assertInstanceOf(CurlAdapter::class, $postRequest);
        $this->assertNotSame($getRequest->getId(), $postRequest->getId());
        $this->assertSame('https://example.com?existing=yes&a=1', $getRequest->getUrl());
        $this->assertSame('https://example.org', $postRequest->getUrl());
        $this->assertSame([
            $getRequest->getId() => $getRequest,
            $postRequest->getId() => $postRequest,
        ], $adapter->getQueuedRequests());
    }

    public function testMultiCurlAdapterExecutesNativeRequestsAndDispatchesCallbacks(): void
    {
        $adapter = new MultiCurlAdapter();
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';
        $completeRequests = [];
        $successRequests = [];
        $errorRequests = [];

        $firstRequest = $adapter->addGet($this->fileUrl($fixturePath));
        $secondRequest = $adapter->addGet($this->fileUrl($fixturePath));

        $adapter
            ->complete(function (CurlAdapter $instance) use (&$completeRequests): void {
                $completeRequests[$instance->getId()] = $instance;
            })
            ->success(function (CurlAdapter $instance) use (&$successRequests): void {
                $successRequests[$instance->getId()] = $instance;
            })
            ->error(function (CurlAdapter $instance) use (&$errorRequests): void {
                $errorRequests[$instance->getId()] = $instance;
            })
            ->start();

        $this->assertSame(file_get_contents($fixturePath), $firstRequest->getResponse());
        $this->assertSame(file_get_contents($fixturePath), $secondRequest->getResponse());
        $this->assertFalse($firstRequest->isError());
        $this->assertFalse($secondRequest->isError());
        $this->assertSame([
            $firstRequest->getId() => $firstRequest,
            $secondRequest->getId() => $secondRequest,
        ], $completeRequests);
        $this->assertSame($completeRequests, $successRequests);
        $this->assertSame([], $errorRequests);
    }

    public function testMultiCurlAdapterAppliesNativeHeadersAndOptionsToFutureQueuedRequests(): void
    {
        $adapter = new MultiCurlAdapter();

        $adapter
            ->setHeader('Accept', 'application/json')
            ->setHeaders(['X-Test' => 'yes'])
            ->setOpt(CURLOPT_TIMEOUT, 10)
            ->setOpts([CURLOPT_CONNECTTIMEOUT => 5]);

        $request = $adapter->addGet('https://example.com');

        $this->assertSame([
            'Accept' => 'application/json',
            'X-Test' => 'yes',
        ], $this->getPrivateProperty($request, 'headers'));
        $this->assertSame([
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ], $this->getPrivateProperty($adapter, 'options'));
    }

    public function testMultiCurlAdapterAppliesNativeHeadersAndOptionsToExistingQueuedRequests(): void
    {
        $adapter = new MultiCurlAdapter();
        $request = $adapter->addGet('https://example.com');

        $adapter
            ->setHeader('Accept', 'application/json')
            ->setHeaders(['X-Test' => 'yes'])
            ->setOpt(CURLOPT_TIMEOUT, 10)
            ->setOpts([CURLOPT_CONNECTTIMEOUT => 5]);

        $this->assertSame([
            'Accept' => 'application/json',
            'X-Test' => 'yes',
        ], $this->getPrivateProperty($request, 'headers'));
        $this->assertSame([
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ], $this->getPrivateProperty($adapter, 'options'));
    }

    public function testMultiCurlAdapterDelegatesRequestMethods(): void
    {
        $getCurl = Mockery::mock(Curl::class);
        $postCurl = Mockery::mock(Curl::class);

        $multiCurl = Mockery::mock(MultiCurl::class);
        $multiCurl->shouldReceive('setHeader')->once()->with('Accept', 'application/json');
        $multiCurl->shouldReceive('setHeaders')->once()->with(['X-Test' => 'yes']);
        $multiCurl->shouldReceive('setOpt')->once()->with(CURLOPT_TIMEOUT, 10);
        $multiCurl->shouldReceive('setOpts')->once()->with([CURLOPT_CONNECTTIMEOUT => 5]);
        $multiCurl->shouldReceive('addGet')->once()->with('https://example.com', ['a' => 1])->andReturn($getCurl);
        $multiCurl->shouldReceive('addPost')->once()->with('https://example.com', 'payload', true)->andReturn($postCurl);
        $multiCurl->shouldReceive('start')->once()->andReturnNull();

        $adapter = new MultiCurlAdapter($multiCurl);

        $this->assertSame($adapter, $adapter->setHeader('Accept', 'application/json'));
        $this->assertSame($adapter, $adapter->setHeaders(['X-Test' => 'yes']));
        $this->assertSame($adapter, $adapter->setOpt(CURLOPT_TIMEOUT, 10));
        $this->assertSame($adapter, $adapter->setOpts([CURLOPT_CONNECTTIMEOUT => 5]));
        $this->assertInstanceOf(CurlAdapter::class, $adapter->addGet('https://example.com', ['a' => 1]));
        $this->assertInstanceOf(CurlAdapter::class, $adapter->addPost('https://example.com', 'payload', true));

        $adapter->start();
    }

    public function testMultiCurlAdapterRegistersCallbacks(): void
    {
        $curl = Mockery::mock(Curl::class);

        $multiCurl = Mockery::mock(MultiCurl::class);
        $multiCurl->shouldReceive('success')
            ->once()
            ->andReturnUsing(function (callable $callback) use ($curl): void {
                $callback($curl);
            });
        $multiCurl->shouldReceive('error')
            ->once()
            ->andReturnUsing(function (callable $callback) use ($curl): void {
                $callback($curl);
            });

        $adapter = new MultiCurlAdapter($multiCurl);
        $successWrapped = null;
        $errorWrapped = null;

        $this->assertSame($adapter, $adapter->success(function (CurlAdapter $instance) use (&$successWrapped): void {
            $successWrapped = $instance;
        }));
        $this->assertSame($adapter, $adapter->error(function (CurlAdapter $instance) use (&$errorWrapped): void {
            $errorWrapped = $instance;
        }));
        $this->assertInstanceOf(CurlAdapter::class, $successWrapped);
        $this->assertInstanceOf(CurlAdapter::class, $errorWrapped);
    }

    public function testMultiCurlAdapterWrapsCompleteCallbackInstance(): void
    {
        $curl = Mockery::mock(Curl::class);

        $multiCurl = Mockery::mock(MultiCurl::class);
        $multiCurl->shouldReceive('complete')
            ->once()
            ->andReturnUsing(function (callable $callback) use ($curl): void {
                $callback($curl);
            });

        $adapter = new MultiCurlAdapter($multiCurl);
        $wrapped = null;

        $this->assertSame($adapter, $adapter->complete(function (CurlAdapter $instance) use (&$wrapped): void {
            $wrapped = $instance;
        }));

        $this->assertInstanceOf(CurlAdapter::class, $wrapped);
    }

    public function testMultiCurlAdapterSupportsDocumentedMethods(): void
    {
        $multiCurl = Mockery::mock(MultiCurl::class);
        $multiCurl->shouldReceive('addGet')->once()->with('https://example.com', [])->andReturn((object) ['id' => 1]);

        $adapter = new MultiCurlAdapter($multiCurl);

        $this->assertTrue($adapter->supportsMethod('addGet'));
        $this->assertFalse($adapter->supportsMethod('missingMethod'));
        $this->assertEquals((object) ['id' => 1], $adapter->callMethod('addGet', ['https://example.com', []]));
    }

    private function fileUrl(string $path): string
    {
        return 'file:///' . str_replace('\\', '/', $path);
    }
}
