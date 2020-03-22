<?php

namespace Wolff\Core;

use Wolff\Utils\Str;

class Template
{

    const RAW = '~';
    const NOT_RAW = '(?<!' . self::RAW . ')';
    const BLOCK_NAME = '[a-zA-Z0-9_]+';
    const FORMAT = [
        'style'  => '/' . self::NOT_RAW . '\{%(\s?){1,}style( ?){1,}=( ?){1,}(.*)(\s?){1,}%\}/',
        'script' => '/' . self::NOT_RAW . '\{%(\s?){1,}script( ?){1,}=( ?){1,}(.*)(\s?){1,}%\}/',
        'icon'   => '/' . self::NOT_RAW . '\{%(\s?){1,}icon( ?){1,}=( ?){1,}(.*)(\s?){1,}%\}/',

        'comment'   => '/' . self::NOT_RAW . '\{#(?s).[^#\}]*#\}/',
        'plainecho' => '/' . self::NOT_RAW . '\{\!( ?){1,}(.*?)( ?){1,}\!\}/',
        'echo'      => '/' . self::NOT_RAW . '\{\{( ?){1,}(.*?)( ?){1,}\}\}/',
        'tag'       => '/' . self::NOT_RAW . '\{%( ?){1,}(.*?)( ?){1,}%\}/',
        'function'  => '/' . self::NOT_RAW . '(.*)( ?){1,}\|([^\}!]{1,})/',
        'include'   => '/' . self::NOT_RAW . '@load\(( |\'?){1,}(.*)( |\'?){1,}\)/',

        'for'    => '/' . self::NOT_RAW . '\{( ?){1,}for( ){1,}(.*)( ){1,}in( ){1,}\((.*)( ?){1,},( ?){1,}(.*)( ?){1,}\)( ?){1,}\}/',
        'endfor' => '/' . self::NOT_RAW . '\{( ?){1,}endfor( ?){1,}\}/',

        'extends'      => '/' . self::NOT_RAW . '@extends\(\'(.*)\'\)/',
        'block'        => '/' . self::NOT_RAW . '{\[[ ?]{1,}block[ ]{1,}(' . self::BLOCK_NAME . ')[ ?]{1,}]}([\s\S]*?){\[[ ?]{1,}endblock[ ?]{1,}]}[\s]/',
        'parent_block' => '/' . self::NOT_RAW . '{\[[ ?]{1,}parent[ ]{1,}(' . self::BLOCK_NAME . ')[ ?]{1,}]}/'
    ];

    /**
     * List of custom templates
     *
     * @var array
     */
    private static $templates = [];


    /**
     * Returns true if the template system is enabled, false otherwise
     * @return bool true if the template system is enabled, false otherwise
     */
    public static function isEnabled()
    {
        return CONFIG['template_on'];
    }


    /**
     * Returns the view content rendered or false in case of errors.
     * The template format will be applied only if the template is enabled.
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the data array present in the view
     * @param  bool  $cache  use or not the cache system
     *
     * @return string the view content rendered or false in case of errors.
     */
    public static function getRender(string $dir, array $data, bool $cache)
    {
        $content = '';

        if (!($cache && Cache::isEnabled() && Cache::has($dir)) &&
            ($content = self::getContent($dir)) === false) {
            return false;
        }

        //Variables in data array
        if (is_array($data)) {
            extract($data);
            unset($data);
        }

        ob_start();

        //Cache system
        if ($cache && Cache::isEnabled()) {
            $content = Cache::has($dir) ?
                Cache::getContent($dir) :
                self::replaceAll(self::getContent($dir));

            include(Cache::set($dir, $content));
        } else {
            $tmp_file = tmpfile();
            fwrite($tmp_file, self::replaceAll($content));
            include(stream_get_meta_data($tmp_file)['uri']);
            fclose($tmp_file);
        }

        $rendered_content = ob_get_contents();
        ob_end_clean();

        return $rendered_content;
    }


    /**
     * Returns the view content with the template format applied
     * or false if it doesn't exists
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the data array present in the view
     *
     * @return string|bool the view content with the template format applied
     * or false if it doesn't exists
     */
    public static function get(string $dir, array $data, bool $cache)
    {
        $content = '';

        if (!($cache && Cache::isEnabled() && Cache::has($dir)) &&
            ($content = self::getContent($dir)) === false) {
            return false;
        }

        //Variables in data array
        if (is_array($data)) {
            extract($data);
            unset($data);
        }

        if ($cache && Cache::isEnabled() && Cache::has($dir)) {
            return Cache::getContent($dir);
        }

        return self::replaceAll($content);
    }


    /**
     * Returns the content of a view file
     *
     * @param  string  $dir  the view directory
     *
     * @return string The content of a view file
     */
    private static function getContent($dir)
    {
        $file_path = View::getPath($dir);

        if (!file_exists($file_path)) {
            throw new \Error("View '$dir' doesn't exists");
        }

        return file_get_contents($file_path);
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
        $content = self::replaceExtend($content);
        $content = self::replaceIncludes($content);
        $content = self::replaceImports($content);
        $content = self::replaceComments($content);
        $content = self::replaceFunctions($content);
        $content = self::replaceTags($content);
        $content = self::replaceCustom($content);
        $content = self::replaceCycles($content);
        $content = self::replaceRaws($content);

        return $content;
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

        $filename = Str::sanitizePath($matches[1]);
        $parent_content = self::getContent($filename);

        //Replace parent block imports in child view
        preg_match_all(self::FORMAT['parent_block'], $content, $parent_blocks);

        foreach ($parent_blocks[1] as $block_name) {
            $block_regex = str_replace(self::BLOCK_NAME, $block_name, self::FORMAT['block']);
            $parent_block_regex = str_replace(self::BLOCK_NAME, $block_name, self::FORMAT['parent_block']);
            preg_match($block_regex, $parent_content, $block_content);

            $content = preg_replace($parent_block_regex, trim($block_content[2] ?? ''), $content);
        }

        //Replace parent blocks with child blocks content
        preg_match_all(self::FORMAT['block'], $content, $child_blocks);

        foreach ($child_blocks[1] as $key => $block_name) {
            $block_regex = str_replace(self::BLOCK_NAME, $block_name, self::FORMAT['block']);
            $parent_content = preg_replace($block_regex, trim($child_blocks[2][$key]), $parent_content);
        }

        //Remove remaining tags
        $parent_content = preg_replace(self::FORMAT['block'], '', $parent_content);
        $parent_content = preg_replace(self::FORMAT['parent_block'], '', $parent_content);

        return $parent_content;
    }


    /**
     * Adds a custom template
     *
     * @param  mixed  $function  the function with the custom template
     */
    public static function custom($function) {
        if (!is_callable($function)) {
            return;
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
    private static function replaceCustom(string $content) {
        if (empty(self::$templates)) {
            return $content;
        }

        foreach(self::$templates as $template) {
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

        foreach ($matches[1] as $key => $value) {
            $filename = Str::sanitizePath($matches[2][$key][0]);
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
        $func = '(.*)';

        //Escape
        $content = preg_replace(str_replace($func, 'e', self::FORMAT['function']), 'htmlspecialchars(strip_tags($2))',
            $content);
        //Uppercase
        $content = preg_replace(str_replace($func, 'upper', self::FORMAT['function']), 'strtoupper($2)', $content);
        //Lowercase
        $content = preg_replace(str_replace($func, 'lower', self::FORMAT['function']), 'strtolower($2)', $content);
        //First uppercase
        $content = preg_replace(str_replace($func, 'upperf', self::FORMAT['function']), 'ucfirst($2)', $content);
        //Length
        $content = preg_replace(str_replace($func, 'length', self::FORMAT['function']), 'strlen($2)', $content);
        //Count
        $content = preg_replace(str_replace($func, 'count', self::FORMAT['function']), 'count($2)', $content);
        //Title case
        $content = preg_replace(str_replace($func, 'title', self::FORMAT['function']), 'ucwords($2)', $content);
        //MD5
        $content = preg_replace(str_replace($func, 'md5', self::FORMAT['function']), 'md5($2)', $content);
        //Count words
        $content = preg_replace(str_replace($func, 'countwords', self::FORMAT['function']), 'str_word_count($2)', $content);
        //Trim
        $content = preg_replace(str_replace($func, 'trim', self::FORMAT['function']), 'trim($2)', $content);
        //nl2br
        $content = preg_replace(str_replace($func, 'nl2br', self::FORMAT['function']), 'nl2br($2)', $content);
        //Join
        $content = preg_replace(str_replace($func, 'join\((.*?)\)', self::FORMAT['function']), 'implode($1, $3)', $content);
        //Repeat
        $content = preg_replace(str_replace($func, 'repeat\((.*?)\)', self::FORMAT['function']), 'str_repeat($3, $1)', $content);

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
        $content = preg_replace(self::FORMAT['plainecho'], '<?php echo $2 ?>', $content);
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
        $content = preg_replace(self::FORMAT['for'], '<?php for (\$$3=$6; \$$3 <= $9; \$$3++): ?>', $content);
        $content = preg_replace(self::FORMAT['endfor'], '<?php endfor; ?>', $content);

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
