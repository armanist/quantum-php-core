<?php

/**
 * Quantum PHP Framework
 *
 * An open source software development framework for PHP
 *
 * @package Quantum
 * @author Arman Ag. <arman.ag@softberg.org>
 * @copyright Copyright (c) 2018 Softberg LLC (https://softberg.org)
 * @link http://quantum.softberg.org/
 * @since 2.9.5
 */

namespace Quantum\Libraries\Storage\Adapters\Local;

use Quantum\Libraries\Storage\Contracts\FilesystemAdapterInterface;
use Throwable;

/**
 * Class LocalFileSystemAdapter
 * @package Quantum\Libraries\Storage
 */
class LocalFileSystemAdapter implements FilesystemAdapterInterface
{

    /**
     * @param string $dirname
     * @param string|null $parentId
     * @inheritDoc
     */
    public function makeDirectory(string $dirname, ?string $parentId = null): bool
    {
        return mkdir($dirname);
    }

    /**
     * @inheritDoc
     */
    public function removeDirectory(string $dirname): bool
    {
        if (!is_dir($dirname)) {
            return false;
        }

        return rmdir($dirname);
    }

    /**
     * @inheritDoc
     */
    public function get(string $filename)
    {
        return file_get_contents($filename);
    }

    /**
     * Reads and returns the content of a file as JSON.
     * @param string $filename
     * @return false|mixed
     */
    public function getJson(string $filename)
    {
        $content = file_get_contents($filename);

        if (empty($content)) {
            return false;
        }

        $data = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $data : false;
    }

    /**
     * @param string $filename
     * @param string $content
     * @param string|null $parentId
     * @inheritDoc
     */
    public function put(string $filename, string $content, ?string $parentId = null)
    {
        return file_put_contents($filename, $content, LOCK_EX);
    }

    /**
     * @inheritDoc
     */
    public function append(string $filename, string $content)
    {
        return file_put_contents($filename, $content, FILE_APPEND | LOCK_EX);
    }

    /**
     * @inheritDoc
     */
    public function rename(string $oldName, string $newName): bool
    {
        return rename($oldName, $newName);
    }

    /**
     * @inheritDoc
     */
    public function copy(string $source, string $dest): bool
    {
        return copy($source, $dest);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $filename): bool
    {
        return file_exists($filename) && is_file($filename);
    }

    /**
     * @inheritDoc
     */
    public function size(string $filename)
    {
        return filesize($filename);
    }

    /**
     * @inheritDoc
     */
    public function lastModified(string $filename)
    {
        return filemtime($filename);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $filename): bool
    {
        return unlink($filename);
    }

    /**
     * @inheritDoc
     */
    public function isFile(string $filename): bool
    {
        return is_file($filename);
    }

    /**
     * @inheritDoc
     */
    public function isDirectory(string $dirname): bool
    {
        return is_dir($dirname);
    }

    /**
     * @inheritDoc
     */
    public function listDirectory(string $dirname)
    {
        $entries = [];

        try {
            foreach (scandir($dirname) as $item) {
                if ($item != '.' && $item != '..') {
                    $entries[] = realpath($dirname . DS . $item);
                }
            }

            return $entries;

        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Find path names matching a pattern
     * @param string $pattern
     * @param int $flags
     * @return array|false
     */
    public function glob(string $pattern, int $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Is Readable
     * @param string $filename
     * @return bool
     */
    public function isReadable(string $filename): bool
    {
        return is_readable($filename);
    }

    /**
     * Is Writable
     * @param string $filename
     * @return bool
     */
    public function isWritable(string $filename): bool
    {
        return is_writable($filename);
    }

    /**
     * Gets the content between given lines
     * @param string $filename
     * @param int $offset
     * @param int|null $length
     * @return array
     */
    public function getLines(string $filename, int $offset = 0, ?int $length = null): array
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES);

        if(!$lines) {
            return [];
        }

        if ($offset || $length) {
            $lines = array_slice($lines, $offset, $length ?: count($lines), true);
        }

        return $lines;
    }

    /**
     * Gets the filename with extension
     * @param string $path
     * @return string
     */
    public function fileNameWithExtension(string $path): string
    {
        return (string)pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Gets the file name
     * @param string $path
     * @return string
     */
    public function fileName(string $path): string
    {
        return (string)pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Gets the file extension
     * @param string $path
     * @return string
     */
    public function extension(string $path): string
    {
        return (string)pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Includes the required file
     * @param string $file
     * @param bool $once
     * @return mixed
     */
    public function require(string $file, bool $once = false)
    {
        if ($once) {
            return require_once $file;
        } else {
            return require $file;
        }
    }

    /**
     * Includes a file
     * @param string $file
     * @param bool $once
     * @return mixed
     */
    public function include(string $file, bool $once = false)
    {
        if ($once) {
            return include_once $file;
        } else {
            return include $file;
        }
    }
}