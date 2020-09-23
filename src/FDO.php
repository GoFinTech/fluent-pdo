<?php

/*
 * This file is part of the Fluent PDO package.
 *
 * (c) 2019,2020 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GoFinTech\FluentPdo;


use DateTimeInterface;
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

    public function immediate(string $statement, array $params = []): void 
    {
        $this->query($statement)->params($params)->execute();
    }

    public function beginTransaction(): FDOTransaction
    {
        return new FDOTransaction($this->pdo);
    }

    /**
     * Helper for building INSERT statements.
     *
     * Single record example:
     * $fdo->insert('comment', ['text' => 'hello'])->execute();
     *
     * Multiple records example:
     * $fdo->insert('comment', ['text'])
     *  ->append(['text'=>'hello'])
     *  ->append(['text'=>'world'])
     *  ->execute();
     *
     * The following $options are still supported
     * but please consider using the constructs above
     * and extend them as {@see FDOInsert} permits.
     *
     * Adding RETURNING clause:
     *  $options = [
     *      'returning' => ['field1', 'field2']
     *  ];
     *
     * Adding ON CONFLICT clause:
     *  $options = [
     *      'onConflict' => [
     *          'of' => ['field1', 'field2'],
     *          'update' => ['field3', 'field4'] // update only selected fields
     *      ]
     *  ];
     *  $options = [
     *      'onConflict' => [
     *          'of' => ['field1', 'field2'],
     *          'update' => [] // equivalent to DO NOTHING
     *      ]
     *  ];
     *  $options = [
     *      'onConflict' => [
     *          'of' => ['field1', 'field2'],
     *          'update' => '*' // update all fields that are not in 'of'
     *      ]
     *  ];
     *
     * @param string $table target table name
     * @param array $values field values ['field_name' => $value]
     * @param array|null $options Additional feature control
     *
     * @return FDOInsert|FDOQuery query is returned if 'returning' option is specified
     */
    public function insert(string $table, array $values, ?array $options = null)
    {
        if (!$values)
            throw new InvalidArgumentException("FDO::insert() values array cannot be empty");

        reset($values);
        if (is_int(key($values))) {
            $insert = new FDOInsert($this, $table, $values);
        }
        else {
            $insert = new FDOInsert($this, $table, array_keys($values));
            $insert->append($values);
        }

        if (isset($options['onConflict'])) {
            $insert->onConflict($options['onConflict']['of'],  $options['onConflict']['update']);
        }

        if (isset($options['returning'])) {
            return $insert->returning($options['returning']);
        }
        else {
            return $insert;
        }
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

    public function quoteValue($value): string
    {
        $valueType = gettype($value);
        switch ($valueType) {
            case 'NULL':
                return 'NULL';
            case 'string':
                return $this->pdo->quote($value);
            case 'integer':
            case 'double':
                return (string)$value;
            case 'boolean':
                return $value ? 'TRUE' : 'FALSE';
            case 'object':
                if ($value instanceof DateTimeInterface)
                    return $this->pdo->quote($value->format(DATE_ATOM));
                else
                    return $this->pdo->quote("$value");
            default:
                throw new InvalidArgumentException("FDO::quoteValue() unsupported value type $valueType");
        }

    }
}
