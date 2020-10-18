<?php

namespace Wolff;

use Wolff\Core\Cache;
use Wolff\Core\Config;
use Wolff\Core\Controller;
use Wolff\Core\DB;
use Wolff\Core\Helper;
use Wolff\Core\Http\Request;
use Wolff\Core\Http\Response;
use Wolff\Core\Language;
use Wolff\Core\Route;
use Wolff\Core\Maintenance;
use Wolff\Core\Middleware;
use Wolff\Core\Template;

final class Kernel
{

    const ACCESS_CLOSURE = 1;
    const ACCESS_METHOD = 2;
    const ACCESS_CONTROLLER = 3;
    const DEFAULT_CONFIG = [
        'db'             => [],
        'language'       => 'english',
        'env_file'       => '',
        'env_override'   => false,
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
     * The current url
     *
     * @var string
     */
    private $url;

    /**
     * The function associated to the current url
     *
     * @var object
     */
    private $function;

    /**
     * The controller name
     *
     * @var string
     */
    private $controller;

    /**
     * The controller method name
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
        $this->initProperties($config);
        $this->initComponents();
        $this->setErrors();
        $this->stdlib();
    }


    /**
     * Initializes the properties
     *
     * @param  array  $config  the configuration
     */
    private function initProperties(array $config = [])
    {
        $this->config = array_merge(self::DEFAULT_CONFIG, $config);
        $this->url = $this->getUrl();
        $this->function = Route::getFunction($this->url);
        $this->req = new Request($_GET, $_POST, $_FILES, $_SERVER, $_COOKIE);
        $this->res = new Response();

        if (is_string($this->function)) {
            $path = explode('@', $this->function);
            $this->controller = $path[0];
            $this->method = empty($path[1]) ? 'index' : $path[1];
        } elseif (($slash_pos = strrpos($this->url, '/')) > 0) {
            $this->controller = substr($this->url, 0, $slash_pos);
            $this->method = substr($this->url, $slash_pos + 1);
        } else {
            $this->controller = $this->url;
            $this->method = 'index';
        }
    }


    /**
     * Initializes the main components based
     * on the current configuration
     */
    private function initComponents()
    {
        Config::init($this->config);
        DB::setCredentials($this->config['db']);
        Cache::setStatus($this->config['cache_on']);
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
     * it's active in the current configuration
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
        } else {
            $access_code = $this->getAccessCode();

            if ($access_code) {
                $this->load($access_code);
            } else {
                http_response_code(404);
            }

            Route::execCode($this->req, $this->res);
        }

        $this->res->send();
    }


    /**
     * Loads the current route and its middlewares
     *
     * @param  int  $access_code  the access code
     */
    private function load(int $access_code)
    {
        $args = [
            $this->req,
            $this->res
        ];

        $this->res->append(Middleware::loadBefore($this->url, $this->req));

        switch ($access_code) {
            case self::ACCESS_CLOSURE:
                call_user_func_array($this->function, $args);
                break;
            case self::ACCESS_METHOD:
                Controller::method($this->controller, $this->method, $args);
                break;
            case self::ACCESS_CONTROLLER:
                Controller::method($this->url, 'index', $args);
                break;
        }

        $this->res->append(Middleware::loadAfter($this->url, $this->req));
    }


    /**
     * Returns the access code of the current route
     *
     * @return int the access code of the current route
     */
    private function getAccessCode()
    {
        if (Route::isBlocked($this->url)) {
            return 0;
        } elseif ($this->function instanceof \Closure) {
            return self::ACCESS_CLOSURE;
        } elseif (Controller::hasMethod($this->controller, $this->method)) {
            return self::ACCESS_METHOD;
        } elseif (Controller::exists($this->url)) {
            return self::ACCESS_CONTROLLER;
        }

        return 0;
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
        if (strpos($root, $_SERVER['DOCUMENT_ROOT']) === 0) {
            $url = substr($url, strlen($root) - strlen($_SERVER['DOCUMENT_ROOT']));
        }

        $url = ltrim($url, '/');

        //Remove parameters
        if (($q_pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $q_pos);
        }

        //Redirection
        $redirect = Route::getRedirection($url);
        if (isset($redirect)) {
            http_response_code($redirect['code']);
            return $redirect['destiny'];
        }

        return $url;
    }
}
