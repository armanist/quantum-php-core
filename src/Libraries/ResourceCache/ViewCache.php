<?php

namespace Quantum\Libraries\ResourceCache;

use Quantum\Libraries\Storage\FileSystem;
use Quantum\Exceptions\ConfigException;
use Quantum\Exceptions\DiException;
use ReflectionException;
use voku\helper\HtmlMin;
use Quantum\Di\Di;

class ViewCache
{
	/**
	 * @var string
	 */
	private $cacheDir;

	/**
	 * @var string
	 */
	private $mimeType = '.tmp';

	/**
	 * @var object
	 */
	private static $fs;

	/**
	 * @param bool $fromCommand
	 * @throws ConfigException
	 * @throws DiException
	 * @throws ReflectionException
	 */
	public function __construct(bool $fromCommand = false)
	{
		self::$fs = Di::get(FileSystem::class);

		if (!config()->has('view_cache') && !$fromCommand) {
			throw ConfigException::configCollision('view_cache');
		}

		$configCacheDir = config()->get('view_cache.cache_dir', 'cache');
		$module = strtolower(current_module());

		$this->cacheDir = base_dir() . DS . $configCacheDir . DS . 'views' . DS;

		if ($module) {
			$this->cacheDir = base_dir() . DS . $configCacheDir . DS . 'views' . DS . $module . DS;
		}

		if (!self::$fs->isDirectory($this->cacheDir) && !$fromCommand) {
			mkdir($this->cacheDir, 0777, true);
		}
	}

	/**
	 * @param string $key
	 * @param string $content
	 * @param string $sessionId
	 * @return void
	 */
	public function set(string $key, string $content, string $sessionId): void
	{
		if (config()->has('view_cache.minify')) {
			$content = $this->minify($content);
		}

		$cacheFile = $this->getCacheFile($key, $sessionId);
		file_put_contents($cacheFile, $content);
	}

	/**
	 * @param string $key
	 * @param string $sessionId
	 * @param int $ttl
	 * @return mixed|null
	 */
	public function get(string $key, string $sessionId, int $ttl): ?string
	{
		$cacheFile = $this->getCacheFile($key, $sessionId);
		if (!file_exists($cacheFile)) {
			return null;
		}

		$data = file_get_contents($cacheFile);
		if (time() > (self::$fs->lastModified($cacheFile) + $ttl)) {
			self::$fs->remove($cacheFile);
			return null;
		}

		return $data;
	}

	/**
	 * @param string $key
	 * @param string $sessionId
	 * @return void
	 */
	public function delete(string $key, string $sessionId): void
	{
		$cacheFile = $this->getCacheFile($key, $sessionId);
		if (file_exists($cacheFile)) {
			self::$fs->remove($cacheFile);
		}
	}

	/**
	 * @param string $key
	 * @param string $sessionId
	 * @param int $ttl
	 * @return bool
	 */
	public static function exists(string $key, string $sessionId, int $ttl): bool
	{
		$cacheFile = (new self())->getCacheFile($key, $sessionId);

		if (!file_exists($cacheFile)) {
			return false;
		}

		if (time() > (self::$fs->lastModified($cacheFile) + $ttl)) {
			self::$fs->remove($cacheFile);
			return false;
		}

		return true;
	}

	/**
	 * @param string $key
	 * @param string $sessionId
	 * @return string
	 */
	private function getCacheFile(string $key, string $sessionId): string
	{
		return $this->cacheDir . md5($key . $sessionId) . $this->mimeType;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	private function minify(string $content): string
	{
		return (new HtmlMin())->minify($content);
	}
}