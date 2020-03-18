<?php

namespace Wolff\Core;

use Wolff\Utils\Str;

class Kernel
{

    const HEADER_404 = 'HTTP/1.0 404 Not Found';

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
     * Default constructor
     */
    public function __construct()
    {
        Config::init();
        Cache::init();

        $this->url = $this->getUrl();
        $this->function = Route::getVal($this->url);

        if (is_string($this->function)) {
            $path = explode('@', $this->function);
            $this->controller = $path[0];
            $this->method = empty($path[1]) ? 'index' : $path[1];
        } else {
            $this->controller = Str::before($this->url, '/');
            $this->method = Str::after($this->url, '/');
        }
    }


    /**
     * Starts the loading of the page
     */
    public function start()
    {
        if (Config::get('maintenance_on') &&
            !Maintenance::hasAccess()) {
            Maintenance::call();
        }

        $this->load();
        Route::execCode();
    }


    /**
     * Loads the current route and its middlewares
     */
    private function load()
    {
        if (!$this->isAccessible()) {
            header(self::HEADER_404);
            return;
        }

        $req = $this->getRequest();
        Middleware::loadBefore($this->url, $req);
        $this->loadPage($req);
        Middleware::loadAfter($this->url, $req);
    }


    /**
     * Returns a new request object
     *
     * @return  Http\Request  The new request object
     */
    private function getRequest()
    {
        return new Http\Request(
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER
        );
    }


    /**
     * Load the requested page
     *
     * @return  mixed  the method return value
     */
    private function loadPage(Http\Request $req)
    {
        if ($this->function instanceof \Closure) {
            ($this->function)($req);
        } else if (Controller::hasMethod($this->controller, $this->method)) {
            Controller::method($this->controller, $this->method, [ $req ]);
        } else if (Controller::exists($this->url)) {
            Controller::method($this->url, 'index', [ $req ]);
        }
    }


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
        $url = isset($_GET['url']) ?
            Str::sanitizeUrl($_GET['url']) :
            (Config::get('main_page') ?? '');

        return Route::getRedirection($url) ?? $url;
    }

}