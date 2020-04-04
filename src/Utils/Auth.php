<?php

namespace Wolff\Utils;

use Wolff\Exception\InvalidArgumentException;

final class Auth extends \Wolff\Core\DB
{

    const DEFAULT_TABLE = 'user';

    /**
     * The password hashing options
     *
     * @var array
     */
    private $options = [
        'cost' => '10',
    ];

    /**
     * The database table to be used.
     *
     * @var string
     */
    private $table = self::DEFAULT_TABLE;

    /**
     * The name of the unique column
     * that cannot be repeated.
     *
     * @var string
     */
    private $unique_column = null;

    /**
     * The last currently authenticated
     * user's data.
     *
     * @var array
     */
    private $last_user = null;

    /**
     * The last inserted user id.
     *
     * @var int
     */
    private $last_id = 0;


    /**
     * Initializes the database connection for
     * the authentication utility
     *
     * @param  array|null  $data  The array containing database authentication data
     * @param  array|null  $options  The PDO connection options
     */
    public function __construct(array $data = null, array $options = null)
    {
        parent::__construct($data, $options);
    }


    /**
     * Sets the database table to be used
     *
     * @param  string  $table  the database table
     */
    public function setTable(string $table = self::DEFAULT_TABLE)
    {
        $this->table = $this->escape($table);
    }


    /**
     * Sets the password hash options
     *
     * @param  array  $options  the password hash options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }


    /**
     * Returns the password hash options
     *
     * @return array the password hash options
     */
    public function getOptions()
    {
        return $this->options;
    }


    /**
     * Returns the last inserted id
     *
     * @return int the last inserted id
     */
    public function getId()
    {
        return $this->last_id;
    }


    /**
     * Returns the currently authenticated
     * user's data
     *
     * @return array the currently authenticated
     * user's data
     */
    public function getUser()
    {
        return $this->last_user;
    }


    /**
     * Sets the unique column that cannot
     * be repeated.
     *
     * @param string $unique_column the unique column name
     */
    public function setUnique(string $unique_column)
    {
        $this->unique_column = $unique_column;
    }


    /**
     * Returns true if the given user data exists in the database
     * and is valid, false otherwise.
     * This method updates the current user data array.
     *
     * @throws \Wolff\Exception\InvalidArgumentException
     *
     * @param  array  $data  the array containing the user data
     *
     * @return bool true if the given user data exists in the database
     * and is valid, false otherwise
     */
    public function login(array $data)
    {
        if (empty($data) || !isAssoc($data) || !array_key_exists('password', $data)) {
            throw new InvalidArgumentException(
                'data',
                'a non-empty associative array with at least a \'password\' key'
            );
        }

        $password = $data['password'];
        unset($data['password']);

        $conditions = [];
        foreach (array_keys($data) as $key) {
            $conditions[] = "$key = :$key";
        }

        $stmt = $this->connection->prepare("SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $conditions));
        $stmt->execute($data);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $valid = is_array($user) &&
            array_key_exists('password', $user) &&
            password_verify($password, $user['password']);

        $this->last_user = $valid ? $user : null;
        return $valid;
    }


    /**
     * Register a new user into the database.
     * The only required values in the given array
     * are 'password' and 'password_confirm'
     *
     * @throws \Wolff\Exception\InvalidArgumentException
     *
     * @param  array  $data  the array containing the user data
     *
     * @return bool True if the registration has been successfully made,
     * false otherwise
     */
    public function register(array $data)
    {
        if (empty($data) || !isAssoc($data)) {
            throw new InvalidArgumentException('data', 'a non-empty associative array');
        }

        if (!$this->passwordMatches($data)) {
            return false;
        }

        unset($data['password_confirm']);
        $data['password'] = $this->getPassword($data['password']);

        //Repeated user
        if (isset($this->unique_column)) {
            $column = $this->unique_column;
            $stmt = $this->connection->prepare("SELECT * FROM $this->table WHERE $column = ?");
            $stmt->execute([ $data[$column] ]);

            if (!empty($stmt->fetch(\PDO::FETCH_ASSOC))) {
                return false;
            }
        }

        if ($this->insertUser($data)) {
            $this->last_id = (int)$this->connection->lastInsertId();
            return true;
        }

        return false;
    }


    /**
     * Returns true if the 'password' and the 'password_confirm' values
     * of the given array are equal, false otherwise
     *
     * @param  array  $data  the array containing the 'password'
     * and 'confirm_password' values
     *
     * @return bool true if the 'password' and the 'password_confirm' values
     * of the given array are equal, false otherwise
     */
    private function passwordMatches(array $data)
    {
        return (array_key_exists('password', $data) &&
                array_key_exists('password_confirm', $data) &&
                is_string($data['password']) &&
                is_string($data['password_confirm']) &&
                $data['password'] === $data['password_confirm']);
    }


    /**
     * Returns the hashed password with the current options
     * and using the BCRYPT algorithm
     *
     * @param  string  $password  the password to hash
     *
     * @return string the hashed password
     */
    private function getPassword(string $password)
    {
        return password_hash($password, PASSWORD_BCRYPT, $this->options);
    }


    /**
     * Inserts the given values into the database
     *
     * @param  array  $data  the array containing the data
     *
     * @return bool true if the insertion has been successfully made,
     * false otherwise
     */
    private function insertUser($data)
    {
        $array_keys = array_keys($data);

        $values = [];
        foreach ($array_keys as $key) {
            $values[] = ":$key";
        }

        $keys = implode(', ', $array_keys);
        $values = implode(', ', $values);

        $stmt = $this->connection->prepare("INSERT INTO `$this->table` ($keys) VALUES ($values)");

        return $stmt->execute($data);
    }
}
