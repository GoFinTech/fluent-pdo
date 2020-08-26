<?php

/*
 * This file is part of the Fluent PDO package.
 *
 * (c) 2020 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\FluentPdo;


use LogicException;
use PDO;


class FDOTransaction
{
    /** @var PDO */
    private $pdo;
    /** @var bool */
    private $commit;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->beginTransaction();
    }

    public function __destruct()
    {
        $this->close();
    }

    public function commit(): void
    {
        if (isset($this->pdo)) {
            $this->pdo->commit();
            $this->pdo = null;
            $this->commit = true;
        }
        else if (!$this->commit) {
            throw new LogicException("FDOTransaction::commit() called after rollback()");
        }
    }

    public function rollback(): void
    {
        if (isset($this->pdo)) {
            $this->pdo->rollBack();
            $this->pdo = null;
            $this->commit = false;
        }
        else if ($this->commit) {
            throw new LogicException("FDOTransaction::rollback() called after commit()");
        }
    }

    public function close(): void
    {
        if (isset($this->pdo)) {
            $this->pdo->rollBack();
            $this->pdo = null;
            $this->commit = false;
        }
    }
}
