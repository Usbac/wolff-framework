<?php

namespace Wolff;

use Wolff\Core\Cache;
use Wolff\Core\Config;
use Wolff\Core\Controller;
use Wolff\Core\DB;
use Wolff\Core\Factory;
use Wolff\Core\Helper;
use Wolff\Core\Language;
use Wolff\Core\Log;
use Wolff\Core\Route;
use Wolff\Core\Maintenance;
use Wolff\Core\Middleware;
use Wolff\Core\Template;
use Wolff\Utils\Str;

final class Kernel
{

    const DEFAULT_CONFIG = [
        'db'             => [],
        'language'       => 'english',
        'env_file'       => '',
        'env_override'   => false,
        'log_on'         => true,
        'development_on' => true,
        'template_on'    => true,
        'cache_on'       => true,
        'stdlib_on'      => true,
        'maintenance_on' => false
    ];

    /**
     * The configuration
     * @var array
     */
    private $config;

    /**
     * The current url.
     *
     * @var string
     */
    private $url;

    /**
     * The function associated to the current url.
     *
     * @var object
     */
    private $function;

    /**
     * The controller name.
     *
     * @var string
     */
    private $controller;

    /**
     * The controller method name.
     *
     * @var string
     */
    private $method;

    /**
     * Current request object
     *
     * @var \Wolff\Core\Http\Request
     */
    private $req;

    /**
     * Current response object
     *
     * @var \Wolff\Core\Http\Response
     */
    private $res;


    /**
     * Default constructor
     */
    public function __construct(array $config = [])
    {
        $this->initConfig($config);
        $this->url = $this->getUrl();
        $this->function = Route::getFunction($this->url);
        $this->req = Factory::request();
        $this->res = Factory::response();

        if (is_string($this->function)) {
            $path = explode('@', $this->function);
            $this->controller = $path[0];
            $this->method = empty($path[1]) ? 'index' : $path[1];
        } elseif (($slash_index = strrpos($this->url, '/')) > 0) {
            $this->controller = substr($this->url, 0, $slash_index);
            $this->method = substr($this->url, $slash_index + 1);
        } else {
            $this->controller = $this->url;
            $this->method = 'index';
        }

        $this->initComponents();
        $this->setErrors();
        $this->stdlib();
    }


    /**
     * Initializes the configuration array based
     * on the given array
     *
     * @param  array  $config  the configuration
     */
    private function initConfig(array $config = [])
    {
        $this->config = [];

        foreach (self::DEFAULT_CONFIG as $key => $val) {
            $this->config[$key] = $config[$key] ?? $val;
        }
    }


    /**
     * Initializes the main components based
     * on the current configuration
     */
    private function initComponents()
    {
        Config::init($this->config);
        Cache::init($this->config['cache_on']);
        DB::setCredentials($this->config['db']);
        Log::setStatus($this->config['log_on']);
        Template::setStatus($this->config['template_on']);
        Maintenance::setStatus($this->config['maintenance_on']);
        Language::setDefault($this->config['language']);
    }


    /**
     * Sets the error reporting state
     * based on the current configuration.
     */
    private function setErrors()
    {
        error_reporting($this->config['development_on'] ? E_ALL : 0);
        ini_set('display_errors', strval($this->config['development_on']));
    }


    /**
     * Includes the standard library if
     * it's activate in the given configuration
     */
    private function stdlib()
    {
        if ($this->config['stdlib_on']) {
            include_once('stdlib.php');
        }
    }


    /**
     * Starts the loading of the page
     */
    public function start()
    {
        if (Maintenance::isEnabled() && !Maintenance::hasAccess()) {
            Maintenance::call($this->req, $this->res);
            $this->res->send();
            return;
        }

        if ($this->isAccessible()) {
            $this->load();
        } else {
            http_response_code(404);
        }

        Route::execCode($this->req, $this->res);
        $this->res->send();
    }


    /**
     * Loads the current route and its middlewares
     */
    private function load()
    {
        $params = [
            $this->req,
            $this->res
        ];

        $this->res->append(Middleware::loadBefore($this->url, $this->req));

        if ($this->function instanceof \Closure) {
            call_user_func_array($this->function, $params);
        } elseif (Controller::hasMethod($this->controller, $this->method)) {
            Controller::method($this->controller, $this->method, $params);
        } elseif (Controller::exists($this->url)) {
            Controller::method($this->url, 'index', $params);
        }

        $this->res->append(Middleware::loadAfter($this->url, $this->req));
    }


    /**
     * Returns true if the current route is accessible,
     * false otherwise
     *
     * @return bool true if the current route is accessible,
     * false otherwise
     */
    private function isAccessible()
    {
        return !Route::isBlocked($this->url) &&
            ($this->function instanceof \Closure ||
            Controller::hasMethod($this->controller, $this->method) ||
            Controller::exists($this->url));
    }


    /**
     * Returns the current url processed
     *
     * @return string the current url processed
     */
    private function getUrl()
    {
        $url = $_SERVER['REQUEST_URI'];
        $root = Helper::getRoot();

        //Remove possible project folder from url
        $doc_root = $_SERVER['DOCUMENT_ROOT'];
        if (strpos($root, $doc_root) === 0) {
            $url = substr($url, strlen($root) - strlen($doc_root));
        }

        $url = ltrim($url, '/');

        //Remove parameters
        if (($query_index = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $query_index);
        }

        $url = Str::sanitizeUrl($url);

        //Redirection
        $redirect = Route::getRedirection($url);
        if (isset($redirect)) {
            http_response_code($redirect['code']);
            return $redirect['destiny'];
        }

        return $url;
    }
}
