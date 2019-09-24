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
 * @since 1.0.0
 */

namespace Quantum\Libraries\Database;

use Quantum\Mvc\Qt_Model;
use PDO;
use ORM;

/**
 * IdiORM DBAL
 *
 * Database Abstract Layer class for IdiOrm
 * Default DBAL for framework
 *
 * @package Quantum
 * @subpackage Libraries.Database
 * @category Libraries
 */
class IdiormDbal implements DbalInterface
{

    /**
     * The database table associated with model
     *
     * @var string
     */
    private $table;

    /**
     * Id column of table
     *
     * @var string
     */
    private $idColumn;

    /**
     * Idiorm object
     *
     * @var object
     */
    public $ormObject;

    /**
     * ORM Class
     *
     * @var string
     */
    private static $ormClass = ORM::class;

    /**
     * Class constructor
     *
     * @param string $table
     * @param string $idColumn
     */
    public function __construct($table, $idColumn = 'id')
    {
        $this->table = $table;
        $this->idColumn = $idColumn;
        $this->ormObject = (self::$ormClass)::for_table($this->table)->use_id_column($this->idColumn);
    }

    /**
     * Get table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * DB Connect
     *
     * Connects to database
     *
     * @param array $connectionString
     * @uses ORM::configure Idiorm
     * @return void
     */
    public static function dbConnect($connectionString)
    {
        (self::$ormClass)::configure(array(
            'connection_string' => $connectionString['driver'] . ':host=' . $connectionString['host'] . ';dbname=' . $connectionString['dbname'],
            'username' => $connectionString['username'],
            'password' => $connectionString['password'],
            'driver_options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $connectionString['charset']),
            'logging' => get_config('debug', false)
        ));

        return (self::$ormClass)::get_config();
    }

    /**
     * Find one
     *
     * Gets record by primary key
     *
     * @param array $params
     * @uses ORM Idiorm
     * @return object
     */
    public function findOne($params)
    {
        $result = $this->ormObject->find_one($params[0]);
        return $result ? $result : $this->ormObject;
    }

    /**
     * FindOneBy
     *
     * Gets record by given column
     *
     * @param array $params
     * @uses ORM Idiorm
     * @return object
     */
    public function findOneBy($params)
    {
        $result = $this->ormObject->where($params[0], $params[1])->find_one();
        return $result ? $result : $this->ormObject;
    }

    /**
     * First
     *
     * Gets the first item
     *
     * @uses ORM Idiorm
     * @return object
     */
    public function first()
    {
        $result = $this->ormObject->find_one();
        return $result ? $result : $this->ormObject;
    }

    /**
     * Criterias
     *
     * Adds where criterias
     *
     * @param array $params
     * @uses ORM Idiorm
     * @return object
     */
    public function criterias($params)
    {
        foreach ($params as $param) {
            $column = $param[0];
            $operation = $param[1];
            $value = $param[2];

            switch ($operation) {
                case '=':
                    $this->ormObject->where_equal($column, $value);
                    break;
                case '!=':
                    $this->ormObject->where_not_equal($column, $value);
                    break;
                case '>':
                    $this->ormObject->where_gt($column, $value);
                    break;
                case '>=':
                    $this->ormObject->where_gte($column, $value);
                    break;
                case '<':
                    $this->ormObject->where_lt($column, $value);
                    break;
                case '<=':
                    $this->ormObject->where_lte($column, $value);
                    break;
                case 'LIKE':
                    $this->ormObject->where_like($column, $value);
                    break;
                case 'NOT LIKE':
                    $this->ormObject->where_not_like($column, $value);
                    break;
            }
        }

        return $this->ormObject;
    }

    /**
     * Order By
     *
     * Orders the result by ascending or descending
     *
     * @param array $params
     * @uses ORM Idiorm
     * @return object
     */
    public function orderBy($params)
    {
        $orderCriteria = array_flip($params[0]);
        $direction = key($orderCriteria);
        $column = $orderCriteria[$direction];

        if (strtolower($direction) == 'asc') {
            $this->ormObject->order_by_asc($column);
        } elseif (strtolower($direction) == 'desc') {
            $this->ormObject->order_by_desc($column);
        }

        return $this->ormObject;
    }

    /**
     * Group By
     *
     * Groups the result by column
     *
     * @param array $params
     * @uses ORM Idiorm
     * @return object
     */
    public function groupBy($params)
    {
        return $this->ormObject->group_by($params[0]);
    }

    /**
     * Limit
     *
     * Returns the result by given limit
     *
     * @param array $params
     * @uses ORM Idiorm
     * @return object
     */
    public function limit($params)
    {
        return $this->ormObject->limit($params[0]);
    }

    /**
     * Offset
     *
     * Returns the result by given offset
     *
     * @param array $params
     * @uses ORM Idiorm
     * @return object
     */
    public function offset($params)
    {
        return $this->ormObject->offset($params[0]);
    }

    /**
     * Get
     *
     * Gets the result set
     *
     * @param array $params
     * @uses ORM Idiorm
     * @return mixed
     */
    public function get($params)
    {
        return ($params && $params[0] == 'object') ? $this->ormObject->find_many() : $this->ormObject->find_array();
    }

    /**
     * Count
     *
     * Counts the result set
     *
     * @uses ORM Idiorm
     * @return int
     */
    public function count()
    {
        return $this->ormObject->count();
    }

    /**
     * asArray
     *
     * Casts the ormObject object to array
     *
     * @uses ORM Idiorm
     * @return array
     */
    public function asArray()
    {
        return $this->ormObject->as_array();
    }

    /**
     * Create
     *
     * Creates new db record
     *
     * @uses ORM Idiorm
     * @return object
     */
    public function create()
    {
        return $this->ormObject->create();
    }

    /**
     * Save
     *
     * Saves the data into the database
     *
     * @uses ORM Idiorm
     * @return bool
     */
    public function save()
    {
        return $this->ormObject->save();
    }

    /**
     * Delete
     *
     * Deletes the data from the database
     *
     * @uses ORM Idiorm
     * @return bool
     */
    public function delete()
    {
        return $this->ormObject->delete();
    }

    /**
     * Execute
     *
     * Raw execute
     *
     * @param string $query
     * @param array $parameters
     * @return bool
     */
    public static function execute($query, $parameters = [])
    {
        return (self::$ormClass)::raw_execute($query, $parameters);
    }

    /**
     * Query
     *
     * Raw query
     *
     * @param $query
     * @param array $parameters
     * @return array
     */
    public static function query($query, $parameters = [])
    {
        return (self::$ormClass)::for_table('dummy')->raw_query($query, $parameters)->find_array();
    }

}
