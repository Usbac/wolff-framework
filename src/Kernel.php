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
        $this->config = $config;
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

        $this->initComponents($config);
        $this->setErrors();
        $this->stdlib();
    }


    /**
     * Initializes the main components
     *
     * @param  array  $config  the configuration
     */
    private function initComponents(array $config = [])
    {
        Config::init($config);
        Cache::init($config['cache_on'] ?? true);
        DB::setCredentials($config['db'] ?? []);
        Log::setStatus($config['log_on'] ?? true);
        Template::setStatus($config['template_on'] ?? true);
        Language::setDefault($config['language'] ?? 'english');
    }


    /**
     * Sets the error reporting state
     * based on the current configuration.
     */
    private function setErrors()
    {
        if (!isset($this->config['development_on'])) {
            return;
        }

        error_reporting($this->config['development_on'] ? E_ALL : 0);
        ini_set('display_errors', strval($this->config['development_on']));
    }


    /**
     * Includes the standard library if
     * it's activate in the given configuration
     */
    private function stdlib()
    {
        if ($this->config['stdlib_on'] ?? false) {
            include_once('stdlib.php');
        }
    }


    /**
     * Starts the loading of the page
     */
    public function start()
    {
        if (($this->config['maintenance_on'] ?? false) &&
            !Maintenance::hasAccess()) {
            Maintenance::call($this->req, $this->res);
            $this->res->send();
            return;
        }

        if ($this->isAccessible()) {
            $this->load();
        } else {
            $this->res->setCode(404);
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
        if (strpos($root, $_SERVER['DOCUMENT_ROOT']) === 0) {
            $project_dir = substr($root, strlen($_SERVER['DOCUMENT_ROOT']));
            $url = substr($url, strlen($project_dir));
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
