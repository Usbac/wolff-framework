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
     * The database table to be used
     *
     * @var string
     */
    private $table = self::DEFAULT_TABLE;

    /**
     * The name of the unique column
     * that cannot be repeated
     *
     * @var string
     */
    private $unique_column = null;

    /**
     * The last currently authenticated
     * user's data
     *
     * @var array
     */
    private $last_user = null;

    /**
     * The last inserted user id
     *
     * @var int|null
     */
    private $last_id = null;


    /**
     * Initializes the database connection for
     * the authentication utility
     *
     * @param  array|null  $credentials  the database credentials
     * @param  array|null  $options  the PDO connection options
     */
    public function __construct(array $credentials = null, array $options = null)
    {
        if (isset($options)) {
            $this->options = $options;
        }

        parent::__construct($credentials, $this->options);
    }


    /**
     * Sets the database table to be used
     *
     * @param  string  $table  the database table
     */
    public function setTable(string $table = self::DEFAULT_TABLE): void
    {
        $this->table = $this->escape($table);
    }


    /**
     * Sets the password hash options
     *
     * @param  array  $options  the password hash options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }


    /**
     * Returns the password hash options
     *
     * @return array the password hash options
     */
    public function getOptions(): array
    {
        return $this->options;
    }


    /**
     * Returns the last inserted id
     *
     * @return int|null the last inserted id
     */
    public function getId(): ?int
    {
        return $this->last_id;
    }


    /**
     * Returns the currently authenticated
     * user's data
     *
     * @return array|null the currently authenticated
     * user's data
     */
    public function getUser(): ?array
    {
        return $this->last_user;
    }


    /**
     * Sets the unique column that cannot
     * be repeated.
     *
     * @param  string  $unique_column  the unique column name
     */
    public function setUnique(string $unique_column): void
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
    public function login(array $data): bool
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

        $conditions = implode(' AND ', $conditions);

        $stmt = $this->connection->prepare("SELECT * FROM {$this->table} WHERE $conditions");
        $stmt->execute($data);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->last_user = $this->isValidUser($user, $password) ? $user : null;
        return isset($this->last_user);
    }


    /**
     * Register a new user into the database.
     * The only required values in the given array
     * are 'password' and 'password_confirm'.
     *
     * @throws \Wolff\Exception\InvalidArgumentException
     *
     * @param  array  $data  the array containing the user data
     *
     * @return bool true if the registration has been successfully made,
     * false otherwise
     */
    public function register(array $data): bool
    {
        if (empty($data) || !isAssoc($data)) {
            throw new InvalidArgumentException('data', 'a non-empty associative array');
        } elseif (!$this->passwordMatches($data)) {
            return false;
        }

        unset($data['password_confirm']);
        $data['password'] = $this->getPassword($data['password']);

        //Repeated user
        if (isset($this->unique_column)) {
            $stmt = $this->connection->prepare("SELECT * FROM $this->table WHERE $this->unique_column = ?");
            $stmt->execute([ $data[$this->unique_column] ]);

            if ($stmt->fetch(\PDO::FETCH_ASSOC) !== false) {
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
     * Returns true if the password in the given user array matches
     * the given password, false otherwise
     *
     * @param  mixed  $user  the user array
     * @param  mixed  $password  the password to match
     *
     * @return bool true if the password in the given user array matches
     * the given password, false otherwise
     */
    private function isValidUser($user, $password): bool
    {
        return is_array($user) &&
            array_key_exists('password', $user) &&
            password_verify($password, $user['password']);
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
    private function passwordMatches(array $data): bool
    {
        return array_key_exists('password', $data) &&
            array_key_exists('password_confirm', $data) &&
            is_string($data['password']) &&
            is_string($data['password_confirm']) &&
            $data['password'] === $data['password_confirm'];
    }


    /**
     * Returns the hashed password with the current options
     * and using the BCRYPT algorithm
     *
     * @param  string  $password  the password to hash
     *
     * @return string the hashed password
     */
    private function getPassword(string $password): string
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
    private function insertUser(array $data): bool
    {
        $array_keys = array_keys($data);

        $values = [];
        foreach ($array_keys as $key) {
            $values[] = ":$key";
        }

        $keys = implode(', ', $array_keys);
        $values = implode(', ', $values);

        $stmt = $this->connection->prepare("INSERT INTO $this->table ($keys) VALUES ($values)");

        return $stmt->execute($data);
    }
}
