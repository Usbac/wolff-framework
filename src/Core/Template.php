<?php

namespace Wolff\Core;

use Wolff\Core\Helper;
use Wolff\Utils\Str;
use Wolff\Exception\FileNotFoundException;
use Wolff\Exception\InvalidArgumentException;

final class Template
{

    const PATH_FORMAT_EXT = 'app/views/%s.wlf';
    const PATH_FORMAT = 'app/views/%s';
    const EXISTS_ERROR = 'View file \'%s\' doesn\'t exists';
    const RAW = '~';
    const NOT_RAW = '(?<!' . self::RAW . ')';
    const BLOCK_NAME = '[a-zA-Z0-9_]+';
    const TOKEN_TIME = 3600; // 1 hour
    const FORMAT = [
        'style'  => '/' . self::NOT_RAW . '\{%(\s?){1,}style( ?){1,}=( ?){1,}(.*)(\s?){1,}%\}/',
        'script' => '/' . self::NOT_RAW . '\{%(\s?){1,}script( ?){1,}=( ?){1,}(.*)(\s?){1,}%\}/',
        'icon'   => '/' . self::NOT_RAW . '\{%(\s?){1,}icon( ?){1,}=( ?){1,}(.*)(\s?){1,}%\}/',

        'comment'    => '/' . self::NOT_RAW . '\{#(?s).[^#\}]*#\}/',
        'plain_echo' => '/' . self::NOT_RAW . '\{\!( ?){1,}(.*?)( ?){1,}\!\}/',
        'echo'       => '/' . self::NOT_RAW . '\{\{( ?){1,}(.*?)( ?){1,}\}\}/',
        'tag'        => '/' . self::NOT_RAW . '\{%( ?){1,}(.*?)( ?){1,}%\}/',
        'function'   => '/' . self::NOT_RAW . '(.*)( ?){1,}\|([^\}!]{1,})/',
        'include'    => '/' . self::NOT_RAW . '@include\([ ]{0,}(\'.*\'|".*")[ ]{0,}\)/',
        'for'        => '/' . self::NOT_RAW . '\{%( ?){1,}for( ){1,}(.*)( ){1,}in( ){1,}\((.*)( ?){1,},( ?){1,}(.*)( ?){1,}\)( ?){1,}%\}/',
        'csrf'       => '/' . self::NOT_RAW . '@csrf/',

        'extends'      => '/' . self::NOT_RAW . '@extends\([ ]{0,}(\'.*\'|".*")[ ]{0,}\)/',
        'block'        => '/' . self::NOT_RAW . '{\[[ ?]{1,}block[ ]{1,}(' . self::BLOCK_NAME . ')[ ?]{1,}]}([\s\S]*?){\[[ ?]{1,}endblock[ ?]{1,}]}[\s]/',
        'parent_block' => '/' . self::NOT_RAW . '{\[[ ?]{1,}parent[ ]{1,}(' . self::BLOCK_NAME . ')[ ?]{1,}]}/'
    ];
    const FUNCTIONS = [
        'upper'           => 'strtoupper($2)',
        'lower'           => 'strtolower($2)',
        'upperf'          => 'ucfirst($2)',
        'length'          => 'strlen($2)',
        'count'           => 'count($2)',
        'title'           => 'ucwords($2)',
        'md5'             => 'md5($2)',
        'countwords'      => 'str_word_count($2)',
        'trim'            => 'trim($2)',
        'nl2br'           => 'nl2br($2)',
        'join\((.*?)\)'   => 'implode($1, $3)',
        'repeat\((.*?)\)' => 'str_repeat($3, $1)',
        'e'               => 'htmlspecialchars(strip_tags($2))'
    ];

    /**
     * List of custom templates
     *
     * @var array
     */
    private static $templates = [];

    /**
     * The template status
     * @var bool
     */
    private static $enabled = true;


    /**
     * Sets the template engine status
     *
     * @param  bool  $enabled  True for enabling the template engine,
     * false for disabling it
     */
    public static function setStatus(bool $enabled = true)
    {
        self::$enabled = $enabled;
    }


    /**
     * Returns true if the template system is enabled, false otherwise
     * @return bool true if the template system is enabled, false otherwise
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }


    /**
     * Returns the view content rendered or false in case of errors.
     * The template format will be applied only if the template is enabled.
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the data array present in the view
     * @param  bool  $cache  use or not the cache system
     *
     * @return string|bool the view content rendered or false in case of errors.
     */
    public static function getRender(string $dir, array $data, bool $cache)
    {
        if (is_array($data)) {
            extract($data);
            unset($data);
        }

        ob_start();

        //Cache
        if ($cache && Cache::isEnabled()) {
            if (Cache::has($dir)) {
                $content = Cache::get($dir);
            } else {
                $content = self::getContent($dir);

                if (self::$enabled) {
                    $content = self::replaceAll($content);
                }
            }

            include(Cache::set($dir, $content));
        } else {
            $tmp_file = tmpfile();
            $content = self::getContent($dir);

            if (self::$enabled) {
                $content = self::replaceAll($content);
            }

            fwrite($tmp_file, $content);
            include(stream_get_meta_data($tmp_file)['uri']);
            fclose($tmp_file);
        }

        $rendered_content = ob_get_contents();
        ob_end_clean();

        return $rendered_content;
    }


    /**
     * Returns the view content with the template format applied
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the data array present in the view
     *
     * @return string the view content with the template format applied
     */
    public static function get(string $dir, array $data, bool $cache)
    {
        //Variables in data array
        if (is_array($data)) {
            extract($data);
            unset($data);
        }

        if ($cache && Cache::isEnabled() && Cache::has($dir)) {
            return Cache::get($dir);
        }

        $content = self::getContent($dir);

        return self::$enabled ? self::replaceAll($content) : $content;
    }


    /**
     * Returns the content of a view file
     *
     * @throws \Wolff\Exception\FileNotFoundException
     *
     * @param  string  $dir  the view directory
     *
     * @return string The content of a view file
     */
    private static function getContent($dir)
    {
        $file_path = self::getPath($dir);

        if (!file_exists($file_path)) {
            throw new FileNotFoundException(
                sprintf(self::EXISTS_ERROR, $file_path)
            );
        }

        return file_get_contents($file_path);
    }


    /**
     * Returns the complete view file path
     *
     * @param  string  $dir  the view directory
     *
     * @return string the complete view file path
     */
    public static function getPath(string $dir)
    {
        $path_format =
            Helper::endsWith($dir, '.php') ||
            Helper::endsWith($dir, '.html') ?
            self::PATH_FORMAT :
            self::PATH_FORMAT_EXT;

        return Helper::getRoot(sprintf($path_format, $dir));
    }


    /**
     * Applies all the replace methods of the template
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the template replaced
     */
    private static function replaceAll(string $content)
    {
        $content = self::replaceCsrf($content);
        $content = self::replaceExtend($content);
        $content = self::replaceIncludes($content);
        $content = self::replaceImports($content);
        $content = self::replaceComments($content);
        $content = self::replaceFunctions($content);
        $content = self::replaceCycles($content);
        $content = self::replaceTags($content);
        $content = self::replaceCustom($content);
        $content = self::replaceRaws($content);

        return $content;
    }


    /**
     * Applies the inputs that prevent csrf
     * (cross site request forgery)
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with
     * the inputs that prevent csrf
     */
    private static function replaceCsrf(string $content)
    {
        preg_match(self::FORMAT['csrf'], $content, $matches);

        if (empty($matches)) {
            return $content;
        }

        $input = '<input type="hidden" name="' . WOLFF_CONFIG['csrf_key'] .'" value="' . self::getCsrfToken() . '"/>';
        return preg_replace(self::FORMAT['csrf'], $input, $content);
    }


    /**
     * Returns a csrf token, generating its respective
     * cookie in the process if it doesn't exists.
     *
     * @return string the csrf token
     */
    private static function getCsrfToken()
    {
        $key = WOLFF_CONFIG['csrf_key'];
        if (!isset($_COOKIE[$key])) {
            $token = bin2hex(random_bytes(8));
            setcookie($key, $token, time() + self::TOKEN_TIME, '/', '', false, true);

            return $token;
        }

        return $_COOKIE[$key];
    }


    /**
     * Applies the template extends
     *
     * @param  string  $content  the view content
     *
     * @return string the child view content rendered
     * based on its parent
     */
    private static function replaceExtend(string $content)
    {
        preg_match(self::FORMAT['extends'], $content, $matches);

        if (!isset($matches[1])) {
            return $content;
        }

        $filename = Str::sanitizePath(trim($matches[1], '"\''));
        $parent_content = self::getContent($filename);

        //Replace parent block imports in child view
        preg_match_all(self::FORMAT['parent_block'], $content, $parent_blocks);

        foreach ($parent_blocks[1] as $block_name) {
            $block_regex = str_replace(self::BLOCK_NAME, $block_name, self::FORMAT['block']);
            $parent_block_regex = str_replace(self::BLOCK_NAME, $block_name, self::FORMAT['parent_block']);
            preg_match($block_regex, $parent_content, $block_content);

            $content = preg_replace($parent_block_regex, trim($block_content[2] ?? ''), $content);
        }

        //Replace blocks in parent with child blocks content
        preg_match_all(self::FORMAT['block'], $content, $child_blocks);

        foreach ($child_blocks[1] as $key => $block_name) {
            $block_regex = str_replace(self::BLOCK_NAME, $block_name, self::FORMAT['block']);
            $parent_content = preg_replace($block_regex, trim($child_blocks[2][$key]), $parent_content);
        }

        //Remove remaining unused tags
        $parent_content = preg_replace(self::FORMAT['block'], '', $parent_content);
        $parent_content = preg_replace(self::FORMAT['parent_block'], '', $parent_content);

        return $parent_content;
    }


    /**
     * Adds a custom template
     *
     * @param  mixed  $function  the function with the custom template
     */
    public static function custom($function)
    {
        if (!is_callable($function)) {
            throw new InvalidArgumentException('function', 'callable');
        }

        self::$templates[] = $function;
    }


    /**
     * Applies the custom templates
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the custom templates formatted
     */
    private static function replaceCustom(string $content)
    {
        foreach (self::$templates as $template) {
            $content = $template($content);
        }

        return $content;
    }


    /**
     * Applies the template includes
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the includes formatted
     */
    private static function replaceIncludes($content)
    {
        preg_match_all(self::FORMAT['include'], $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $key => $val) {
            $filename = Str::sanitizePath(trim($val[0], '"\''));
            $content = str_replace($matches[0][$key][0], self::getContent($filename), $content);
        }

        return $content;
    }


    /**
     * Applies the template imports
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the import tags formatted
     */
    private static function replaceImports($content)
    {
        $content = preg_replace(self::FORMAT['style'], '<link rel="stylesheet" type="text/css" href=$4/>', $content);
        $content = preg_replace(self::FORMAT['script'], '<script type="text/javascript" src=$4></script>', $content);
        $content = preg_replace(self::FORMAT['icon'], '<link rel="icon" href=$4>', $content);

        return $content;
    }


    /**
     * Applies the template functions
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the functions formatted
     */
    private static function replaceFunctions($content)
    {
        foreach (self::FUNCTIONS as $original => $replacement) {
            $original = str_replace('(.*)', $original, self::FORMAT['function']);
            $content = preg_replace($original, $replacement, $content);
        }

        return $content;
    }


    /**
     * Applies the template format over the tags of a content
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the tags formatted
     */
    private static function replaceTags($content)
    {
        $content = preg_replace(self::FORMAT['echo'], '<?php echo htmlspecialchars($2, ENT_QUOTES) ?>', $content);
        $content = preg_replace(self::FORMAT['plain_echo'], '<?php echo $2 ?>', $content);
        $content = preg_replace(self::FORMAT['tag'], '<?php $2 ?>', $content);

        return $content;
    }


    /**
     * Applies the template format over the cycles of a content
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the cycles formatted
     */
    private static function replaceCycles($content)
    {
        $content = preg_replace(self::FORMAT['for'], '<?php for ($3 = $6; $3 <= $9; $3++): ?>', $content);

        return $content;
    }


    /**
     * Removes the comments of a content
     *
     * @param  string  $content  the view content
     *
     * @return string the view content without the comments
     */
    private static function replaceComments($content)
    {
        return preg_replace(self::FORMAT['comment'], '', $content);
    }


    /**
     * Removes the raw tag from the rest of the tags
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the raw tag removed from the rest of the tags
     */
    private static function replaceRaws($content)
    {
        foreach (self::FORMAT as $format) {
            $format = trim($format, '/');
            $format = str_replace(self::NOT_RAW, '', $format);

            $content = preg_replace('/' . self::RAW . '(' . $format . ')/', '$1', $content);
        }

        return $content;
    }
}
