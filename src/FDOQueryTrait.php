<?php


namespace GoFinTech\FluentPdo;


use DateTimeInterface;
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
        if ($value instanceof DateTimeInterface)
            $value = $value->format(DATE_ISO8601);

        $this->statement->bindValue($key, $value);
    }
}
