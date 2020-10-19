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
     * Current server
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
     * @param  array  $server  the super global server array
     *
     * @return array The headers parsed
     */
    private function parseHeaders(array $server): array
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
     * @param  array  $arr  the array of files
     * @param  array  $options  the array with the file options
     *
     * @return array The array of files instances based on the
     * given files array
     */
    private function getFiles(array $arr, array &$options): array
    {
        $files = [];

        foreach ($arr as $key => $val) {
            $files[$key] = new File($val, $options);
        }

        return $files;
    }


    /**
     * Sets the options for uploading the files
     *
     * @throws \Wolff\Exception\FileNotFoundException
     * @throws \Wolff\Exception\InvalidArgumentException
     *
     * @param  array  $arr  the array with the options
     * (dir, extensions, max_size, override)
     */
    public function fileOptions(array $arr = []): void
    {
        if (isset($arr['dir']) && !is_string($arr['dir'])) {
            throw new InvalidArgumentException('dir', 'a string');
        }

        $dir = Helper::getRoot($arr['dir'] ?? '');

        if (isset($arr['dir']) && !is_dir($dir)) {
            throw new FileNotFoundException($dir);
        } elseif (isset($arr['extensions']) && !is_string($arr['extensions'])) {
            throw new InvalidArgumentException('extensions', 'a string');
        } elseif (isset($arr['max_size']) && !is_numeric($arr['max_size'])) {
            throw new InvalidArgumentException('max_size', 'a numeric value');
        }

        $exts = isset($arr['extensions']) ?
            array_map('trim', explode(',', $arr['extensions'])) :
            [];

        $this->file_options = [
            'dir'        => $dir,
            'extensions' => $exts,
            'max_size'   => ($arr['max_size'] ?? 0) * 1024,
            'override'   => boolval($arr['override'] ?? true)
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function query(string $key = null)
    {
        return !isset($key) ?
            $this->query :
            ($this->query[$key] ?? null);
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
        return !isset($key) ?
            $this->body :
            ($this->body[$key] ?? null);
    }


    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }


    /**
     * {@inheritdoc}
     */
    public function file(string $key = null)
    {
        return !isset($key) ?
            $this->files :
            ($this->files[$key] ?? null);
    }


    /**
     * {@inheritdoc}
     */
    public function hasFile(string $key): bool
    {
        return array_key_exists($key, $this->files);
    }


    /**
     * {@inheritdoc}
     */
    public function cookie(string $key = null)
    {
        return !isset($key) ?
            $this->cookies :
            ($this->cookies[$key] ?? null);
    }


    /**
     * {@inheritdoc}
     */
    public function hasCookie(string $key): bool
    {
        return array_key_exists($key, $this->cookies);
    }


    /**
     * {@inheritdoc}
     */
    public function getHeader(string $key = null)
    {
        return !isset($key) ?
            $this->headers :
            ($this->headers[$key] ?? null);
    }


    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'];
    }


    /**
     * {@inheritdoc}
     */
    public function getUri(): string
    {
        return substr($this->server['REQUEST_URI'], 0, strpos($this->server['REQUEST_URI'], '?'));
    }


    /**
     * {@inheritdoc}
     */
    public function getFullUri(): string
    {
        return $this->server['REQUEST_URI'];
    }


    /**
     * {@inheritdoc}
     */
    public function isSecure(): bool
    {
        return (isset($this->server['HTTPS']) &&
            ($this->server['HTTPS'] == 'on' || $this->server['HTTPS'] == 1)) ||
            (isset($this->server['HTTP_X_FORWARDED_PROTO']) &&
            $this->server['HTTP_X_FORWARDED_PROTO'] == 'https');
    }
}
