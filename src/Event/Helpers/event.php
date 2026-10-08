<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\Event\EventManager;
use Quantum\Di\Di;

/**
 * Gets the EventManager instance
 */
function event(): EventManager
{
    if (!Di::isRegistered(EventManager::class)) {
        Di::register(EventManager::class);
    }

    return Di::get(EventManager::class);
}
