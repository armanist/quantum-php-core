<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Encryption\Enums;

/**
 * Class CryptorType
 * @codeCoverageIgnore
 */
final class CryptorType
{
    public const SYMMETRIC = 'symmetric';

    public const ASYMMETRIC = 'asymmetric';

    private function __construct()
    {
    }
}
