<?php

return [
    /**
     * ---------------------------------------------------------
     * Authentication configurations
     * ---------------------------------------------------------
     */
    'default' => 'jwt',

    'session' => [
        'service' => {{MODULE_NAMESPACE}}\Services\AuthService::class,
    ],

    'jwt' => [
        'service' => {{MODULE_NAMESPACE}}\Services\AuthService::class,
        'claims' => [
            'jti' => uniqid(),
            'iss' => 'issuer',
            'aud' => 'audience',
            'iat' => time(),
            'nbf' => time() + 1,
            'exp' => time() + 3600,
        ]
    ],

    /**
     * ---------------------------------------------------------
     * Two-factor authentication
     * ---------------------------------------------------------
     *
     * Enables or disables 2-factor authentication
     */
    'two_fa' => env('TWO_FA', true),

    /**
     * ---------------------------------------------------------
     * OTP expiration
     * ---------------------------------------------------------
     *
     * OTP expires after minutes defined
     */
    'otp_expires' => 2,
];