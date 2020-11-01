<?php

namespace Wolff\Core\Http;

interface ResponseInterface
{

    /**
     * Default constructor
     */
    public function __construct();


    /**
     * Sets the response content
     *
     * @param  mixed  $content  the content
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function write($content);


    /**
     * Appends content to the response content
     *
     * @param  mixed  $content  the content
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function append($content);


    /**
     * Returns the response content
     *
     * @return string the response content
     */
    public function get();


    /**
     * Sets the value of a header
     * If the header exists, it will be overwritten
     *
     * @param  string  $key  the header key
     * @param  string  $value  the header value
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function setHeader(string $key, string $value);


    /**
     * Sets the HTTP status code
     *
     * @param  int  $status  the HTTP status code
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function setCode(int $status);


    /**
     * Returns the HTTP status code
     *
     * @return int the HTTP status code
     */
    public function getCode();


    /**
     * Sets a cookie
     *
     * @param  string  $key  the cookie key
     * @param  string  $value  the cookie value
     * @param  mixed  $time  the cookie time
     * @param  string  $path  the path where the cookie will work
     * @param  string  $domain the cookie domain
     * @param  bool  $secure  only available through https or not
     * @param  bool  $http_only  only available through http protocol or not,
     * this will hide the cookie from scripting languages like JS
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function setCookie(
        string $key,
        string $value,
        $time = null,
        string $path = '',
        string $domain = '',
        bool $secure = false,
        bool $http_only = true
    );


    /**
     * Removes a cookie
     *
     * @param  string  $key  the cookie key
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function unsetCookie(string $key);


    /**
     * Sends the response with the available values
     *
     * @param  bool  $return  return or not the response content
     *
     * @return string|void the response content
     */
    public function send(bool $return = false);
}
