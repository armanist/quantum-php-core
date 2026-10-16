<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Http\Traits\Request;

use Quantum\Http\Enums\ContentType;

/**
 * Trait Header
 * @package Quantum\Http
 */
trait Header
{
    /**
     * Request headers
     * @var array<string, mixed>
     */
    private array $__headers = [];

    /**
     * Checks the request header existence by given key
     */
    public function hasHeader(string $key): bool
    {
        [$keyWithHyphens, $keyWithUnderscores] = $this->normalizeHeaderKey($key);

        return isset($this->__headers[$keyWithHyphens]) || isset($this->__headers[$keyWithUnderscores]);
    }

    /**
     * Gets the request header by given key
     */
    public function getHeader(string $key): ?string
    {
        if ($this->hasHeader($key)) {
            [$keyWithHyphens, $keyWithUnderscores] = $this->normalizeHeaderKey($key);
            return $this->__headers[$keyWithHyphens] ?? $this->__headers[$keyWithUnderscores];
        }

        return null;
    }

    /**
     * Sets the request header
     * @param mixed $value
     */
    public function setHeader(string $key, $value): void
    {
        $this->__headers[strtolower($key)] = $value;
    }

    /**
     * Gets all request headers
     * @return array<string, mixed>
     */
    public function allHeaders(): array
    {
        return $this->__headers;
    }

    /**
     * Deletes the header by given key
     */
    public function deleteHeader(string $key): void
    {
        if ($this->hasHeader($key)) {
            unset($this->__headers[strtolower($key)]);
        }
    }

    /**
     * Gets Authorization Bearer token
     */
    public function getAuthorizationBearer(): ?string
    {
        $bearerToken = null;

        $authorization = (string) $this->getHeader('Authorization');

        if ($this->hasHeader('Authorization') && preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
            $bearerToken = $matches[1];
        }

        return $bearerToken;
    }

    /**
     * Gets Basic Auth Credentials
     * @return array<string, string>|null
     */
    public function getBasicAuthCredentials(): ?array
    {
        if ($this->server->has('PHP_AUTH_USER') && $this->server->has('PHP_AUTH_PW')) {
            return [
                'username' => $this->server->get('PHP_AUTH_USER'),
                'password' => $this->server->get('PHP_AUTH_PW'),
            ];
        }

        if (!$this->hasHeader('Authorization')) {
            return null;
        }

        $authorization = (string) $this->getHeader('Authorization');

        if (preg_match('/Basic\s(\S+)/', $authorization, $matches)) {
            $decoded = base64_decode($matches[1], true);

            if ($decoded && str_contains($decoded, ':')) {
                [$username, $password] = explode(':', $decoded, 2);
                return ['username' => $username, 'password' => $password];
            }
        }

        return null;
    }

    /**
     * Checks to see if request was AJAX request
     */
    public function isAjax(): bool
    {
        return $this->hasHeader('X-REQUESTED-WITH') || $this->server->ajax();
    }

    /**
     * Checks whether the client expects JSON: the Accept header lists a JSON media type
     * (application/json or a +json type), or it is an AJAX request that accepts anything and does not ask for HTML
     */
    public function expectsJson(): bool
    {
        $mediaTypes = $this->getAcceptedMediaTypes();

        foreach ($mediaTypes as $mediaType) {
            if ($mediaType === ContentType::JSON || str_ends_with($mediaType, '+json')) {
                return true;
            }
        }

        return $this->isAjax()
            && in_array($mediaTypes[0], ['', '*/*', '*'], true)
            && !in_array(ContentType::HTML, $mediaTypes, true);
    }

    /**
     * Gets the referrer
     */
    public function getReferrer(): ?string
    {
        return $this->server->referrer();
    }

    /**
     * Gets the media types listed in the Accept header, without parameters, trimmed and lowercased
     * @return array<string>
     */
    private function getAcceptedMediaTypes(): array
    {
        return array_map(
            fn (string $mediaType): string => strtolower(trim(explode(';', $mediaType)[0])),
            explode(',', (string) $this->getHeader('Accept'))
        );
    }

    /**
     * @return array<string>
     */
    private function normalizeHeaderKey(string $key): array
    {
        $keyWithHyphens = str_replace('_', '-', strtolower($key));
        $keyWithUnderscores = str_replace('-', '_', $key);

        return [$keyWithHyphens, $keyWithUnderscores];
    }
}
