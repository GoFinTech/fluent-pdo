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
use PDO;
use PDOException;

class FDO
{
    /** @var PDO */
    private $pdo;

    /**
     * FDO constructor.
     * @param string|PDO $pdoOrDsn
     * @param string|null $username
     * @param string|null $password
     */
    public function __construct($pdoOrDsn, ?string $username = null, ?string $password = null)
    {
        if (is_string($pdoOrDsn)) {
            $this->pdo = new PDO($pdoOrDsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } else if ($pdoOrDsn instanceof PDO) {
            $this->pdo = $pdoOrDsn;
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            throw new InvalidArgumentException(
                "FDO: first argument needs to be PDO or string, got " . gettype($pdoOrDsn)
            );
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $t): FDOQuery
    {
        $statement = $this->pdo->prepare($t);
        if ($statement === false)
            throw new PDOException("FDO::query() statement prepare failed");
        return new FDOQuery($statement);
    }
}
