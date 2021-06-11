<?php

/*
 * This file is part of the Fluent PDO package.
 *
 * (c) 2019-2021 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\FluentPdo;


use LogicException;
use PDO;
use PDOException;
use PDOStatement;

class FDOMappedQuery
{
    use FDOQueryTrait;

    /** @var string */
    private $className;

    public function __construct(PDOStatement $statement, ?array $params, string $className)
    {
        $this->statement = $statement;
        $this->params = $params;
        $this->className = $className;
    }

    public function fetchAll(&$array): void
    {
        $this->executeStatement();

        $array = $this->statement->fetchAll();
    }

    public function iterate(&$traversable): void
    {
        $this->executeStatement();

        $traversable = $this->statement;
    }

    public function single(&$result): void
    {
        $this->executeStatement();

        $row = $this->statement->fetch();
        if ($row === false)
            throw new LogicException("FDOMappedQuery::single() returned no records");

        $next = $this->statement->fetch();
        if ($next !== false)
            throw new LogicException("FDOMappedQuery::single() returned multiple records");

        $result = $row;
    }

    public function singleOrNull(&$result): void
    {
        $this->executeStatement();

        $row = $this->statement->fetch();
        if ($row === false) {
            $result = null;
            return;
        }

        $next = $this->statement->fetch();
        if ($next !== false)
            throw new LogicException("FDOMappedQuery::singleOrNull() returned multiple records");

        $result = $row;
    }

    private function executeStatement(): void
    {
        switch ($this->className) {
            case 'array':
                $this->statement->setFetchMode(PDO::FETCH_ASSOC);
                break;
            case 'indexed':
                $this->statement->setFetchMode(PDO::FETCH_NUM);
                break;
            case 'object':
                $this->statement->setFetchMode(PDO::FETCH_OBJ);
                break;
            default:
                $this->statement->setFetchMode(PDO::FETCH_CLASS, $this->className);
                break;
        }

        $this->bindParameters();

        if (!$this->statement->execute())
            throw new PDOException("FDOMappedQuery::executeStatement() statement->execute failed");
    }
}
