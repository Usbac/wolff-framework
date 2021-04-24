<?php

namespace {

    if (!function_exists('bytesToString')) {

        /**
         * Returns the given size (in bytes) as a human-readable string
         *
         * @param  int  $size  size (in bytes)
         * @param  int  $precision  number of digits after the decimal point
         *
         * @return string the size as a human-readable string
         */
        function bytesToString(int $size, int $precision = 0): string
        {
            $sizes = [ 'YB', 'ZB', 'EB', 'PB', 'TB', 'GB', 'MB', 'KB', 'B' ];
            $total = count($sizes);

            while ($total-- && $size > 1024) {
                $size /= 1024;
            }

            return round($size, $precision) . $sizes[$total];
        }
    }

    if (!function_exists('arrayRemove')) {

        /**
         * Removes an element from the given array based on its value
         *
         * @param  array  $arr  the array
         * @param  mixed  $needle  the value to remove
         *
         * @return bool true if the element has been removed, false otherwise
         */
        function arrayRemove(array &$arr, $needle): bool
        {
            return \Wolff\Core\Helper::arrayRemove($arr, $needle);
        }
    }

    if (!function_exists('validateCsrf')) {

        /**
         * Returns true if the current request is safe from csrf
         * (cross site request forgery), false otherwise.
         * This method combined with the 'csrf' tag in the template engine
         * is perfect for making forms that prevent csrf
         *
         * @return bool true if the current request is safe from csrf,
         * false otherwise
         */
        function validateCsrf(): bool
        {
            $key = WOLFF_CONFIG['csrf_key'];

            return ($_SERVER['REQUEST_METHOD'] === 'POST' &&
                isset($_POST[$key], $_COOKIE[$key]) &&
                $_POST[$key] === $_COOKIE[$key]) ||
                ($_SERVER['REQUEST_METHOD'] === 'GET' &&
                isset($_GET[$key], $_COOKIE[$key]) &&
                $_GET[$key] === $_COOKIE[$key]);
        }
    }

    if (!function_exists('path')) {

        /**
         * Returns the absolute path of the given relative path (supposed
         * to be relative to the project root)
         *
         * @param  string  $path  the relative path
         *
         * @return string the absolute path
         */
        function path(string $path = ''): string
        {
            return \Wolff\Core\Helper::getRoot($path);
        }
    }

    if (!function_exists('relativePath')) {

        /**
         * Returns the relative path (based on the project root)
         * of the given absolute path
         *
         * @param  string  $path  the absolute path
         *
         * @return string the path as relative
         */
        function relativePath(string $path = ''): string
        {
            $root = Wolff\Core\Helper::getRoot();

            if (strpos($path, $root) === 0) {
                return substr($path, strlen($root));
            }

            return $path;
        }
    }

    if (!function_exists('val')) {

        /**
         * Returns the key value of the given array, or null if it doesn't exists.
         * The key param can use the dot notation, like 'user.name'
         *
         * @param  array  $arr  the array
         * @param  string|null  $key  the array key to obtain
         *
         * @return mixed the value of the specified key in the array
         */
        function val(array $arr, string $key = null)
        {
            return \Wolff\Core\Helper::val($arr, $key);
        }
    }

    if (!function_exists('config')) {

        /**
         * Returns the configuration array or the specified key of it.
         * The key param can use the dot notation, like 'user.name'
         *
         * @param  string|null  $key  the configuration array key
         *
         * @return mixed the configuration array or the specified key of it
         */
        function config(string $key = null)
        {
            return \Wolff\Core\Helper::val(\Wolff\Core\Config::get(), $key);
        }
    }

    if (!function_exists('getPublic')) {

        /**
         * Returns the public directory of the project
         *
         * @param  string  $path  the optional path to append
         *
         * @return string the public directory of the project
         */
        function getPublic(string $path = ''): string
        {
            return \Wolff\Core\Helper::getRoot('public/' . ltrim($path, '/'));
        }
    }

    if (!function_exists('isAssoc')) {

        /**
         * Returns true if the given array is associative, false otherwise
         *
         * @param  array  $arr  the array
         *
         * @return bool true if the given array is associative, false otherwise
         */
        function isAssoc(array $arr): bool
        {
            return \Wolff\Core\Helper::isAssoc($arr);
        }
    }

    if (!function_exists('echod')) {

        /**
         * Print a string and die
         */
        function echod(...$args): void
        {
            foreach ($args as $arg) {
                echo $arg;
            }

            die;
        }
    }

    if (!function_exists('printr')) {

        /**
         * Print the given values in a nice looking way
         */
        function printr(...$args): void
        {
            echo '<pre>';
            array_map('print_r', $args);
            echo '</pre>';
        }
    }

    if (!function_exists('printrd')) {

        /**
         * Print the given values in a nice looking way and die
         */
        function printrd(...$args): void
        {
            echo '<pre>';
            array_map('print_r', $args);
            echo '</pre>';
            die;
        }
    }

    if (!function_exists('dumpd')) {

        /**
         * Var dump the given values and die
         */
        function dumpd(...$args): void
        {
            array_map('var_dump', $args);
            die;
        }
    }

    if (!function_exists('redirect')) {

        /**
         * Make a redirection
         *
         * @param  string|null  $url  the url to redirect to
         * @param  int  $status  the HTTP status code
         */
        function redirect(string $url = null, int $status = 302): void
        {
            // Set url to the homepage when null
            if (!isset($url)) {
                $http = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://';

                $project_dir = '';
                $root = \Wolff\Core\Helper::getRoot();
                if (strpos($root, $_SERVER['DOCUMENT_ROOT']) === 0) {
                    $project_dir = substr($root, strlen($_SERVER['DOCUMENT_ROOT']));
                }

                $directory = str_replace('\\', '/', $project_dir);

                if (substr($directory, -1) !== '/') {
                    $directory .= '/';
                }

                $url = $http . $_SERVER['HTTP_HOST'] . $directory;
            }

            header("Location: $url", true, $status);
            exit;
        }
    }

    if (!function_exists('isJson')) {

        /**
         * Returns true if the given string is a Json, false otherwise.
         * Notice: This function modifies the 'json_last_error' value
         *
         * @param  string  $str  the string
         *
         * @return bool true if the given string is a Json, false otherwise
         */
        function isJson(string $str): bool
        {
            json_decode($str);
            return json_last_error() === JSON_ERROR_NONE;
        }
    }

    if (!function_exists('toArray')) {

        /**
         * Returns the given variable as an associative array
         *
         * @param  mixed  $obj  the object
         *
         * @return mixed the given variable as an associative array
         */
        function toArray($obj)
        {
            //Json
            if (is_string($obj)) {
                json_decode($obj);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $obj = json_decode($obj);
                }
            }

            $new = [];

            if (is_object($obj)) {
                $obj = (array) $obj;
            }

            if (is_array($obj)) {
                foreach ($obj as $key => $val) {
                    $new[$key] = toArray($val);
                }
            } else {
                $new = $obj;
            }

            return $new;
        }
    }

    if (!function_exists('url')) {

        /**
         * Returns the complete url relative to the local site
         *
         * @param  string  $url  the url to redirect to
         *
         * @return string the complete url relative to the local site
         */
        function url(string $url = ''): string
        {
            $http = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://';

            $project_dir = '';
            $root = \Wolff\Core\Helper::getRoot();
            if (strpos($root, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $project_dir = substr($root, strlen($_SERVER['DOCUMENT_ROOT']));
            }

            $directory = str_replace('\\', '/', $project_dir);

            if (substr($directory, -1) !== '/') {
                $directory .= '/';
            }

            return $http . $_SERVER['HTTP_HOST'] . $directory . $url;
        }
    }

    if (!function_exists('local')) {

        /**
         * Returns true if running in localhost, false otherwise
         *
         * @param  array  $whitelist  the optional list of local IPs to check
         *
         * @return bool true if running in localhost, false otherwise
         */
        function local($whitelist = [ '127.0.0.1', '::1' ])
        {
            return in_array($_SERVER['REMOTE_ADDR'] ?? '::1', $whitelist);
        }
    }

    if (!function_exists('average')) {

        /**
         * Returns the average value of the given array
         *
         * @param  array  $arr  the array with the numeric values
         *
         * @return float|int the average value of the given array
         */
        function average(array $arr)
        {
            return array_sum($arr) / count($arr);
        }
    }

    if (!function_exists('getClientIP')) {

        /**
         * Returns the current client IP
         *
         * @return string the current client IP
         */
        function getClientIP(): string
        {
            return \Wolff\Core\Helper::getClientIP();
        }
    }

    if (!function_exists('getCurrentPage')) {

        /**
         * Returns the current page relative to the project url
         *
         * @return string the current page relative to the project url
         */
        function getCurrentPage(): string
        {
            return \Wolff\Core\Helper::getCurrentPage();
        }
    }

    if (!function_exists('getPureCurrentPage')) {

        /**
         * Returns the current page without arguments
         *
         * @return string the current page without arguments
         */
        function getPureCurrentPage(): string
        {
            $host = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

            if (($q_pos = strpos($_SERVER['REQUEST_URI'], '?')) !== false) {
                $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, $q_pos);
            }

            return $host . $_SERVER['REQUEST_URI'];
        }
    }

    if (!function_exists('getBenchmark')) {

        /**
         * Returns the time between the page load start and the current time
         *
         * @return float the time between the page load start and the current time
         */
        function getBenchmark(): float
        {
            return microtime(true) - WOLFF_CONFIG['start'];
        }
    }

    if (!function_exists('isInt')) {

        /**
         * Returns true if the argument complies with an int, false otherwise
         *
         * @param  mixed  $int  the variable
         */
        function isInt($int): bool
        {
            return filter_var($int, FILTER_VALIDATE_INT) !== false;
        }
    }

    if (!function_exists('isFloat')) {

        /**
         * Returns true if the argument complies with an float, false otherwise
         *
         * @param  mixed  $float  the variable
         */
        function isFloat($float): bool
        {
            return filter_var($float, FILTER_VALIDATE_FLOAT) !== false;
        }
    }

}
