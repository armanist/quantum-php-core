<?php

use Quantum\Storage\Contracts\TokenServiceInterface;
use Quantum\Tests\_root\modules\Meme\Services\DisabledTokenService;

return [
    TokenServiceInterface::class => DisabledTokenService::class,
];
