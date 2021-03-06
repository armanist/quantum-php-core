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
 * @since 2.4.0
 */

namespace Quantum\Http\Request;

use Quantum\Libraries\Upload\File as FileUpload;
use Quantum\Exceptions\FileUploadException;

/**
 * Trait File
 * @package Quantum\Http\Request
 */
trait File
{

    /**
     * Files
     * @var array
     */
    private static $__files = [];

    /**
     * Checks to see if request contains file
     * @param string $key
     * @return bool
     */
    public static function hasFile(string $key): bool
    {
        return isset(self::$__files[$key]);
    }

    /**
     * Gets the file or array of file objects
     * @param string $key
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function getFile(string $key)
    {
        if (!self::hasFile($key)) {
            throw new \InvalidArgumentException(_message(FileUploadException::UPLOADED_FILE_NOT_FOUND, $key));
        }

        return self::$__files[$key];
    }

    /**
     * @param array $files
     * @return array|\Quantum\Libraries\Upload\File[]|null
     * @throws \Quantum\Exceptions\DiException
     * @throws \ReflectionException
     */
    private static function handleFiles(array $files): ?array
    {
        if (!count($files)) {
            return [];
        }

        $key = key($files);

        if ($key) {
            if (!is_array($files[$key]['name'])) {
                return [$key => new FileUpload($files[$key])];
            } else {
                $formattedFiles = [];

                foreach ($files[$key]['name'] as $index => $name) {
                    $formattedFiles[$key][$index] = new FileUpload([
                        'name' => $name,
                        'type' => $files[$key]['type'][$index],
                        'tmp_name' => $files[$key]['tmp_name'][$index],
                        'error' => $files[$key]['error'][$index],
                        'size' => $files[$key]['size'][$index],
                    ]);
                }

                return $formattedFiles;
            }
        }

    }

}