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


use LogicException;
use PDO;
use PDOException;
use PDOStatement;

class FDOBoundQuery
{
    use FDOQueryTrait;

    /** @var array */
    private $bindMap;

    public function __construct(PDOStatement $statement, ?array $params, array $bindMap)
    {
        $this->statement = $statement;
        $this->params = $params;
        $this->bindMap = $bindMap;
    }

    public function single(): void
    {
        $this->executeStatement();

        $row = $this->statement->fetch();
        if ($row === false)
            throw new LogicException("FDOBoundQuery::single() returned no records");

        $next = $this->statement->fetch();
        if ($next !== false)
            throw new LogicException("FDOBoundQuery::single() returned multiple records");

        $this->bindResultRow($row);
    }

    public function singleOrNull(&$fetched): void
    {
        $this->executeStatement();

        $fetched = false;

        $row = $this->statement->fetch();
        if ($row === false) {
            return;
        }

        $next = $this->statement->fetch();
        if ($next !== false)
            throw new LogicException("FDOBoundQuery::singleOrNull() returned multiple records");

        $fetched = true;

        $this->bindResultRow($row);
    }

    private function executeStatement(): void
    {
        reset($this->bindMap);
        $firstKey = key($this->bindMap);
        if (is_int($firstKey))
            $this->statement->setFetchMode(PDO::FETCH_NUM);
        else if (is_string($firstKey))
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
        else
            // This is our own problem since API should guard against it
            throw new LogicException("FDOBoundQuery::executeStatement() bindMap is empty");

        $this->bindParameters();

        if (!$this->statement->execute())
            throw new PDOException("FDOBoundQuery::executeStatement() statement->execute failed");
    }

    private function bindResultRow(array $row)
    {
        foreach ($this->bindMap as $key => &$value) {
            $value = $row[$key];
        }
    }
}
