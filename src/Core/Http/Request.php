<?php

namespace Wolff\Core\Http;

use Wolff\Core\Helper;
use Wolff\Exception\InvalidArgumentException;
use Wolff\Exception\FileNotFoundException;

class Request implements RequestInterface
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
     * {@inheritdoc}
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
     * (dir, extensions, max_size, override)
     */
    public function fileOptions(array $arr = [])
    {
        if (isset($arr['dir']) && !is_string($arr['dir'])) {
            throw new InvalidArgumentException('dir', 'a string');
        }

        $dir = Helper::getRoot($arr['dir'] ?? '');
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
     * {@inheritdoc}
     */
    public function query(string $key = null)
    {
        if (!isset($key)) {
            return $this->query;
        }

        return $this->query[$key] ?? null;
    }


    /**
     * {@inheritdoc}
     */
    public function hasQuery(string $key)
    {
        return array_key_exists($key, $this->query);
    }


    /**
     * {@inheritdoc}
     */
    public function body(string $key = null)
    {
        if (!isset($key)) {
            return $this->body;
        }

        return $this->body[$key] ?? null;
    }


    /**
     * {@inheritdoc}
     */
    public function has(string $key)
    {
        return array_key_exists($key, $this->body);
    }


    /**
     * {@inheritdoc}
     */
    public function file(string $key = null)
    {
        if (!isset($key)) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }


    /**
     * {@inheritdoc}
     */
    public function hasFile(string $key)
    {
        return array_key_exists($key, $this->files);
    }


    /**
     * {@inheritdoc}
     */
    public function cookie(string $key = null)
    {
        if (!isset($key)) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? null;
    }


    /**
     * {@inheritdoc}
     */
    public function hasCookie(string $key)
    {
        return array_key_exists($key, $this->cookies);
    }


    /**
     * {@inheritdoc}
     */
    public function getHeader(string $key = null)
    {
        if (!isset($key)) {
            return $this->headers;
        }

        return $this->headers[$key] ?? null;
    }


    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->server['REQUEST_METHOD'];
    }


    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return substr($this->server['REQUEST_URI'], 0, strpos($this->server['REQUEST_URI'], '?'));
    }


    /**
     * {@inheritdoc}
     */
    public function getFullUri()
    {
        return $this->server['REQUEST_URI'];
    }


    /**
     * {@inheritdoc}
     */
    public function isSecure()
    {
        return isset($this->server['HTTPS']) &&
            ($this->server['HTTPS'] == 'on' || $this->server['HTTPS'] == 1) ||
            isset($this->server['HTTP_X_FORWARDED_PROTO']) &&
            $this->server['HTTP_X_FORWARDED_PROTO'] == 'https';
    }
}
