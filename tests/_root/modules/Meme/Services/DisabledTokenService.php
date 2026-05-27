<?php

namespace Quantum\Tests\_root\modules\Meme\Services;

use Quantum\Storage\Contracts\TokenServiceInterface;
use Quantum\Service\Service;

class DisabledTokenService extends Service implements TokenServiceInterface
{
    public function getAccessToken(): string
    {
        return 'disabled-access-token';
    }

    public function getRefreshToken(): string
    {
        return 'disabled-refresh-token';
    }

    public function saveTokens(string $accessToken, ?string $refreshToken = null): bool
    {
        return true;
    }
}
