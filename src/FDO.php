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

    /**
     * Helper for building INSERT INTO statements.
     * 
     * @param string $table target table name
     * @param array $values field values ['field_name' => $value]
     * @param array|null $options additional feature control
     * 
     * @example
     *  $fdo->insert('order', ['customer' => 7], [
     *      'returning' => ['id']
     *  ]);
     * 
     * @return FDOQuery
     */
    public function insert(string $table, array $values, ?array $options = null): FDOQuery
    {
        $sql = ['insert into ' . self::quoteIdentifier($table) . ' ('];
        $params = [];
        $bindGroup = [];
        $i = 0;
        foreach ($values as $name => $value) {
            if ($i > 0) {
                $sql[] = ',';
                $bindGroup[] = ',';
            }
            $sql[] = self::quoteIdentifier($name);
            $paramName = ":p$i";
            $params[$paramName] = $value;
            $bindGroup[] = $paramName;
            $i++;
        }
        $sql[] = ') values (' . implode($bindGroup) . ')';
        if (isset($options['returning'])) {
            $sql[] = ' returning ';
            $first = true;
            foreach ($options['returning'] as $field) {
                if (!$first) {
                    $sql[] = ',';
                }
                $sql[] = self::quoteIdentifier($field);
                $first = false;
            }
        }
        return $this->query(implode($sql))->params($params);
    }
    
    public static function quoteIdentifier(string $name): string 
    {
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name))
            return $name;
        // Support schema reference with a dot
        $parts = explode('.', $name, 2);
        foreach ($parts as &$part) {
            $part = '"' . str_replace('"', '""', $part) . '"'; 
        }
        return implode('.', $parts);
    }
}
