<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Environment;

use Quantum\Environment\Exceptions\EnvException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Di\Exceptions\DiException;
use Quantum\Environment\Enums\Env;
use ReflectionException;
use Quantum\App\App;
use Dotenv\Dotenv;

/**
 * Class Environment
 * @package Quantum\Environment
 * @uses Dotenv
 */
class Environment
{
    private bool $isMutable = false;

    /**
     * Loaded env content
     * @var array<string, mixed>
     */
    private array $envContent = [];

    private bool $loaded = false;

    private string $appEnv = Env::PRODUCTION;

    public function setMutable(bool $isMutable): Environment
    {
        $this->isMutable = $isMutable;
        return $this;
    }

    /**
     * Loads environment variables from file
     * @throws EnvException|DiException|BaseException
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        [$appEnv, $envFile] = $this->resolveEnvironmentFile();
        $envContent = $this->loadDotenvFile($envFile);

        $this->appEnv = $appEnv;
        $this->envContent = $envContent;
        $this->loaded = true;
    }

    /**
     * Checks whether the environment file the app is configured for exists.
     * It does not throw and needs no helpers, so it can be asked before the boot.
     */
    public function fileExists(): bool
    {
        $envConfigFile = $this->envConfigFile();

        if (!file_exists($envConfigFile)) {
            return false;
        }

        return file_exists(App::getBaseDir() . DS . $this->getEnvFileName($this->readAppEnv($envConfigFile)));
    }

    /**
     * Gets the app current environment
     */
    public function getAppEnv(): string
    {
        return $this->appEnv;
    }

    public function isProduction(): bool
    {
        return $this->appEnv === Env::PRODUCTION;
    }

    public function isStaging(): bool
    {
        return $this->appEnv === Env::STAGING;
    }

    public function isDevelopment(): bool
    {
        return $this->appEnv === Env::DEVELOPMENT;
    }

    public function isTesting(): bool
    {
        return $this->appEnv === Env::TESTING;
    }

    public function isLocal(): bool
    {
        return $this->appEnv === Env::LOCAL;
    }

    /**
     * Gets the environment variable value
     * @throws EnvException
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        if (!$this->loaded) {
            throw EnvException::environmentNotLoaded();
        }

        if (array_key_exists($key, $this->envContent)) {
            return $this->envContent[$key];
        }

        return $default;
    }

    /**
     * Checks if there is a such key
     */
    public function hasKey(string $key): bool
    {
        return array_key_exists($key, $this->envContent);
    }

    /**
     * Gets the row of .env file by given key
     */
    public function getRow(string $key): ?string
    {
        if (!array_key_exists($key, $this->envContent)) {
            return null;
        }

        return $key . '=' . $this->envContent[$key];
    }

    /**
     * Creates or updates the row in .env
     * @throws EnvException|ConfigException|DiException|BaseException|ReflectionException
     */
    public function updateRow(string $key, ?string $value): void
    {
        if (!$this->isMutable) {
            throw EnvException::environmentImmutable();
        }

        if (!$this->loaded) {
            throw EnvException::environmentNotLoaded();
        }

        $envFilePath = $this->getEnvFilePath();

        if (array_key_exists($key, $this->envContent)) {
            $envFileContent = fs()->get($envFilePath);

            if (!is_string($envFileContent)) {
                throw EnvException::fileNotFound($this->getEnvFileName($this->appEnv));
            }

            $pattern = '/^' . preg_quote($key . '=' . $this->envContent[$key], '/') . '/m';
            $envFileContent = preg_replace($pattern, $key . '=' . $value, $envFileContent);

            fs()->put($envFilePath, (string) $envFileContent);
        } else {
            fs()->append($envFilePath, PHP_EOL . $key . '=' . $value . PHP_EOL);
        }

        $this->envContent[$key] = $value;
    }

    /**
     * @return array{string, string}
     * @throws BaseException
     */
    private function resolveEnvironmentFile(): array
    {
        $envConfigFile = $this->envConfigFile();

        if (!file_exists($envConfigFile)) {
            throw EnvException::fileNotFound($envConfigFile);
        }

        $appEnv = $this->readAppEnv($envConfigFile);
        $envFile = $this->getEnvFileName($appEnv);

        if (!file_exists(App::getBaseDir() . DS . $envFile)) {
            throw EnvException::fileNotFound($envFile);
        }

        return [$appEnv, $envFile];
    }

    private function envConfigFile(): string
    {
        return App::getBaseDir() . DS . 'shared' . DS . 'config' . DS . 'env.php';
    }

    private function readAppEnv(string $envConfigFile): string
    {
        $envConfig = require $envConfigFile;

        return $envConfig['app_env'] ?? Env::PRODUCTION;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadDotenvFile(string $envFile): array
    {
        $loadedVars = Dotenv::createArrayBacked(App::getBaseDir(), $envFile)->load();

        return is_array($loadedVars) ? $loadedVars : [];
    }

    private function getEnvFilePath(): string
    {
        return App::getBaseDir() . DS . $this->getEnvFileName($this->appEnv);
    }

    private function getEnvFileName(string $appEnv): string
    {
        return '.env' . ($appEnv !== Env::PRODUCTION ? ".$appEnv" : '');
    }
}
