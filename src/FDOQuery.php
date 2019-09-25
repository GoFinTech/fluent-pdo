<?php

/*
 * This file is part of the Fluent PDO package.
 *
 * (c) 2019 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\FluentPdo;


use InvalidArgumentException;
use PDOException;
use PDOStatement;

class FDOQuery
{
    use FDOQueryTrait;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function params(array $params): FDOQuery
    {
        if (isset($this->params))
            throw new InvalidArgumentException("FDOQuery::params() called twice");

        $this->params = $params;

        return $this;
    }

    public function bind(array $map): FDOBoundQuery
    {
        if (empty($map))
            throw new InvalidArgumentException("FDOQuery::bind() called with empty map");

        return new FDOBoundQuery($this->statement, $this->params, $map);
    }

    public function map(string $className): FDOMappedQuery
    {
        return new FDOMappedQuery($this->statement, $this->params, $className);
    }

    public function execute(): void
    {
        $this->bindParameters();

        if (!$this->statement->execute())
            throw new PDOException("FDOQuery::execute() statement->execute failed");
    }
}
