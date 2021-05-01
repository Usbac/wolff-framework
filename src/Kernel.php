<?php

namespace Wolff;

use Wolff\Core\Cache;
use Wolff\Core\Config;
use Wolff\Core\DB;
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
        'language'       => 'en',
        'env_file'       => '',
        'env_override'   => false,
        'development_on' => true,
        'template_on'    => true,
        'cache_on'       => true,
        'stdlib_on'      => true,
        'maintenance_on' => false,
    ];

    /**
     * Current url
     *
     * @var string
     */
    private $url;

    /**
     * Function associated to the current url
     *
     * @var \Closure|null
     */
    private $func;

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
     * Constructor
     *
     * @param  array  $config  the configuration
     */
    public function __construct(array $config = [])
    {
        $this->url = $this->getUrl();
        $this->func = Route::getFunction($this->url);
        $this->req = new Request($_GET, $_POST, $_FILES, $_SERVER, $_COOKIE);
        $this->res = new Response();
        $this->initModules(array_merge(self::DEFAULT_CONFIG, $config));
    }


    /**
     * Initializes the framework modules with the given configuration
     *
     * @param  array  $config  the configuration
     */
    private function initModules(array $config): void
    {
        Config::init($config);
        DB::setCredentials($config['db']);
        Cache::setStatus($config['cache_on']);
        Template::setStatus($config['template_on']);
        Maintenance::setStatus($config['maintenance_on']);
        Language::setDefault($config['language']);
    }


    /**
     * Starts the page loading
     */
    public function start(): void
    {
        if (Maintenance::isEnabled() && !Maintenance::hasAccess()) {
            Maintenance::call($this->req, $this->res);
        } elseif ($this->func && !Route::isBlocked($this->url)) {
            $this->handle();
        } else {
            $this->res->setCode(404);
        }

        Route::execCode($this->res->getCode(), $this->req, $this->res);
        $this->res->send();
    }


    /**
     * Loads the current route and its middlewares
     */
    private function handle(): void
    {
        $this->res->append(Middleware::loadBefore($this->url, $this->req));
        call_user_func_array($this->func, [ $this->req, $this->res ]);
        $this->res->append(Middleware::loadAfter($this->url, $this->req));
    }


    /**
     * Returns the current url processed
     *
     * @return string the current url processed
     */
    private function getUrl(): string
    {
        $url = trim(\Wolff\Core\Helper::getCurrentPage(), '/');

        // Remove GET parameters
        if (($q_pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $q_pos);
        }

        // Redirection
        $redirect = Route::getRedirection($url);
        if (isset($redirect)) {
            $this->res->setCode($redirect['code']);
            return $redirect['to'];
        }

        return $url;
    }
}
