<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

use Quantum\App\Enums\AppType;
use Quantum\App\App;

/**
 * Checks whether the running application is the web app
 */
function is_web_app(): bool
{
    return App::getContext()->getType() === AppType::WEB;
}

/**
 * Checks whether the running application is the console app
 */
function is_console_app(): bool
{
    return App::getContext()->getType() === AppType::CONSOLE;
}
