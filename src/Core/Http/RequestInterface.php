<?php

namespace Wolff\Core\Http;

interface RequestInterface
{

    /**
     * Constructor
     *
     * @param  array  $query  the url parameters
     * @param  array  $body  the body parameters
     * @param  array  $files  the files
     * @param  array  $server  the super global server
     * @param  array  $cookies  the cookies
     */
    public function __construct(
        array $query,
        array $body,
        array $files,
        array $server,
        array $cookies
    );


    /**
     * Returns the specified parameter
     *
     * @param  string|null  $key  the parameter key
     *
     * @return mixed the specified parameter
     */
    public function query(string $key = null);


    /**
     * Returns true if the specified parameter is set, false otherwise
     *
     * @param  string  $key  the parameter key
     *
     * @return bool true if the specified parameter is set, false otherwise
     */
    public function hasQuery(string $key);


    /**
     * Returns the specified body parameter
     *
     * @param  string|null  $key  the body parameter key
     *
     * @return mixed the specified body parameter
     */
    public function body(string $key = null);


    /**
     * Returns true if the specified body parameter is set,
     * false otherwise
     *
     * @param  string  $key  the parameter key
     *
     * @return bool true if the specified body parameter is set,
     * false otherwise
     */
    public function has(string $key);


    /**
     * Returns the specified file
     *
     * @param  string|null  $key  the file key
     *
     * @return mixed the specified file
     */
    public function file(string $key = null);


    /**
     * Returns true if the specified file is set, false otherwise
     *
     * @param  string  $key  the parameter key
     *
     * @return bool true if the specified file is set, false otherwise
     */
    public function hasFile(string $key);


    /**
     * Returns the specified cookie
     *
     * @param  string|null  $key  the cookie key
     *
     * @return mixed the specified
     */
    public function cookie(string $key = null);


    /**
     * Returns true if the specified cookie is set, false otherwise
     *
     * @param  string  $key  the parameter key
     *
     * @return bool true if the specified cookie is set, false otherwise
     */
    public function hasCookie(string $key);


    /**
     * Returns the headers array, or the specified header key
     *
     * @param  string|null  $key  the header key to get
     *
     * @return mixed the headers array, or the specified header key
     */
    public function getHeader(string $key = null);


    /**
     * Returns the request method
     *
     * @return string the request method
     */
    public function getMethod();


    /**
     * Returns the request uri
     *
     * @return string the request uri
     */
    public function getUri();


    /**
     * Returns the full request uri
     *
     * @return string the request uri
     */
    public function getFullUri();


    /**
     * Returns true if the current protocol is secure (https), false otherwise
     *
     * @return bool true if the current protocol is secure (https),
     * false otherwise
     */
    public function isSecure();
}
