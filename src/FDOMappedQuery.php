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


use PDO;
use PDOException;
use PDOStatement;

class FDOMappedQuery
{
    /** @var PDOStatement */
    private $statement;
    /** @var ?array */
    private $params;
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

    private function executeStatement(): void
    {
        switch ($this->className) {
            case 'array':
                $this->statement->setFetchMode(PDO::FETCH_ASSOC);
                break;
            case 'object':
                $this->statement->setFetchMode(PDO::FETCH_OBJ);
                break;
            default:
                $this->statement->setFetchMode(PDO::FETCH_CLASS, $this->className);
                break;
        }

        if ($this->params) {
            foreach ($this->params as $key => $value) {
                if (is_int($key))
                    $this->statement->bindValue($key + 1, $value);
                else
                    $this->statement->bindValue($key, $value);
            }
        }

        if (!$this->statement->execute())
            throw new PDOException("FDOMappedQuery::executeStatement() statement->execute failed");
    }
}
