<?php

namespace Wolff;

use Wolff\Core\Cache;
use Wolff\Core\Config;
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

    const DEFAULT_CONFIG = [
        'db'             => [],
        'language'       => 'english',
        'env_file'       => '',
        'env_override'   => false,
        'development_on' => true,
        'template_on'    => true,
        'cache_on'       => true,
        'stdlib_on'      => true,
        'maintenance_on' => false,
    ];

    /**
     * The configuration
     *
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
     * @var \Closure|null
     */
    private $function;

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
     *
     * @param  array  $config  the configuration
     */
    public function __construct(array $config = [])
    {
        $this->initProperties($config);
        $this->initModules();
        $this->setErrors();
        $this->stdlib();
    }


    /**
     * Initializes the properties
     *
     * @param  array  $config  the configuration
     */
    private function initProperties(array $config = []): void
    {
        $this->config = array_merge(self::DEFAULT_CONFIG, $config);
        $this->url = $this->getUrl();
        $this->function = Route::getFunction($this->url);
        $this->req = new Request($_GET, $_POST, $_FILES, $_SERVER, $_COOKIE);
        $this->res = new Response();
    }


    /**
     * Initializes the core framework modules based
     * on the current configuration
     */
    private function initModules(): void
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
     * based on the current configuration
     */
    private function setErrors(): void
    {
        error_reporting($this->config['development_on'] ? E_ALL : 0);
        ini_set('display_errors', strval($this->config['development_on']));
    }


    /**
     * Includes the standard library if
     * it's active in the current configuration
     */
    private function stdlib(): void
    {
        if ($this->config['stdlib_on']) {
            include_once('stdlib.php');
        }
    }


    /**
     * Starts the page loading
     */
    public function start(): void
    {
        if (Maintenance::isEnabled() && !Maintenance::hasAccess()) {
            Maintenance::call($this->req, $this->res);
        } elseif ($this->function && !Route::isBlocked($this->url)) {
            $this->handle();
        } else {
            http_response_code(404);
        }

        Route::execCode($this->req, $this->res);
        $this->res->send();
    }


    /**
     * Loads the current route and its middlewares
     */
    private function handle(): void
    {
        $this->res->append(Middleware::loadBefore($this->url, $this->req));
        call_user_func_array($this->function, [ $this->req, $this->res ]);
        $this->res->append(Middleware::loadAfter($this->url, $this->req));
    }


    /**
     * Returns the current url processed
     *
     * @return string the current url processed
     */
    private function getUrl(): string
    {
        $url = $_SERVER['REQUEST_URI'];
        $root = Helper::getRoot();

        //Remove possible project folder from url
        if (strpos($root, $_SERVER['DOCUMENT_ROOT']) === 0) {
            $url = substr($url, strlen($root) - strlen($_SERVER['DOCUMENT_ROOT']));
        }

        $url = trim($url, '/');

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
