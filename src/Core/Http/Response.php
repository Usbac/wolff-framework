<?php

namespace Wolff\Core\Http;

class Response
{

    const COOKIE_TIMES = [
        'FOREVER' => 157680000, // Five years
        'MONTH'   => 2629743,
        'DAY'     => 86400,
        'HOUR'    => 3600
    ];

    /**
     * The response content
     *
     * @var string
     */
    private $content;

    /**
     * The HTTP status code.
     *
     * @var int|null
     */
    private $status_code;

    /**
     * The header location.
     *
     * @var string
     */
    private $url;

    /**
     * The header tag list.
     *
     * @var array
     */
    private $headers;

    /**
     * The cookies list.
     *
     * @var array
     */
    private $cookies;


    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->content = '';
        $this->status_code = null;
        $this->headers = [];
        $this->url = '';
        $this->cookies = [];
    }


    /**
     * Sets the response content
     *
     * @param  mixed  $content  the content
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function write($content)
    {
        $this->content = strval($content);

        return $this;
    }


    /**
     * Appends content to the response content
     *
     * @param  mixed  $content  the content
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function append($content)
    {
        $this->content .= strval($content);

        return $this;
    }


    /**
     * Sets the value of a header
     * If the header exists, it will be overwritten
     *
     * @param  string  $key  the header key
     * @param  string  $value  the header value
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function setHeader(string $key, string $value)
    {
        $this->headers[trim($key)] = $value;

        return $this;
    }


    /**
     * Sets the HTTP status code
     *
     * @param  int  $status  the HTTP status code
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function setCode(int $status)
    {
        $this->status_code = $status;

        return $this;
    }


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
    ) {
        if (isset($time) && array_key_exists($time, self::COOKIE_TIMES)) {
            $time = self::COOKIE_TIMES[strtoupper($time)];
        }

        array_push($this->cookies, [
            'key'       => $key,
            'value'     => $value,
            'time'      => isset($time) ? time() + $time : 0,
            'path'      => $path,
            'domain'    => $domain,
            'secure'    => $secure,
            'http_only' => $http_only
        ]);

        return $this;
    }


    /**
     * Removes a cookie
     *
     * @param  string  $key  the cookie key
     *
     * @return \Wolff\Core\Http\Response this
     */
    public function unsetCookie(string $key)
    {
        array_push($this->cookies, [
            'key'       => $key,
            'value'     => '',
            'time'      => time() - self::COOKIE_TIMES['HOUR'],
            'path'      => '',
            'domain'    => '',
            'secure'    => false,
            'http_only' => false
        ]);

        return $this;
    }


    /**
     * Sends the response with the available values
     */
    public function send()
    {
        if (isset($this->status_code)) {
            http_response_code($this->status_code);
        }

        foreach ($this->headers as $key => $val) {
            header("$key: $val");
        }

        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['key'],
                $cookie['value'],
                $cookie['time'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['http_only']
            );
        }

        print($this->content);
    }
}
