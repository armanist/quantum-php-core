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
 * @since 2.0.0
 */

namespace Quantum\Libraries\Auth;

use Quantum\Exceptions\ExceptionMessages;
use Quantum\Exceptions\AuthException;
use Quantum\Libraries\JWToken\JWToken;
use Quantum\Libraries\Hasher\Hasher;
use Quantum\Http\Response;
use Quantum\Http\Request;
use Quantum\Libraries\Mailer\Mailer;

/**
 * Class ApiAuth
 * @package Quantum\Libraries\Auth
 */
class ApiAuth extends BaseAuth implements AuthenticableInterface
{

    /**
     * @var JWToken
     */
    protected $jwt;

    /**
     * @var Hasher
     */
    protected $hasher;

    /**
     * @var AuthServiceInterface
     */
    protected $authService;

    /**
     * @var array
     */
    protected $keys = [];

    /**
     * @var string
     */
    protected $authUserKey = 'auth_user';

    /**
     * ApiAuth constructor.
     * @param AuthServiceInterface $authService
     * @param Hasher $hasher
     * @param JWToken|null $jwt
     */
    public function __construct(AuthServiceInterface $authService, Hasher $hasher, JWToken $jwt = null)
    {
        $this->jwt = $jwt;
        $this->hasher = $hasher;
        $this->authService = $authService;
        $this->keys = $this->authService->getDefinedKeys();
    }

    /**
     * Sign In
     * @param string $username
     * @param string $password
     * @return array
     * @throws AuthException
     */
    public function signin($mailer, $username, $password)
    {
        $user = $this->authService->get($this->keys['usernameKey'], $username);

        if (empty($user)) {
            throw new AuthException(ExceptionMessages::INCORRECT_AUTH_CREDENTIALS);
        }

        if (!$this->hasher->check($password, $user[$this->keys['passwordKey']])) {
            throw new AuthException(ExceptionMessages::INCORRECT_AUTH_CREDENTIALS);
        }
        if (!$this->isActivated($user)) {
            throw new AuthException(ExceptionMessages::INACTIVE_ACCOUNT);
        }

        if (filter_var(config()->get('2SV'), FILTER_VALIDATE_BOOLEAN)) {

            $otp_token = $this->generateOtpToken($user[$this->keys['usernameKey']]);

            $time = new \DateTime();

            $time->add(new \DateInterval('PT' . config()->get('otp_expiry_time') . 'M'));

            $otp_expiry_time = $time->format('Y-m-d H:i');

            $this->towStepVerification($mailer, $user, $otp_expiry_time, $otp_token);

            return $otp_token;

        } else {

            $tokens = $this->setUpdatedTokens($user);

            return $tokens;
        }
    }

    /**
     * Sign Out
     * @return bool|mixed
     */
    public function signout()
    {
        $refreshToken = Request::getHeader($this->keys['refreshTokenKey']);

        $user = $this->authService->get($this->keys['refreshTokenKey'], $refreshToken);

        if (!empty($user)) {
            $this->authService->update(
                    $this->keys['refreshTokenKey'],
                    $refreshToken,
                    [
                        $this->authUserKey => $user,
                        $this->keys['refreshTokenKey'] => ''
                    ]
            );

            Request::deleteHeader($this->keys['refreshTokenKey']);
            Request::deleteHeader('Authorization');
            Response::delete('tokens');

            return true;
        }

        return false;
    }

    /**
     * User
     * @return mixed|null
     */
    public function user()
    {
        try {
            $accessToken = base64_decode(Request::getAuthorizationBearer());
            return (object) $this->jwt->retrieve($accessToken)->fetchData();
        } catch (\Exception $e) {
            if (Request::hasHeader($this->keys['refreshTokenKey'])) {
                $user = $this->checkRefreshToken();
                if ($user) {
                    return $this->user();
                }
            }
            return null;
        }
    }

    /**
     * Get Updated Tokens
     * @param object $user
     * @return array
     */
    public function getUpdatedTokens(array $user)
    {
        return [
            $this->keys['refreshTokenKey'] => $this->generateToken(),
            $this->keys['accessTokenKey'] => base64_encode($this->jwt->setData($this->filterFields($user))->compose())
        ];
    }

    /**
     * Verify
     * @param int $otp
     * @param string $otp_token
     * @return array
     * @throws \Exception
     */
    public function verify($otp, $otp_token)
    {
        $user = $this->authService->get($this->keys['otpToken'], $otp_token);

        if (new \DateTime() >= new \DateTime($user[$this->keys['otpExpiryIn']])){
            throw new AuthException(ExceptionMessages::VERIFICATION_CODE_EXPIRY_IN);
        }

        if ($otp != $user[$this->keys['otpKey']]) {
            throw new AuthException(ExceptionMessages::INCORRECT_VERIFICATION_CODE);
        }

        $this->authService->update($this->keys['usernameKey'], $user[$this->keys['usernameKey']], [
            $this->keys['otpKey'] => null,
            $this->keys['otpExpiryIn'] => null,
            $this->keys['otpToken'] => null,
        ]);

        $tokens = $this->setUpdatedTokens($this->filterFields($user));

        return $tokens;
    }

    /**
     * Resend Otp
     * @param Mailer $mailer
     * @param string $otp_token
     * @return bool|mixed
     * @throws AuthException
     */

    public function resendOtp($mailer, $otp_token)
    {
        $user = $this->authService->get($this->keys['otpToken'], $otp_token);

        if (empty($user)) {

            throw new AuthException(ExceptionMessages::INCORRECT_AUTH_CREDENTIALS);
        }

        $otp_token = $this->generateOtpToken($user[$this->keys['usernameKey']]);

        $time = new \DateTime();

        $time->add(new \DateInterval('PT' . config()->get('otp_expiry_time') . 'M'));

        $stamp = $time->format('Y-m-d H:i');

        $this->towStepVerification($mailer, $user, $stamp, $otp_token);

        return $otp_token;
    }

    /**
     * Check Refresh Token
     * @return bool|mixed
     */
    protected function checkRefreshToken()
    {
        $user = $this->authService->get($this->keys['refreshTokenKey'], Request::getHeader($this->keys['refreshTokenKey']));

        if (!empty($user)) {
            $this->setUpdatedTokens($user);
            return $user;
        }

        return false;
    }

    /**
     * Set Updated Tokens
     * @param array $user
     * @return array
     */
    protected function setUpdatedTokens(array $user)
    {
        $tokens = $this->getUpdatedTokens($user);

        $this->authService->update(
                $this->keys['usernameKey'],
                $user[$this->keys['usernameKey']],
                [
                    $this->authUserKey => $user,
                    $this->keys['refreshTokenKey'] => $tokens[$this->keys['refreshTokenKey']]
                ]
        );

        Request::setHeader($this->keys['refreshTokenKey'], $tokens[$this->keys['refreshTokenKey']]);
        Request::setHeader('Authorization', 'Bearer ' . $tokens[$this->keys['accessTokenKey']]);
        Response::set('tokens', $tokens);

        return $tokens;
    }

}
