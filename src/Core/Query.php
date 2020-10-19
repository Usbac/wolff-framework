<?php

namespace Wolff\Core;

use PDOStatement;

final class Query
{

    /**
     * The query statement
     *
     * @var PDOStatement
     */
    private $stmt;


    /**
     * Default constructor
     *
     * @param  PDOStatement  $stmt  the PDO statement
     */
    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }


    /**
     * Returns the query results
     *
     * @return array the query results
     */
    public function get()
    {
        return $this->stmt->fetchAll();
    }


    /**
     * Returns the query results as a Json
     *
     * @return string the query results as a Json
     */
    public function getJson(): string
    {
        return json_encode($this->get());
    }


    /**
     * Returns the first element of the query results
     * or only the specified column of the first element
     *
     * @param  string|null  $column  the column name to pick
     *
     * @return array the first element of the query results,
     * or only the specified column of the first element
     */
    public function first(string $column = null)
    {
        $first = $this->get()[0] ?? null;

        return isset($first, $column) ?
            $first[$column] :
            $first;
    }


    /**
     * Returns only the specified column/s of the query result
     *
     * @param mixed ...$columns  the columns to pick
     *
     * @return array only the specified column/s of the query result
     */
    public function pick(...$columns)
    {
        $rows = [];
        $result = $this->get();

        //Only one column to pick
        if (count($columns) === 1) {
            return array_column($result, $columns[0]);
        }

        //Multiple columns to pick
        foreach ($result as $row) {
            $new_row = [];
            foreach ($columns as $column) {
                if (array_key_exists($column, $row)) {
                    $new_row[$column] = $row[$column];
                }
            }

            array_push($rows, $new_row);
        }

        return $rows;
    }


    /**
     * Returns the number of rows in the query results
     *
     * @return int the number of rows in the query results
     */
    public function count(): int
    {
        return count($this->get());
    }


    /**
     * Returns the query result sliced
     *
     * @param  int  $start  the offset
     * @param  int  $end  the length
     *
     * @return array the query result sliced
     */
    public function limit(int $start, int $end)
    {
        return array_slice($this->get(), $start, $end);
    }


    /**
     * Var dump the query results
     */
    public function dump(): void
    {
        var_dump($this->get());
    }


    /**
     * Prints the query results in a nice looking way
     */
    public function printr(): void
    {
        echo '<pre>';
        array_map('print_r', $this->get());
        echo '</pre>';
    }


    /**
     * Var dump the query results and die
     */
    public function dumpd(): void
    {
        array_map('var_dump', $this->get());
        die;
    }


    /**
     * Prints the query results in a nice looking way and die
     */
    public function printrd(): void
    {
        $this->printr();
        die;
    }
}
