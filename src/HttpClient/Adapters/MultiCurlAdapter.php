<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\HttpClient\Adapters;

use Quantum\HttpClient\Contracts\MultiCurlAdapterInterface;
use CurlMultiHandle;
use CurlHandle;

/**
 * Class MultiCurlAdapter
 * @package Quantum\HttpClient
 */
class MultiCurlAdapter implements MultiCurlAdapterInterface
{
    private CurlMultiHandle $handle;

    /**
     * @var array<int|string, CurlAdapter>
     */
    private array $queue = [];

    /**
     * @var array<int|string, mixed>
     */
    private array $headers = [];

    /**
     * @var array<int, mixed>
     */
    private array $options = [];

    /**
     * @var callable|null
     */
    private $completeCallback;

    /**
     * @var callable|null
     */
    private $successCallback;

    /**
     * @var callable|null
     */
    private $errorCallback;

    public function __construct()
    {
        $this->handle = curl_multi_init();
    }

    public function __destruct()
    {
        curl_multi_close($this->handle);
    }

    public function complete(callable $callback): MultiCurlAdapterInterface
    {
        $this->completeCallback = $callback;

        return $this;
    }

    public function success(callable $callback): MultiCurlAdapterInterface
    {
        $this->successCallback = $callback;

        return $this;
    }

    public function error(callable $callback): MultiCurlAdapterInterface
    {
        $this->errorCallback = $callback;

        return $this;
    }

    public function start(): void
    {
        $this->startNativeRequests();
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function addGet(string $url, array $data = [])
    {
        $adapter = $this->queueRequest($this->buildUrl($url, $data));
        $adapter->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $adapter->setOpt(CURLOPT_HTTPGET, true);

        return $adapter;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function addPost(string $url, $data = '', bool $follow_303_with_post = false)
    {
        $adapter = $this->queueRequest($url);

        if ($follow_303_with_post) {
            $adapter->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        }

        $adapter->setOpt(CURLOPT_POST, true);
        $adapter->setOpt(CURLOPT_POSTFIELDS, $adapter->buildPostData($data));

        return $adapter;
    }

    /**
     * @param mixed $value
     */
    public function setHeader(string $key, $value): MultiCurlAdapterInterface
    {
        $this->applyHeader($key, $value);

        return $this;
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    public function setHeaders(array $headers): MultiCurlAdapterInterface
    {
        foreach ($headers as $key => $value) {
            $this->applyHeader(trim((string) $key), trim((string) $value));
        }

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setOpt(int $option, $value): MultiCurlAdapterInterface
    {
        $this->applyOption($option, $value);

        return $this;
    }

    /**
     * @param array<int, mixed> $options
     */
    public function setOpts(array $options): MultiCurlAdapterInterface
    {
        foreach ($options as $option => $value) {
            $this->applyOption($option, $value);
        }

        return $this;
    }

    public function supportsMethod(string $method): bool
    {
        return in_array($method, ['addGet', 'addPost', 'setHeader', 'setHeaders', 'setOpt', 'setOpts'], true);
    }

    /**
     * @return array<int|string, CurlAdapter>
     */
    public function getQueuedRequests(): array
    {
        return $this->queue;
    }

    /**
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function callMethod(string $method, array $arguments)
    {
        if (in_array($method, ['addGet', 'addPost', 'setHeader', 'setHeaders', 'setOpt', 'setOpts'], true)) {
            return $this->$method(...$arguments);
        }

        return null;
    }

    private function queueRequest(string $url): CurlAdapter
    {
        $adapter = new CurlAdapter();
        $adapter->setUrl($url);
        $adapter->setHeaders($this->headers);
        $adapter->setOpts($this->options);

        $this->queue[$adapter->getId()] = $adapter;

        return $adapter;
    }

    /**
     * @param mixed $value
     */
    private function applyHeader(string $key, $value): void
    {
        $this->headers[$key] = $value;

        foreach ($this->queue as $adapter) {
            $adapter->setHeader($key, $value);
        }
    }

    /**
     * @param mixed $value
     */
    private function applyOption(int $option, $value): void
    {
        $this->options[$option] = $value;

        foreach ($this->queue as $adapter) {
            $adapter->setOpt($option, $value);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildUrl(string $url, array $data): string
    {
        if ($data === []) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
    }

    private function startNativeRequests(): void
    {
        foreach ($this->queue as $adapter) {
            curl_multi_add_handle($this->handle, $adapter->getHandle());
        }

        $running = 0;

        do {
            do {
                $status = curl_multi_exec($this->handle, $running);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            while ($info = curl_multi_info_read($this->handle)) {
                $this->completeNativeRequest($info['handle']);
            }

            if ($running > 0 && curl_multi_select($this->handle) === -1) {
                usleep(1000);
            }
        } while ($running > 0);
    }

    private function completeNativeRequest(CurlHandle $handle): void
    {
        foreach ($this->queue as $id => $adapter) {
            if ($adapter->getHandle() !== $handle) {
                continue;
            }

            $adapter->finalizeResponse(curl_multi_getcontent($handle));

            if ($this->completeCallback !== null) {
                ($this->completeCallback)($adapter);
            }

            if ($adapter->isError()) {
                if ($this->errorCallback !== null) {
                    ($this->errorCallback)($adapter);
                }
            } elseif ($this->successCallback !== null) {
                ($this->successCallback)($adapter);
            }

            curl_multi_remove_handle($this->handle, $handle);
            unset($this->queue[$id]);

            return;
        }
    }
}
