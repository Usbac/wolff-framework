<?php

namespace Wolff\Core;

use PDO;
use PDOException;
use Wolff\Exception\InvalidArgumentException;

class DB
{

    const DEFAULT_OPTIONS = [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION
    ];

    /**
     * The default connection credentials
     */
    protected static $credentials;

    /**
     * DB connection
     *
     * @var PDO
     */
    protected $connection;

    /**
     * The last query executed
     *
     * @var string
     */
    protected $last_sql;

    /**
     * The arguments of the last query executed
     *
     * @var array
     */
    protected $last_args;

    /**
     * The last PDO statement executed
     *
     * @var \PDOStatement
     */
    protected $last_stmt;


    /**
     * Initializes the database connection
     *
     * @param  array|null  $data  the array containing database authentication data
     * @param  array|null  $options  the PDO connection options
     */
    public function __construct(array $data = null, array $options = null)
    {
        $this->connection = self::getConnection(
            $data ?? self::$credentials,
            $options ?? self::DEFAULT_OPTIONS
        );
    }


    /**
     * Returns a PDO connection
     *
     * @throws \PDOException
     *
     * @param  array  $data  the data to connect to the database
     * @param  array  $options  the connection options
     *
     * @return PDO|null the PDO connection
     */
    private static function getConnection(array $data, array $options): ?PDO
    {
        if (empty($data['dsn']) || empty($options)) {
            return null;
        }

        return new PDO($data['dsn'],
            $data['username'] ?? null,
            $data['password'] ?? null,
            $options);
    }


    /**
     * Sets the connection default credentials
     *
     * @param  array  $data  the connection default credentials
     */
    public static function setCredentials(array $data): void
    {
        self::$credentials = $data;
    }


    /**
     * Returns the PDO connection
     *
     * @return PDO the PDO connection
     */
    public function getPdo(): ?PDO
    {
        return $this->connection;
    }


    /**
     * Returns the last query executed
     *
     * @return string the last query executed
     */
    public function getLastSql(): string
    {
        return $this->last_sql;
    }


    /**
     * Returns the arguments of the last query executed
     *
     * @return array the arguments of the last query executed
     */
    public function getLastArgs(): array
    {
        return $this->last_args;
    }


    /**
     * Returns the last prepared PDO statement
     *
     * @return \PDOStatement the last prepared PDO statement
     */
    public function getLastStmt(): \PDOStatement
    {
        return $this->last_stmt;
    }


    /**
     * Returns the last inserted id in the database
     *
     * @return string the last inserted id in the database
     */
    public function getLastId(): string
    {
        return $this->connection->lastInsertId();
    }


    /**
     * Returns the number of rows affected by the last query
     *
     * @return int|null the number of rows affected by the last query
     */
    public function getAffectedRows(): ?int
    {
        return $this->last_stmt ? $this->last_stmt->rowCount() : null;
    }


    /**
     * Proxy to native PDO methods
     *
     * @param  mixed  $method  the method name
     * @param  mixed  $args  the method arguments
     *
     * @return mixed the function result
     */
    public function __call($method, $args)
    {
        return call_user_func_array([ $this->connection, $method ], $args);
    }


    /**
     * Returns a query result object
     *
     * @param  string  $sql  the query
     * @param  mixed  ...$args  the arguments
     *
     * @return mixed the query result object
     */
    public function query(string $sql, ...$args)
    {
        $this->last_sql = $sql;
        $this->last_args = $args;

        if (!$args) {
            $this->last_stmt = $this->connection->query($sql);
        } else {
            $this->last_stmt = $this->connection->prepare($sql);
            $this->last_stmt->execute($args);
        }

        return new Query($this->last_stmt);
    }


    /**
     * Runs the last query executed
     *
     * @return mixed the last query result
     */
    public function runLastSql()
    {
        return $this->query($this->getLastSql(), ...$this->getLastArgs());
    }


    /**
     * Returns true if the specified table exists in the database, false otherwise
     *
     * @param  string  $table  the table name
     *
     * @return bool true if the specified table exists in the database, false otherwise
     */
    public function tableExists(string $table): bool
    {
        $table = $this->escape($table);

        try {
            return $this->connection->query("SELECT 1 FROM $table LIMIT 1") !== false;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Returns true if the specified column and table exists in the table of the database,
     * false otherwise
     *
     * @param  string  $table  the table name
     * @param  string  $column  the column name
     *
     * @return bool true if the specified column and table exists in the table of the database,
     * false otherwise
     */
    public function columnExists(string $table, string $column): bool
    {
        $table = $this->escape($table);
        $column = $this->escape($column);

        try {
            $query = $this->connection->query("SELECT COUNT($column) FROM $table");

            return is_bool($query) ? false : !empty($query->fetchAll());
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * Inserts an array into the specified table
     * or null in case of errors
     *
     * @throws \Wolff\Exception\InvalidArgumentException
     *
     * @param  string  $table  the table for the query
     * @param  array  $data  the data to insert
     *
     * @return mixed the query result object or null in
     * case of errors
     */
    public function insert(string $table, array $data)
    {
        if (empty($data) || !Helper::isAssoc($data)) {
            throw new InvalidArgumentException('data', 'a non-empty associative array');
        }

        $table = $this->escape($table);
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(1, count($data), '?'));

        return $this->query("INSERT INTO $table ($columns) VALUES ($values)", ...array_values($data));
    }


    /**
     * Returns the result of a select query, this method accepts dot notation
     * WARNING: The conditions parameter must be manually escaped
     *
     * @param  string  $table  the table for the query
     * @param  string|null  $conds  the select conditions
     * @param  mixed  ...$args the query arguments
     *
     * @return array the query result as an associative array
     */
    public function select(string $table, string $conds = '1', ...$args)
    {
        $arr = explode('.', $table);
        $table = $this->escape($arr[0]);

        $this->last_stmt = $this->connection->prepare("SELECT * FROM $table WHERE $conds");
        $this->last_stmt->execute($args);

        if (isset($arr[1])) {
            $column = $this->escape($arr[1]);
            return array_column($this->last_stmt->fetchAll(), $column);
        }

        return $this->last_stmt->fetchAll();
    }


    /**
     * Returns the result of a SELECT COUNT(*) query
     * WARNING: The conditions parameter must be manually escaped
     *
     * @param  string  $table  the table for the query
     * @param  string|null  $conds  the select conditions
     * @param  mixed  ...$args the query arguments
     *
     * @return int the query result
     */
    public function count(string $table, string $conds = '1', ...$args): int
    {
        $this->last_stmt = $this->connection->prepare("SELECT COUNT(*) FROM $table WHERE $conds");
        $this->last_stmt->execute($args);

        $result = $this->last_stmt->fetchAll();

        return empty($result) ? 0 : $result[0]['COUNT(*)'];
    }


    /**
     * Moves rows from one table to another, deleting the rows of the
     * original table in the process
     * WARNING: The conditions parameter must be manually escaped
     * NOTE: In case of errors, the changes are completely rolled back
     *
     * @param  string  $src_table  the source table
     * @param  string  $dest_table  the destination table
     * @param  string  $conds  the conditions
     * @param  array|null  $args the query arguments
     *
     * @return bool true if the transaction has been made successfully, false otherwise
     */
    public function moveRows(string $src_table, string $dest_table, string $conds = '1', array $args = null): bool
    {
        $src_table = $this->escape($src_table);
        $dest_table = $this->escape($dest_table);

        try {
            $insert_stmt = $this->connection->prepare("INSERT INTO $dest_table
                SELECT * FROM $src_table WHERE $conds");
            $delete_stmt = $this->connection->prepare("DELETE FROM $src_table WHERE $conds");

            $this->connection->beginTransaction();

            $insert_stmt->execute($args);
            $delete_stmt->execute($args);

            $this->connection->commit();
        } catch (\Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollback();
            }

            return false;
        }

        return true;
    }


    /**
     * Runs a delete query
     * WARNING: The conditions parameter must be manually escaped
     *
     * @param  string  $table  the table for the query
     * @param  string|null  $conditions  the select conditions
     * @param  mixed  ...$args  the query arguments
     *
     * @return bool true in case of success, false otherwise
     */
    public function delete(string $table, string $conditions = '1', ...$args): bool
    {
        $table = $this->escape($table);

        $this->last_stmt = $this->connection->prepare("DELETE FROM $table WHERE $conditions");

        return $this->last_stmt->execute($args);
    }


    /**
     * Returns the string escaped.
     * Any character that is not a letter, number or underscore is removed
     *
     * @param  string  $str  the string
     *
     * @return string the string escaped
     */
    protected function escape(string $str): string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $str);
    }
}
