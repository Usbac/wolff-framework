<?php

namespace Wolff\Core\Http;

use Wolff\Exception\InvalidArgumentException;
use Wolff\Exception\FileNotFoundException;

class Request
{

    const DEFAULT_FILE_OPTIONS = [
        'dir'        => '',
        'extensions' => [],
        'max_size'   => 0,
        'override'   => true
    ];

    /**
     * List of parameters
     *
     * @var array
     */
    private $query;

    /**
     * List of body parameters
     *
     * @var array
     */
    private $body;

    /**
     * List of files
     *
     * @var array
     */
    private $files;

    /**
     * List of headers
     *
     * @var array
     */
    private $headers;

    /**
     * Current server superglobal
     *
     * @var array
     */
    private $server;

    /**
     * List of cookies
     *
     * @var array
     */
    private $cookies;

    /**
     * List of options for uploading files
     * @var array
     */
    private $file_options;


    /**
     * Default constructor
     *
     * @param  array  $query  the url parameters
     * @param  array  $body  the body parameters
     * @param  array  $files  the files
     * @param  array  $server  the superglobal server
     * @param  array  $cookies  the cookies
     */
    public function __construct(
        array $query,
        array $body,
        array $files,
        array $server,
        array $cookies
    ) {
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->headers = $this->parseHeaders($server);
        $this->file_options = self::DEFAULT_FILE_OPTIONS;
        $this->files = $this->getFiles($files, $this->file_options);
    }


    /**
     * Returns the headers parsed
     *
     * @param  array  $server  the superglobal server array
     *
     * @return array The headers parsed
     */
    private function parseHeaders(array $server)
    {
        $headers = [];

        foreach ($server as $header => $val) {
            if (strpos($header, 'HTTP_') === 0) {
                $key = ucwords(str_replace('_', '-', strtolower(substr($header, 5))), '-');
                $headers[$key] = $val;
            }
        }

        return $headers;
    }


    /**
     * Returns an array of files instances based on the
     * given files array
     *
     * @param array $arr the array of files
     * @param array $options the array with the file options
     *
     * @return array The array of files instances based on the
     * given files array
     */
    private function getFiles(array $arr, array &$options)
    {
        $files = [];

        foreach ($arr as $key => $val) {
            $files[$key] = new File($val, $options);
        }

        return $files;
    }


    /**
     * Sets the options for uploading the files.
     *
     * @param array $arr the array with the options
     * (dir, extensions, max_size)
     */
    public function fileOptions(array $arr = [])
    {
        if (isset($arr['dir']) && !is_string($arr['dir'])) {
            throw new InvalidArgumentException('dir', 'a string');
        }

        $dir = CONFIG['root_dir'] . '/' . ($arr['dir'] ?? '');
        if (isset($arr['dir']) && !is_dir($dir)) {
            throw new FileNotFoundException($dir);
        }

        if (isset($arr['extensions']) && !is_string($arr['extensions'])) {
            throw new InvalidArgumentException('extensions', 'a string');
        }

        if (isset($arr['max_size']) && !is_numeric($arr['max_size'])) {
            throw new InvalidArgumentException('max_size', 'a numeric value');
        }

        $extensions = isset($arr['extensions']) ?
            array_map('trim', explode(',', $arr['extensions'])) :
            [];

        $this->file_options = [
            'dir'        => $dir,
            'extensions' => $extensions,
            'max_size'   => ($arr['max_size'] ?? 0) * 1024,
            'override'   => boolval($arr['override'] ?? true)
        ];
    }


    /**
     * Returns the specified parameter
     *
     * @param  string|null  $key  the parameter key
     *
     * @return mixed The specified parameter
     */
    public function query(string $key = null)
    {
        if (!isset($key)) {
            return $this->query;
        }

        return $this->query[$key] ?? null;
    }


    /**
     * Returns true if the specified parameter is set,
     * false otherwise.
     *
     * @param  string  $key  the parameter key
     *
     * @return bool True if the specified parameter is set,
     * false otherwise.
     */
    public function hasQuery(string $key)
    {
        return array_key_exists($key, $this->query);
    }


    /**
     * Returns the specified body parameter
     *
     * @param  string|null  $key  the body parameter key
     *
     * @return mixed The specified body parameter
     */
    public function body(string $key = null)
    {
        if (!isset($key)) {
            return $this->body;
        }

        return $this->body[$key] ?? null;
    }


    /**
     * Returns true if the specified body parameter is set,
     * false otherwise.
     *
     * @param  string  $key  the parameter key
     *
     * @return bool True if the specified body parameter is set,
     * false otherwise.
     */
    public function has(string $key)
    {
        return array_key_exists($key, $this->body);
    }


    /**
     * Returns the specified file
     *
     * @param  string|null  $key  the file key
     *
     * @return mixed The specified file
     */
    public function file(string $key = null)
    {
        if (!isset($key)) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }


    /**
     * Returns true if the specified file is set,
     * false otherwise.
     *
     * @param  string  $key  the parameter key
     *
     * @return bool True if the specified file is set,
     * false otherwise.
     */
    public function hasFile(string $key)
    {
        return array_key_exists($key, $this->files);
    }


    /**
     * Returns the specified cookie
     *
     * @param  string|null  $key  the cookie key
     *
     * @return mixed the specified
     */
    public function cookie(string $key = null)
    {
        if (!isset($key)) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? null;
    }


    /**
     * Returns true if the specified cookie is set,
     * false otherwise.
     *
     * @param  string  $key  the parameter key
     *
     * @return bool True if the specified cookie is set,
     * false otherwise.
     */
    public function hasCookie(string $key)
    {
        return array_key_exists($key, $this->cookies);
    }


    /**
     * Returns the headers array,
     * or the specified header key
     *
     * @param  string|null  $key  the header key to get
     *
     * @return mixed The headers array,
     * or the specified header key
     */
    public function getHeader(string $key = null)
    {
        if (!isset($key)) {
            return $this->headers;
        }

        return $this->headers[$key] ?? null;
    }


    /**
     * Returns the request method
     *
     * @return string The request method
     */
    public function getMethod()
    {
        return $this->server['REQUEST_METHOD'];
    }


    /**
     * Returns the request uri
     *
     * @return string The request uri
     */
    public function getUri()
    {
        return substr($this->server['REQUEST_URI'], 0, strpos($this->server['REQUEST_URI'], '?'));
    }


    /**
     * Returns the full request uri
     *
     * @return string The request uri
     */
    public function getFullUri()
    {
        return $this->server['REQUEST_URI'];
    }


    /**
     * Returns true if the current protocol is secure (https),
     * false otherwise.
     *
     * @return bool true if the current protocol is secure (https),
     * false otherwise.
     */
    public function isSecure()
    {
        return isset($this->server['HTTPS']) &&
            ($this->server['HTTPS'] == 'on' || $this->server['HTTPS'] == 1) ||
            isset($this->server['HTTP_X_FORWARDED_PROTO']) &&
            $this->server['HTTP_X_FORWARDED_PROTO'] == 'https';
    }
}
