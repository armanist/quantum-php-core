<?php

namespace Quantum\Tests\Unit\HttpClient\Adapters;

use Quantum\HttpClient\Adapters\MultiCurlAdapter;
use Quantum\HttpClient\Adapters\CurlAdapter;
use Quantum\Tests\Unit\AppTestCase;
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

    public function testMultiCurlAdapterRemovesCompletedRequestsFromQueue(): void
    {
        $adapter = new MultiCurlAdapter();
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';
        $completeRequests = [];

        $adapter->complete(function (CurlAdapter $instance) use (&$completeRequests): void {
            $completeRequests[] = $instance->getId();
        });

        $firstRequest = $adapter->addGet($this->fileUrl($fixturePath));
        $adapter->start();

        $secondRequest = $adapter->addGet($this->fileUrl($fixturePath));
        $adapter->start();

        $this->assertSame([$firstRequest->getId(), $secondRequest->getId()], $completeRequests);
        $this->assertSame([], $adapter->getQueuedRequests());
    }

    public function testMultiCurlAdapterCleansQueueWhenCallbackThrows(): void
    {
        $adapter = new MultiCurlAdapter();
        $fixturePath = PROJECT_ROOT . DS . 'app.conf';
        $completeRequests = [];

        $adapter
            ->complete(function (): void {
                throw new \RuntimeException('Callback failed');
            })
            ->addGet($this->fileUrl($fixturePath));

        try {
            $adapter->start();
            $this->fail('Expected callback exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Callback failed', $e->getMessage());
        }

        $this->assertSame([], $adapter->getQueuedRequests());

        $adapter
            ->complete(function (CurlAdapter $instance) use (&$completeRequests): void {
                $completeRequests[] = $instance->getId();
            })
            ->addGet($this->fileUrl($fixturePath));

        $adapter->start();

        $this->assertCount(1, $completeRequests);
        $this->assertSame([], $adapter->getQueuedRequests());
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

    public function testMultiCurlAdapterSupportsDocumentedMethods(): void
    {
        $adapter = new MultiCurlAdapter();

        $this->assertTrue($adapter->supportsMethod('addGet'));
        $this->assertFalse($adapter->supportsMethod('missingMethod'));
        $this->assertInstanceOf(CurlAdapter::class, $adapter->callMethod('addGet', ['https://example.com', []]));
        $this->assertNull($adapter->callMethod('missingMethod', []));
    }

    private function fileUrl(string $path): string
    {
        return 'file:///' . str_replace('\\', '/', $path);
    }
}
