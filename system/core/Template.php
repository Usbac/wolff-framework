<?php

namespace Core;

use Utilities\Str;

class Template
{

    /**
     * List of custom templates
     *
     * @var array
     */
    private static $templates = [];

    const RAW = '~';
    const NOT_RAW = '(?<!' . self::RAW . ')';

    const FORMAT = [
        'comment'   => '/' . self::NOT_RAW . '\{#(?s).[^#\}]*#\}/',
        'plainecho' => '/' . self::NOT_RAW . '\{\!( ?){1,}(.*?)( ?){1,}\!\}/',
        'echo'      => '/' . self::NOT_RAW . '\{\{( ?){1,}(.*?)( ?){1,}\}\}/',
        'tag'       => '/' . self::NOT_RAW . '\{%( ?){1,}(.*?)( ?){1,}%\}/',
        'function'  => '/' . self::NOT_RAW . '{func}( ?){1,}\|([^\}!]{1,})/',
        'include'   => '/' . self::NOT_RAW . '@load\(( |\'?){1,}(.*)(.php|.html)( |\'?){1,}\)/',

        'if'     => '/' . self::NOT_RAW . '\{(\s?){1,}(.*)\?(\s?){1,}\}/',
        'endif'  => '/' . self::NOT_RAW . '\{\?\}/',
        'else'   => '/' . self::NOT_RAW . '\{(\s?){1,}else(\s?){1,}\}/',
        'elseif' => '/' . self::NOT_RAW . '\{(\s?){1,}else(\s?){1,}(.*)(\s?){1,}\}/',

        'for'        => '/' . self::NOT_RAW . '\{( ?){1,}for( ){1,}(.*)( ){1,}in( ){1,}\((.*)( ?){1,},( ?){1,}(.*)( ?){1,}\)( ?){1,}\}/',
        'endfor'     => '/' . self::NOT_RAW . '\{( ?){1,}for( ?){1,}\}/',
        'foreach'    => '/' . self::NOT_RAW . '\{( ?){1,}foreach( ?){1,}(.*)( ?){1,}as( ?){1,}(.*)( ?){1,}\}/',
        'endforeach' => '/' . self::NOT_RAW . '\{( ?){1,}foreach( ?){1,}\}/',
    ];


    public function __construct()
    {
    }


    /**
     * Returns true if the template system is enabled, false otherwise
     * @return bool true if the template system is enabled, false otherwise
     */
    public static function isEnabled()
    {
        return CONFIG['template_on'];
    }


    /**
     * Apply the template format over a content and render it.
     * The template format will be applied only if the template is enabled.
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the data array present in the view
     * @param  bool  $cache  use or not the cache system
     *
     * @return string the view content
     */
    public function get(string $dir, array $data, bool $cache)
    {
        //Variables in data array
        if (is_array($data)) {
            extract($data);
            unset($data);
        }

        $content = $this->getContent($dir);

        if ($content === false) {
            return false;
        }

        if ($this->isEnabled()) {
            $content = $this->replaceAll($content);
        }

        //Cache system
        if ($cache && Cache::isEnabled()) {
            include(Cache::set($dir, $content));
        } else {
            $temp = tmpfile();
            fwrite($temp, $content);
            include(stream_get_meta_data($temp)['uri']);
            fclose($temp);
        }

        return $content;
    }


    /**
     * Apply the template format over a view content and return it
     *
     * @param  string  $dir  the view directory
     * @param  array  $data  the data array present in the view
     *
     * @return string|bool the view content or false if it doesn't exists
     */
    public function getView(string $dir, array $data)
    {
        //Variables in data array
        if (is_array($data)) {
            extract($data);
            unset($data);
        }

        $content = $this->getContent($dir);

        if ($content === false) {
            return false;
        }

        return $this->replaceAll($content);
    }


    /**
     * Get the content of a view file
     *
     * @param  string  $dir  the view directory
     *
     * @return string|bool the view content or false if it doesn't exists
     */
    private function getContent($dir)
    {
        $file_path = getAppDirectory() . 'views/' . $dir;

        if (file_exists($file_path . '.php')) {
            return file_get_contents($file_path . '.php');
        } elseif (file_exists($file_path . '.html')) {
            return file_get_contents($file_path . '.html');
        } else {
            Log::error("View '$dir' doesn't exists");

            return false;
        }
    }


    /**
     * Add a custom template
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
     * Apply the custom templates
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the custom templates formatted
     */
    private function replaceCustom(string $content) {
        if (empty(self::$templates)) {
            return $content;
        }

        foreach(self::$templates as $template) {
            $content = $template($content);
        }

        return $content;
    }


    /**
     * Apply all the replace methods of the template
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the template replaced
     */
    private function replaceAll(string $content)
    {
        $content = $this->replaceIncludes($content);
        $content = $this->replaceComments($content);
        $content = $this->replaceFunctions($content);
        $content = $this->replaceTags($content);
        $content = $this->replaceConditionals($content);
        $content = $this->replaceCustom($content);
        $content = $this->replaceCycles($content);
        $content = $this->replaceRaws($content);

        return $content;
    }


    /**
     * Apply the template includes
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the includes formatted
     */
    private function replaceIncludes($content)
    {
        preg_match_all(self::FORMAT['include'], $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $key => $value) {
            $filename = Str::sanitizePath($matches[2][$key][0]);
            $content = str_replace($matches[0][$key][0], $this->getContent($filename), $content);
        }

        return $content;
    }


    /**
     * Apply the template functions
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the functions formatted
     */
    private function replaceFunctions($content)
    {
        $func = '{func}';

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
     * Apply the template format over the tags of a content
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the tags formatted
     */
    private function replaceTags($content)
    {
        $content = preg_replace(self::FORMAT['echo'], '<?php echo htmlspecialchars($2, ENT_QUOTES) ?>', $content);
        $content = preg_replace(self::FORMAT['plainecho'], '<?php echo $2 ?>', $content);
        $content = preg_replace(self::FORMAT['tag'], '<?php $2 ?>', $content);

        return $content;
    }


    /**
     * Apply the template format over the conditionals of a content
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the conditionals formatted
     */
    private function replaceConditionals($content)
    {
        $content = preg_replace(self::FORMAT['endif'], '<?php endif; ?>', $content);
        $content = preg_replace(self::FORMAT['if'], '<?php if ($2): ?>', $content);
        $content = preg_replace(self::FORMAT['else'], '<?php else: ?>', $content);
        $content = preg_replace(self::FORMAT['elseif'], '<?php elseif ($3): ?>', $content);

        return $content;
    }


    /**
     * Apply the template format over the cycles of a content
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the cycles formatted
     */
    private function replaceCycles($content)
    {
        //For
        $content = preg_replace(self::FORMAT['for'], '<?php for (\$$3=$6; \$$3 <= $9; \$$3++): ?>', $content);
        $content = preg_replace(self::FORMAT['endfor'], '<?php endfor; ?>', $content);
        //Foreach
        $content = preg_replace(self::FORMAT['foreach'], '<?php foreach ($3 as $6): ?>', $content);
        $content = preg_replace(self::FORMAT['endforeach'], '<?php endforeach; ?>', $content);

        return $content;
    }


    /**
     * Remove the comments of a content
     *
     * @param  string  $content  the view content
     *
     * @return string the view content without the comments
     */
    private function replaceComments($content)
    {
        return preg_replace(self::FORMAT['comment'], '', $content);
    }


    /**
     * Remove the raw tag from the rest of the tags
     *
     * @param  string  $content  the view content
     *
     * @return string the view content with the raw tag removed from the rest of the tags
     */
    private function replaceRaws($content)
    {
        foreach (self::FORMAT as $format) {
            $format = trim($format, '/');
            $format = Str::remove($format, self::NOT_RAW);

            $content = preg_replace('/' . self::RAW . '(' . $format . ')/', '$1', $content);
        }

        return $content;
    }
}
