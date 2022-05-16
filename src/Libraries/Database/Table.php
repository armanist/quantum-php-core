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
 * @since 2.7.0
 */

namespace Quantum\Libraries\Database;

use Quantum\Exceptions\MigrationException;

/**
 * Class Table
 * @package Quantum\Libraries\Database
 * 
 * @method self autoIncrement()
 * @method self primary()
 * @method self index(string $name = null)
 * @method self unique(string $name = null)
 * @method self fulltext(string $name = null)
 * @method self spatial(string $name = null)
 * @method self nullable(bool $indeed = true)
 * @method self default($value, bool $quoted = true)
 * @method self defaultQuoted()
 * @method self attribute(?string $value)
 * @method self comment(?string $value)
 * @method self type(string $type, $constraint)
 */
class Table
{

    /**
     * Action create
     */
    const CREATE = 1;

    /**
     * Action alter
     */
    const ALTER = 2;

    /**
     * Action drop
     */
    const DROP = 3;

    /**
     * Action rename
     */
    const RENAME = 4;

    /**
     * @var string
     */
    private $name;

    /**
     * 
     * @var string
     */
    private $newName;

    /**
     * @var int 
     */
    private $action = null;

    /**
     * @var array
     */
    private $columns = [];

    /**
     * @var array
     */
    private $indexKeys = [];

    /**
     * @var array
     */
    private $droppedIndexKeys = [];

    /**
     * Table constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Table destructor
     * Saves the data before object goes out of scope
     */
    public function __destruct()
    {
        $this->save();
    }

    public function renameTo(string $newName)
    {
        $this->newName = $newName;
        return $this;
    }

    /**
     * Sets an action on a table to be performed 
     * @param int $action
     * @param array|null $data
     * @return Table
     */
    public function setAction(int $action, ?array $data = null): Table
    {
        $this->action = $action;

        if ($data) {
            $key = key($data);
            $this->$key = $data[$key];
        }

        return $this;
    }

    /**
     * Adds column to the table
     * @param string $name
     * @param string $type
     * @param mixed $constraint
     * @return Table
     */
    public function addColumn(string $name, string $type, $constraint = null): Table
    {
        array_push($this->columns, [
            'column' => new Column($name, $type, $constraint),
            'action' => $this->action == self::ALTER ? Column::ADD : null
        ]);

        return $this;
    }

    /**
     * Modifies the column
     * @param string $name
     * @param string $type
     * @param mixed $constraint
     * @return Table
     */
    public function modifyColumn(string $name, string $type = null, $constraint = null): Table
    {
        if ($this->action == self::ALTER) {
            array_push($this->columns, [
                'column' => new Column($name, $type, $constraint),
                'action' => Column::MODIFY
            ]);
        }

        return $this;
    }

    /**
     * Renames the column name
     * @param string $oldName
     * @param string $newName
     */
    public function renameColumn(string $oldName, string $newName)
    {
        if ($this->action == self::ALTER) {
            array_push($this->columns, [
                'column' => (new Column($oldName))->renameTo($newName),
                'action' => Column::RENAME
            ]);
        }
    }

    /**
     * Drops the column
     * @param string $name
     */
    public function dropColumn(string $name)
    {
        if ($this->action == self::ALTER) {
            array_push($this->columns, [
                'column' => new Column($name),
                'action' => Column::DROP
            ]);
        }
    }

    /**
     * Adds new index to column
     * @param string $columnName
     * @param string $indexType
     * @param string $indexName
     */
    public function addIndex(string $columnName, string $indexType, string $indexName = null)
    {
        if ($this->action == self::ALTER) {
            array_push($this->columns, [
                'column' => new Column($columnName),
                'action' => Column::ADD_INDEX
            ]);

            $this->$indexType($indexName);
        }
    }

    /**
     * Drops the column index
     * @param string $indexName
     */
    public function dropIndex(string $indexName)
    {
        if ($this->action == self::ALTER) {
            array_push($this->columns, [
                'column' => (new Column('dummy'))->indexDrop($indexName),
                'action' => Column::DROP_INDEX
            ]);
        }
    }

    public function after(string $columnName)
    {
        $this->columns[$this->columnKey()]['column']->after($columnName);
        return $this;
    }

    /**
     * Gets the generated query
     * @return string
     */
    public function getSql(): string
    {
        $sql = '';

        switch ($this->action) {
            case self::CREATE:
                $sql = $this->createTableSql();
                break;
            case self::ALTER:
                $sql = $this->alterTableSql();
                break;
            case self::RENAME:
                $sql = $this->renameTableSql();
                break;
            case self::DROP:
                $sql = $this->dropTableSql();
                break;
        }

        return $sql;
    }

    /**
     * Allows to call methods of Column class 
     * @param string $method
     * @param array|null $arguments
     * @return $this
     * @throws Quantum\Exceptions\MigrationException
     */
    public function __call(string $method, ?array $arguments)
    {
        if (!method_exists(Column::class, $method)) {
            throw MigrationException::methodNotDefined($method);
        }

        $this->columns[$this->columnKey()]['column']->{$method}(...$arguments);
        return $this;
    }

    /**
     * Saves the query
     */
    protected function save()
    {
        $sql = $this->getSql();

        if ($sql) {
            Database::execute($sql);
        }
    }

    /**
     * Generates create table statement
     * @return string
     */
    protected function createTableSql()
    {
        $columnsSql = $this->columnsSql();
        $indexesSql = $this->indexesSql();
        $sql = '';

        if ($columnsSql) {
            $sql = 'CREATE TABLE `' . $this->name . '` (';
            $sql .= $columnsSql;
            $sql .= ($indexesSql ? ', ' . $indexesSql : '');
            $sql .= ');';
        }

        return $sql;
    }

    /**
     * Generates alter table statement
     * @return string
     */
    protected function alterTableSql(): string
    {
        $columnsSql = $this->columnsSql();
        $indexesSql = $this->indexesSql();
        $dropIndexesSql = $this->dropIndexesSql();
        $sql = '';

        if ($columnsSql || $indexesSql || $dropIndexesSql) {
            $sql = 'ALTER TABLE `' . $this->name . '` ';
            $sql .= $columnsSql;
            $sql .= (($columnsSql && $indexesSql) ? ', ' . $indexesSql : $indexesSql);
            $sql .= ((($columnsSql || $indexesSql) && $dropIndexesSql) ? ', ' . $dropIndexesSql : $dropIndexesSql);
            $sql .= ';';
        }

        return $sql;
    }

    /**
     * Prepares rename table statement
     * @return string
     */
    protected function renameTableSql(): string
    {
        return 'RENAME TABLE `' . $this->name . '` TO `' . $this->newName . '`;';
    }

    /**
     * Prepares drop table statement
     * @return string
     */
    protected function dropTableSql(): string
    {
        return 'DROP TABLE `' . $this->name . '`';
    }

    /**
     * Prepares columns statements for table
     * @return string
     */
    protected function columnsSql(): string
    {
        $sql = '';

        if ($this->columns) {
            $columns = [];

            foreach ($this->columns as $entry) {
                $columnString = '';

                if ($entry['action'] != Column::ADD_INDEX && $entry['action'] != Column::DROP_INDEX) {
                    $columnString .= ($entry['action'] ? $entry['action'] . ' COLUMN ' : '');
                    $columnString .= $this->composeColumn($entry['column'], $entry['action']);
                }

                if ($entry['column']->get('indexKey')) {
                    $this->indexKeys[$entry['column']->get('indexKey')][] = [
                        'columnName' => $entry['column']->get('name'),
                        'indexName' => $entry['column']->get('indexName'),
                    ];
                }

                if ($entry['column']->get('indexDrop')) {
                    $this->droppedIndexKeys[] = $entry['column']->get('indexDrop');
                }

                if ($columnString) {
                    array_push($columns, $columnString);
                }
            }

            $sql = implode(', ', $columns);
        }

        return $sql;
    }

    /**
     * Composes the column 
     * @param Column $column
     * @param string $action
     * @return string
     */
    protected function composeColumn(Column $column, string $action = null): string
    {
        return
                $this->columnAttrSql($column->get(Column::NAME), '`', '`') .
                $this->columnAttrSql($column->get(Column::NEW_NAME), ' TO `', '`') .
                $this->columnAttrSql($column->get(Column::TYPE), ' ') .
                $this->columnAttrSql($column->get(Column::CONSTRAINT), '(', ')') .
                $this->columnAttrSql($column->get(Column::ATTRIBUTE), ' ') .
                $this->columnAttrSql($column->get(Column::NULLABLE, $action), ' ',) .
                $this->columnAttrSql($column->get(Column::DEFAULT), ' DEFAULT ' . ($column->defaultQuoted() ? '\'' : ''), ($column->defaultQuoted() ? '\'' : '')) .
                $this->columnAttrSql($column->get(Column::COMMENT), ' COMMENT \'', '\'') .
                $this->columnAttrSql($column->get(Column::AFTER), ' AFTER `', '`') .
                $this->columnAttrSql($column->get(Column::AUTO_INCREMENT), ' ');
    }

    /**
     * Prepares column attributes
     * @param string|null $definition
     * @param string $before
     * @param string $after
     * @return string
     */
    protected function columnAttrSql(?string $definition, string $before = '', string $after = ''): string
    {
        $sql = '';

        if (!is_null($definition)) {
            $sql .= $before . $definition . $after;
        }

        return $sql;
    }

    /**
     * Prepares statement for primary key
     * @return string
     */
    protected function primaryKeysSql(): string
    {
        $sql = '';

        if (isset($this->indexKeys['primary'])) {
            $sql .= ($this->action == self::ALTER ? 'ADD ' : '');

            $sql .= 'PRIMARY KEY (';

            foreach ($this->indexKeys['primary'] as $key => $primaryKey) {
                $sql .= '`' . $primaryKey['columnName'] . '`';
                $sql .= (array_key_last($this->indexKeys['primary']) != $key ? ', ' : '');
            }

            $sql .= ')';
        }

        return $sql;
    }

    /**
     * Prepares statement for index keys
     * @param string $type
     * @return string
     */
    protected function indexKeysSql(string $type): string
    {
        $sql = '';

        if (isset($this->indexKeys[$type])) {
            $indexes = [];

            foreach ($this->indexKeys[$type] as $key => $indexKey) {
                $indexString = '';

                $indexString .= ($this->action == self::ALTER ? 'ADD ' : '');
                $indexString .= strtoupper($type);
                $indexString .= ($indexKey['indexName'] ? ' `' . $indexKey['indexName'] . '`' : '');
                $indexString .= ' (`' . $indexKey['columnName'] . '`)';

                array_push($indexes, $indexString);
            }

            $sql = implode(', ', $indexes);
        }

        return $sql;
    }

    /**
     * Builds a complete statement for index keys
     * @return string
     */
    protected function indexesSql(): string
    {
        return $this->primaryKeysSql() .
                $this->indexKeysSql(Key::INDEX) .
                $this->indexKeysSql(Key::UNIQUE) .
                $this->indexKeysSql(Key::FULLTEXT) .
                $this->indexKeysSql(Key::SPATIAL);
    }

    protected function dropIndexesSql()
    {
        $sql = '';

        if (!empty($this->droppedIndexKeys)) {
            $indexes = [];

            foreach ($this->droppedIndexKeys as $index) {
                $indexes[] = 'DROP INDEX `' . $index . '`';
            }

            $sql .= implode(', ', $indexes);
        }

        return $sql;
    }

    /**
     * Checks if column exists on a table 
     * @param string $columnName
     * @return bool
     */
    private function checkColumnExists(string $columnName): bool
    {
        $columnIndex = null;

        $columns = Database::fetchColumns($this->name);

        foreach ($columns as $index => $column) {
            if ($columnName == $column) {
                $columnIndex = $index;
                break;
            }
        }

        return !is_null($columnIndex);
    }

    /**
     * Gets the column key
     * @return int
     */
    private function columnKey(): int
    {
        return (int) array_key_last($this->columns);
    }

}
