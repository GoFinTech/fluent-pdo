<?php

/*
 * This file is part of the Fluent PDO package.
 *
 * (c) 2020 Go Financial Technologies, JSC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/* @noinspection SqlNoDataSourceInspection */

namespace GoFinTech\FluentPdo;

use InvalidArgumentException;
use LogicException;

/**
 * Helper class for constructing INSERT statements.
 * Should NOT be used directly. See {@see FDO::insert()} instead.
 *
 * @package GoFinTech\FluentPdo
 */
class FDOInsert
{
    /** @var FDO */
    private $fdo;
    /** @var string */
    private $table;
    /** @var string[] */
    private $fields;
    /** @var string[] values clause(s) */
    private $values;
    /** @var string */
    private $onConflictClause;
    /** @var string */
    private $returningClause;

    /**
     * FDOInsert constructor.
     * @internal Preferred use is through {@see FDO::insert()}
     * @param FDO $fdo
     * @param string $table table name
     * @param string[] $fields field list to use
     */
    public function __construct(FDO $fdo, string $table, array $fields)
    {
        $this->fdo = $fdo;
        $this->table = $table;
        $this->fields = $fields;
    }

    /**
     * Appends row data (VALUES clause)
     * @param array $row record data, indexed by column name ['column' => 'value', ...]
     * @return $this for method chaining
     */
    public function append(array $row): FDOInsert
    {
        $sql = ['('];
        $first = true;
        foreach ($this->fields as $field) {
            if (!$first) $sql[] = ',';
            if (array_key_exists($field, $row))
                $sql[] = $this->fdo->quoteValue($row[$field]);
            else
                $sql[] = 'DEFAULT';
            $first = false;
        }
        $sql[] = ')';

        $this->values[] = implode($sql);

        return $this;
    }

    /**
     * Appends multiple rows (VALUES clause)
     * @param mixed $list anything iterable by foreach
     * @return $this for method chaining
     */
    public function appendAll($list): FDOInsert
    {
        foreach ($list as $row) {
            $this->append($row);
        }
        return $this;
    }

    /**
     * Adds an ON CONFLICT clause
     * @param string[] $of list of conflict columns (should match unique constraint(s))
     * @param string|string[] $update list of columns to update or '*' for all; empty array means 'do nothing'
     * @return $this for method chaining
     */
    public function onConflict(array $of, $update): FDOInsert
    {
        if (isset($this->onConflictClause))
            throw new LogicException("FDOInsert::onConflict() can only be called once");

        $sql = [];

        $sql[] = ' on conflict ';
        if ($of) {
            $sql[] = '(';
            $first = true;
            foreach ($of as $field) {
                if (!$first) $sql[] = ',';
                $sql[] = FDO::quoteIdentifier($field);
                $first = false;
            }
            $sql[] = ') ';
        }
        if (is_array($update)) {
            if ($update) {
                $sql[] = ' do update set ';
                $first = true;
                foreach ($update as $field) {
                    if (!$first) $sql[] = ',';
                    $quoted = FDO::quoteIdentifier($field);
                    $sql[] = $quoted;
                    $sql[] = '=';
                    $sql[] = "excluded.$quoted";
                    $first = false;
                }
            }
            else {
                $sql[] = ' do nothing';
            }
        }
        else if ($update == '*') {
            $ofNames = array_flip($of);
            $sql[] = ' do update set ';
            $first = true;
            foreach ($this->fields as $field) {
                if (array_key_exists($field, $ofNames))
                    continue;
                if (!$first) $sql[] = ',';
                $quoted = FDO::quoteIdentifier($field);
                $sql[] = $quoted;
                $sql[] = '=';
                $sql[] = "excluded.$quoted";
                $first = false;
            }
        }
        else {
            throw new InvalidArgumentException("FDOInsert::onConflict() invalid update option value");
        }

        $this->onConflictClause = implode($sql);

        return $this;
    }

    /**
     * Adds a RETURNING clause
     * @param string[] $fields list of fields to return
     * @return FDOQuery a compiled query that will return the requested dataset
     */
    public function returning(array $fields): FDOQuery
    {
        $sql = [' returning '];
        $first = true;
        foreach ($fields as $field) {
            if (!$first) $sql[] = ',';
            $sql[] = FDO::quoteIdentifier($field);
            $first = false;
        }
        $this->returningClause = implode($sql);

        return $this->query();
    }

    /**
     * Compiles the constructed INSERT statement into a query
     * @return FDOQuery
     */
    public function query(): FDOQuery
    {
        $sql = ['insert into ' . FDO::quoteIdentifier($this->table) . ' ('];
        $first = true;
        foreach ($this->fields as $name) {
            if (!$first) $sql[] = ',';
            $sql[] = FDO::quoteIdentifier($name);
            $first = false;
        }
        $sql[] = ') values ';
        $sql[] = implode(',', $this->values);

        if (isset($this->onConflictClause))
            $sql[] = $this->onConflictClause;

        if (isset($this->returningClause))
            $sql[] = $this->returningClause;

        return $this->fdo->query(implode($sql));
    }

    /**
     * Compiles and executes the constructed INSERT statement
     */
    public function execute(): void
    {
        $this->query()->execute();
    }
}
