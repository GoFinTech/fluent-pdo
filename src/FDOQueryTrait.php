<?php


namespace GoFinTech\FluentPdo;


use DateTimeInterface;
use PDO;
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
                $this->bindValue($key + 1, $value);
            else
                $this->bindValue($key, $value);
        }
    }

    private function bindValue($key, $value): void
    {
        if (is_bool($value)) {
            $this->statement->bindValue($key, $value, PDO::PARAM_BOOL);
        }
        else if (is_object($value) && $value instanceof DateTimeInterface) {
            $this->statement->bindValue($key, $value->format(DATE_ATOM));
        }
        else {
            $this->statement->bindValue($key, $value);
        }
    }
}
