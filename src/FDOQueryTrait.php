<?php


namespace GoFinTech\FluentPdo;


use PDOStatement;

trait FDOQueryTrait
{
    /** @var PDOStatement */
    private $statement;
    /** @var ?array */
    private $params;

    private function bindParameters(): void
    {
        if (!$this->params)
            return;

        foreach ($this->params as $key => $value) {
            if (is_int($key))
                $this->statement->bindValue($key + 1, $value);
            else
                $this->statement->bindValue($key, $value);
        }
    }
}
