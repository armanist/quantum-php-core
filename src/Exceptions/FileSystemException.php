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
 * @since 2.8.0
 */

namespace Quantum\Exceptions;

/**
 * Class FileSystemException
 * @package Quantum\Exceptions
 */
class FileSystemException extends \Exception
{
    /**
     * @param string $methodName
     * @param string $adapterName
     * @return \Quantum\Exceptions\FileSystemException
     */
    public static function methodNotSupported(string $methodName, string $adapterName): FileSystemException
    {
        return new static(t('not_supported_method', [$methodName, $adapterName]), E_WARNING);
    }

    /**
     * @param string $name
     * @return \Quantum\Exceptions\FileSystemException
     */
    public static function directoryNotExists(string $name): FileSystemException
    {
        return new static(t('directory_not_exist', $name), E_WARNING);
    }

    /**
     * @param string $name
     * @return \Quantum\Exceptions\FileSystemException
     */
    public static function directoryNotWritable(string $name): FileSystemException
    {
        return new static(t('directory_not_writable', $name), E_WARNING);
    }

    /**
     * @return \Quantum\Exceptions\FileSystemException
     */
    public static function fileAlreadyExists(): FileSystemException
    {
        return new static(t('file_already_exists'), E_WARNING);
    }

}
