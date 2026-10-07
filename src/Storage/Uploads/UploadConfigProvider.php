<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Storage\Uploads;

use Quantum\Storage\Exceptions\FileUploadException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Config\Setup;

class UploadConfigProvider
{
    /**
     * @return array<string, list<string>>
     * @throws FileUploadException|ConfigException
     */
    public function getAllowedMimeTypesMap(): array
    {
        if (!config()->has('uploads')) {
            if (!config()->importIfExists(new Setup('config', 'uploads'))) {
                return [];
            }
        }

        $allowedMimeTypesMap = config()->get('uploads.allowed_mime_types') ?? [];

        if (!is_array($allowedMimeTypesMap)) {
            throw FileUploadException::incorrectMimeTypesConfig('uploads');
        }

        return $allowedMimeTypesMap;
    }
}
