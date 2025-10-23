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
 * @since 2.9.8
 */

namespace {{MODULE_NAMESPACE}}\Services;

use Quantum\Libraries\Auth\Contracts\AuthServiceInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Model\Exceptions\ModelException;
use Quantum\Libraries\Auth\User as AuthUser;
use Quantum\App\Exceptions\BaseException;
use Quantum\Model\Factories\ModelFactory;
use {{MODULE_NAMESPACE}}\Models\User;
use Quantum\Di\Exceptions\DiException;
use Quantum\Model\ModelCollection;
use Quantum\Service\QtService;
use Quantum\Model\QtModel;
use ReflectionException;

/**
 * Class AuthService
 * @package Modules\{{MODULE_NAME}}
 */
class AuthService extends QtService implements AuthServiceInterface
{

    /**
     * @var QtModel
     */
    private $model;

    /**
     * @throws ModelException
     */
    public function __construct()
    {
        $this->model = ModelFactory::get(User::class);
    }

    /**
     * Get users
     * @return ModelCollection
     */
    public function getAll(): ModelCollection
    {
        return $this->model->get();
    }

    /**
     * Get user
     * @param string $uuid
     * @return User
     */
    public function getUserByUuid(string $uuid): User
    {
        return$this->model->findOneBy('uuid', $uuid);
    }

    /**
     * Get user
     * @param string $field
     * @param $value
     * @return AuthUser|null
     */
    public function get(string $field, $value): ?AuthUser
    {
        $user = $this->model->findOneBy($field, $value);

        if ($user->isEmpty()) {
            return null;
        }

        return (new AuthUser())->setData($user->asArray());
    }

    /**
     * Add user
     * @param array $data
     * @return AuthUser
     * @throws BaseException
     * @throws ConfigException
     * @throws DiException
     * @throws ReflectionException
     */
    public function add(array $data): AuthUser
    {
        $this->createUserDirectory($data['uuid']);

        $user = $this->model->create();
        $user->fillObjectProps($data);
        $user->save();

        return (new AuthUser())->setData($data);
    }

    /**
     * Update user
     * @param string $field
     * @param string|null $value
     * @param array $data
     * @return AuthUser|null
     */
    public function update(string $field, ?string $value, array $data): ?AuthUser
    {
        $user = $this->model->findOneBy($field, $value);

        if ($user->isEmpty()) {
            return null;
        }

        $user->fillObjectProps($data);
        $user->save();

        return (new AuthUser())->setData($this->model->findOneBy($field, $value)->asArray());
    }

    /**
     * Delete users table
     */
    public function deleteTable()
    {
        $this->model->deleteTable();
    }

    /**
     * User Schema
     * @return array
     */
    public function userSchema(): array
    {
        return [
            'id' => ['name' => 'id', 'visible' => true],
            'uuid' => ['name' => 'uuid', 'visible' => true],
            'firstname' => ['name' => 'firstname', 'visible' => true],
            'lastname' => ['name' => 'lastname', 'visible' => true],
            'role' => ['name' => 'role', 'visible' => true],
            'username' => ['name' => 'email', 'visible' => true],
            'password' => ['name' => 'password', 'visible' => false],
            'image' => ['name' => 'image', 'visible' => true],
            'activationToken' => ['name' => 'activation_token', 'visible' => false],
            'rememberToken' => ['name' => 'remember_token', 'visible' => false],
            'resetToken' => ['name' => 'reset_token', 'visible' => false],
            'accessToken' => ['name' => 'access_token', 'visible' => false],
            'refreshToken' => ['name' => 'refresh_token', 'visible' => false],
            'otp' => ['name' => 'otp', 'visible' => false],
            'otpExpiry' => ['name' => 'otp_expires', 'visible' => false],
            'otpToken' => ['name' => 'otp_token', 'visible' => false],
        ];
    }

    /**
     * Creates user directory
     * @param string $uuid
     * @throws DiException
     * @throws ReflectionException
     * @throws BaseException
     * @throws ConfigException
     */
    private function createUserDirectory(string $uuid)
    {
        $userDirectory = uploads_dir() . DS . $uuid;

        if(!fs()->isDirectory($userDirectory)) {
            fs()->makeDirectory($userDirectory);
        }
    }
}