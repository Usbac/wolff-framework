<?php

namespace Wolff;

use Wolff\Core\Cache;
use Wolff\Core\Config;
use Wolff\Core\Controller;
use Wolff\Core\Factory;
use Wolff\Core\Route;
use Wolff\Core\Maintenance;
use Wolff\Core\Middleware;
use Wolff\Utils\Str;

final class Kernel
{

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
    public function __construct()
    {
        Config::init();
        Cache::init();

        $this->setErrors();
        $this->stdlib();

        $this->url = $this->getUrl();
        $this->function = Route::getVal($this->url);
        $this->req = Factory::request();
        $this->res = new Core\Http\Response();

        if (is_string($this->function)) {
            $path = explode('@', $this->function);
            $this->controller = $path[0];
            $this->method = empty($path[1]) ? 'index' : $path[1];
        } elseif (($last_slash = strrpos($this->url, '/')) > 0) {
            $this->controller = substr($this->url, 0, $last_slash);
            $this->method = substr($this->url, $last_slash + 1);
        } else {
            $this->controller = $this->url;
            $this->method = 'index';
        }
    }


    /**
     * Sets the error reporting state
     * based on the current configuration.
     */
    private function setErrors()
    {
        error_reporting(CONFIG['development_on'] ? E_ALL : 0);
        ini_set('display_errors', strval(CONFIG['development_on']));
    }


    /**
     * Includes the standard library if
     * it's activated in the configuration file
     */
    private function stdlib()
    {
        if (isset(CONFIG['stdlib_on']) && CONFIG['stdlib_on']) {
            include_once('stdlib.php');
        }
    }


    /**
     * Starts the loading of the page
     */
    public function start()
    {
        if (CONFIG['maintenance_on'] &&
            !Maintenance::hasAccess()) {
            Maintenance::call($this->req, $this->res);
            $this->res->send();
            return;
        }

        if (!$this->isAccessible()) {
            http_response_code(404);
        } else {
            $this->load();
        }

        Route::execCode($this->req, $this->res);
        $this->res->send();
    }


    /**
     * Loads the current route and its middlewares
     */
    private function load()
    {
        Middleware::loadBefore($this->url, $this->req);
        $this->loadPage();
        Middleware::loadAfter($this->url, $this->req);
    }


    /**
     * Loads the requested page
     */
    private function loadPage()
    {
        $params = [
            $this->req,
            $this->res
        ];

        if ($this->function instanceof \Closure) {
            call_user_func_array($this->function, $params);
        } elseif (Controller::hasMethod($this->controller, $this->method)) {
            Controller::method($this->controller, $this->method, $params);
        } elseif (Controller::exists($this->url)) {
            Controller::method($this->url, 'index', $params);
        }
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
        return (!Route::isBlocked($this->url) &&
            ($this->function instanceof \Closure ||
            Controller::hasMethod($this->controller, $this->method) ||
            Controller::exists($this->url)));
    }


    /**
     * Returns the current url processed
     *
     * @return  string  the current url processed
     */
    private function getUrl()
    {
        $url = $_SERVER['REQUEST_URI'];

        //Remove possible project folder from url
        if (strpos(CONFIG['root_dir'], $_SERVER['DOCUMENT_ROOT']) === 0) {
            $project_dir = substr(CONFIG['root_dir'], strlen($_SERVER['DOCUMENT_ROOT']));
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
