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
 * @since 2.9.7
 */

namespace Quantum\Libraries\Database\Adapters\Idiorm;

use Quantum\Libraries\Database\Adapters\Idiorm\Statements\Transaction;
use Quantum\Libraries\Database\Adapters\Idiorm\Statements\Criteria;
use Quantum\Libraries\Database\Adapters\Idiorm\Statements\Reducer;
use Quantum\Libraries\Database\Adapters\Idiorm\Statements\Result;
use Quantum\Libraries\Database\Adapters\Idiorm\Statements\Query;
use Quantum\Libraries\Database\Adapters\Idiorm\Statements\Model;
use Quantum\Libraries\Database\Adapters\Idiorm\Statements\Join;
use Quantum\Libraries\Database\Contracts\RelationalInterface;
use Quantum\Libraries\Database\Exceptions\DatabaseException;
use Quantum\Libraries\Database\Contracts\DbalInterface;
use InvalidArgumentException;
use ORM;
use PDO;

/**
 * Class IdiormDbal
 * @package Quantum\Libraries\Database
 */
class IdiormDbal implements DbalInterface, RelationalInterface
{

    use Transaction;
    use Model;
    use Result;
    use Criteria;
    use Reducer;
    use Join;
    use Query;

    /**
     * SQLite driver
     */
    const DRIVER_SQLITE = 'sqlite';

    /**
     * MySQL driver
     */
    const DRIVER_MYSQL = 'mysql';

    /**
     * PostgresSQL driver
     */
    const DRIVER_PGSQL = 'pgsql';

    /**
     * Default charset
     */
    const DEFAULT_CHARSET = 'utf8';

    /**
     * The database table associated with model
     * @var string
     */
    private $table;

    /**
     * Id column of table
     * @var string
     */
    private $idColumn;

    /**
     * Foreign keys
     * @var array
     */
    private $foreignKeys;

    /**
     * Hidden fields
     * @var array
     */
    private $hidden;

    /**
     * Idiorm Patch object
     * @var IdiormPatch
     */
    private $ormPatch = null;

    /**
     * ORM Model
     * @var object
     */
    private $ormModel;

    /**
     * Active connection
     * @var array|null
     */
    private static $connection = null;

    /**
     * Operators map
     * @var string[]
     */
    private $operators = [
        '=' => 'where_equal',
        '!=' => 'where_not_equal',
        '>' => 'where_gt',
        '>=' => 'where_gte',
        '<' => 'where_lt',
        '<=' => 'where_lte',
        'IN' => 'where_in',
        'NOT IN' => 'where_not_in',
        'LIKE' => 'where_like',
        'NOT LIKE' => 'where_not_like',
        'NULL' => 'where_null',
        'NOT NULL' => 'where_not_null',
        '#=#' => null,
    ];

    /**
     * ORM Class
     * @var string
     */
    private static $ormClass = ORM::class;

    /**
     * Class constructor
     * @param string $table
     * @param string $idColumn
     * @param array $foreignKeys
     * @param array $hidden
     */
    public function __construct(string $table, string $idColumn = 'id', array $foreignKeys = [], array $hidden = [])
    {
        $this->table = $table;
        $this->idColumn = $idColumn;
        $this->foreignKeys = $foreignKeys;
        $this->hidden = $hidden;
    }

    /**
     * @inheritDoc
     */
    public static function connect(array $config)
    {
        $driver = $config['driver'] ?? '';
        $charset = $config['charset'] ?? self::DEFAULT_CHARSET;

        $configuration = self::getBaseConfig($driver, $config) + self::getDriverConfig($driver, $config, $charset);

        (self::$ormClass)::configure($configuration);

        self::$connection = (self::$ormClass)::get_config();
    }

    /**
     * @inheritDoc
     */
    public static function getConnection(): ?array
    {
        return self::$connection;
    }

    /**
     * @inheritDoc
     */
    public static function disconnect()
    {
        self::$connection = null;
        (self::$ormClass)::reset_db();
    }

    /**
     * @inheritDoc
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Gets the ORM model
     * @return ORM
     * @throws DatabaseException
     */
    public function getOrmModel(): ORM
    {
        if (!$this->ormModel) {
            if (!self::getConnection()) {
                throw DatabaseException::missingConfig();
            }

            $this->ormModel = (self::$ormClass)::for_table($this->table)->use_id_column($this->idColumn);
        }

        return $this->ormModel;
    }

    /**
     * @param ORM $ormModel
     */
    protected function updateOrmModel(ORM $ormModel)
    {
        $this->ormModel = $ormModel;
    }

    /**
     * @param string $driver
     * @param array $config
     * @return array
     */
    protected static function getBaseConfig(string $driver, array $config): array
    {
        return [
            'connection_string' => self::buildConnectionString($driver, $config),
            'logging' => config()->get('debug', false),
            'error_mode' => PDO::ERRMODE_EXCEPTION,
        ];
    }

    /**
     * @param string $driver
     * @param array $config
     * @param string $charset
     * @return array
     */
    protected static function getDriverConfig(string $driver, array $config, string $charset): array
    {
        if ($driver === self::DRIVER_MYSQL || $driver === self::DRIVER_PGSQL) {
            return [
                'username' => $config['username'] ?? null,
                'password' => $config['password'] ?? null,
                'driver_options' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset],
            ];
        }

        if ($driver === self::DRIVER_SQLITE) {
            return [];
        }

        throw new InvalidArgumentException("Unsupported driver: $driver");
    }

    /**
     * Builds connection string
     * @param string $driver
     * @param array $config
     * @return string
     */
    protected static function buildConnectionString(string $driver, array $config): string
    {
        if ($driver === self::DRIVER_SQLITE) {
            return $driver . ':' . ($config['database'] ?? '');
        }

        if ($driver === self::DRIVER_MYSQL || $driver === self::DRIVER_PGSQL) {
            $parts = [
                'host=' . ($config['host'] ?? ''),
                'dbname=' . ($config['dbname'] ?? ''),
            ];

            if (!empty($config['port'])) {
                $parts[] = 'port=' . $config['port'];
            }

            if (!empty($config['charset'])) {
                $parts[] = 'charset=' . $config['charset'];
            }

            return $driver . ':' . implode(';', $parts);
        }

        throw new InvalidArgumentException("Unsupported driver: $driver");
    }
}