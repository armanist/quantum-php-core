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
 * @since 2.9.6
 */

namespace Quantum\Libraries\Database\Adapters\Sleekdb\Statements;

use Quantum\Libraries\Database\Exceptions\DatabaseException;
use Quantum\Libraries\Database\Contracts\DbalInterface;

/**
 * Trait Criteria
 * @package Quantum\Libraries\Database
 */
trait Criteria
{

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function criteria(string $column, string $operator, $value = null): DbalInterface
    {
        if (!in_array($operator, $this->operators)) {
            throw DatabaseException::operatorNotSupported($operator);
        }

        $this->criterias[] = [$column, $operator, $this->sanitizeValue($value)];

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function criterias(...$criterias): DbalInterface
    {
        foreach ($criterias as $criteria) {
            if (isset($criteria[0]) && is_array($criteria[0])) {
                $this->orCriteria($criteria);
            } else {
                $this->criteria(...$criteria);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function having(string $column, string $operator, string $value = null): DbalInterface
    {
        if (!in_array($operator, $this->operators)) {
            throw DatabaseException::operatorNotSupported($operator);
        }

        $this->havings[] = [$column, $operator, $value];

        return $this;
    }

    /**
     * Adds one or more OR criteria in brackets
     * @param array $orCriterias
     * @throws DatabaseException
     */
    protected function orCriteria(array $orCriterias)
    {
        foreach ($orCriterias as $index => $criteria) {
            $this->criteria(...$criteria);

            if ($index != array_key_last($orCriterias)) {
                $this->criterias[] = 'OR';
            }
        }
    }

    /**
     * @param $value
     * @return mixed|string|null
     */
    protected function sanitizeValue($value)
    {
        if (is_array($value)) {
            return array_map(function ($v) {
                return is_string($v) ? preg_quote($v, '/') : $v;
            }, $value);
        }

        return is_string($value) ? preg_quote($value, '/') : $value;
    }
}