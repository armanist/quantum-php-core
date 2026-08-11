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
use Curl\MultiCurl;
use Curl\Curl;

/**
 * Class MultiCurlAdapter
 * @package Quantum\HttpClient
 */
class MultiCurlAdapter implements MultiCurlAdapterInterface
{
    private ?MultiCurl $client;

    private CurlMultiHandle $handle;

    /**
     * @var array<int|string, CurlAdapter>
     */
    private array $queue = [];

    public function __construct(?MultiCurl $client = null)
    {
        $this->client = $client;
        $this->handle = curl_multi_init();
    }

    public function __destruct()
    {
        curl_multi_close($this->handle);
    }

    public function complete(callable $callback): MultiCurlAdapterInterface
    {
        if ($this->client === null) {
            return $this;
        }

        $this->client->complete(function (Curl $instance) use ($callback): void {
            $callback(new CurlAdapter($instance));
        });

        return $this;
    }

    public function success(callable $callback): MultiCurlAdapterInterface
    {
        if ($this->client === null) {
            return $this;
        }

        $this->client->success(function (Curl $instance) use ($callback): void {
            $callback(new CurlAdapter($instance));
        });

        return $this;
    }

    public function error(callable $callback): MultiCurlAdapterInterface
    {
        if ($this->client === null) {
            return $this;
        }

        $this->client->error(function (Curl $instance) use ($callback): void {
            $callback(new CurlAdapter($instance));
        });

        return $this;
    }

    public function start(): void
    {
        if ($this->client === null) {
            return;
        }

        $this->client->start();
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function addGet(string $url, array $data = [])
    {
        if ($this->client === null) {
            $adapter = new CurlAdapter();
            $adapter->setUrl($url);

            $this->queue[$adapter->getId()] = $adapter;

            return $adapter;
        }

        return $this->wrapCurlResult($this->client->addGet($url, $data));
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    public function addPost(string $url, $data = '', bool $follow_303_with_post = false)
    {
        if ($this->client === null) {
            $adapter = new CurlAdapter();
            $adapter->setUrl($url);

            $this->queue[$adapter->getId()] = $adapter;

            return $adapter;
        }

        return $this->wrapCurlResult($this->client->addPost($url, $data, $follow_303_with_post));
    }

    /**
     * @param mixed $value
     */
    public function setHeader(string $key, $value): MultiCurlAdapterInterface
    {
        if ($this->client === null) {
            return $this;
        }

        $this->client->setHeader($key, $value);

        return $this;
    }

    /**
     * @param array<int|string, mixed> $headers
     */
    public function setHeaders(array $headers): MultiCurlAdapterInterface
    {
        if ($this->client === null) {
            return $this;
        }

        $this->client->setHeaders($headers);

        return $this;
    }

    /**
     * @param mixed $value
     */
    public function setOpt(int $option, $value): MultiCurlAdapterInterface
    {
        if ($this->client === null) {
            return $this;
        }

        $this->client->setOpt($option, $value);

        return $this;
    }

    /**
     * @param array<int, mixed> $options
     */
    public function setOpts(array $options): MultiCurlAdapterInterface
    {
        if ($this->client === null) {
            return $this;
        }

        $this->client->setOpts($options);

        return $this;
    }

    public function supportsMethod(string $method): bool
    {
        return in_array($method, ['addGet', 'addPost', 'setHeader', 'setHeaders', 'setOpt', 'setOpts'], true)
            || ($this->client !== null && method_exists($this->client, $method));
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

        if ($this->client === null) {
            return null;
        }

        return $this->client->$method(...$arguments);
    }

    /**
     * @param mixed $result
     * @return mixed
     */
    private function wrapCurlResult($result)
    {
        return $result instanceof Curl ? new CurlAdapter($result) : $result;
    }
}
