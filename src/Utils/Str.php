<?php

namespace Wolff\Utils;

use Wolff\Core\Helper;

final class Str
{

    const DEFAULT_ENCODING = 'UTF-8';
    const SLUG_CHARS = [
        'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
        'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
        'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
        'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b',
        'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', '/' => '-', ' ' => '-'
    ];


    /**
     * Sanitizes an url
     *
     * @param  string  $url the url
     *
     * @return string the url sanitized
     */
    public static function sanitizeUrl(string $url)
    {
        return filter_var(rtrim($url, '/'), FILTER_SANITIZE_URL);
    }


    /**
     * Sanitizes an email
     *
     * @param  string  $email the email
     *
     * @return string the email sanitized
     */
    public static function sanitizeEmail(string $email)
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }


    /**
     * Sanitizes an string to integer (only numbers and +-)
     *
     * @param  string  $int the integer
     *
     * @return string the integer sanitized
     */
    public static function sanitizeInt(string $int)
    {
        return filter_var($int, FILTER_SANITIZE_NUMBER_INT);
    }


    /**
     * Sanitizes an string to float (only numbers, fractions and +-)
     *
     * @param  string  $float the float
     *
     * @return string the float sanitized
     */
    public static function sanitizeFloat(string $float)
    {
        return filter_var($float, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }


    /**
     * Sanitizes the given path for only letters, numbers, underscores, dots and slashes
     *
     * @param  string  $path the path
     *
     * @return string the path sanitized
     */
    public static function sanitizePath(string $path)
    {
        return preg_replace('/[^a-zA-Z0-9_\-\/. ]/', '', $path);
    }


    /**
     * Returns the given string without the single or double
     * quotes surrounding it
     *
     * @param  string  $str  the string
     * @return string the string without single or double
     * quotes surrounding it
     */
    public static function removeQuotes(string $str)
    {
        return Helper::removeQuotes($str);
    }


    /**
     * Returns a friendly url string based on the given string.
     *
     * @param  string  $str the original string
     *
     * @return string the url friendly string
     */
    public static function slug(string $str)
    {
        //Trim whitespaces and change special characters by their normal counterpart
        $str = strtr(trim($str), self::SLUG_CHARS);

        //Remove remaining special characters
        $str = preg_replace('/[^a-zA-Z0-9-]+/', '-', $str);

        //Remove followed and duplicated hyphen characters
        $str = preg_replace('/-{2,}/', '-', $str);

        return mb_strtolower($str);
    }


    /**
     * Returns a random generated token
     *
     * @param  int  $length  the token length,
     * by default it is 16 characters
     *
     * @return string a random generated token
     */
    public static function token(int $length = 16)
    {
        return bin2hex(random_bytes($length / 2));
    }


    /**
     * Returns true if the given string is a valid email,
     * false otherwise
     *
     * @param  string  $email the email string
     *
     * @return bool Returns true if the given string is a valid email,
     * false otherwise
     */
    public static function isEmail(string $email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }


    /**
     * Returns true if the given string contains only
     * alphanumeric characters, false otherwise
     *
     * @param  string  $str the string
     *
     * @return bool Returns true if the given string contains only
     * alphanumeric characters, false otherwise
     */
    public static function isAlphanumeric(string $str)
    {
        return preg_match('/^[\w-]*$/', $str) == true;
    }


    /**
     * Returns true if the given string contains only
     * letters, false otherwise
     *
     * @param  string  $str the string
     *
     * @return bool Returns true if the given string contains only
     * letters, false otherwise
     */
    public static function isAlpha(string $str)
    {
        return preg_match('/[^A-Za-z]+/', $str) == false;
    }


    /**
     * Returns true if a substring is present in another string
     * or false otherwise
     *
     * @param  string  $str  the string
     * @param  string  $needle  the substring you are looking for
     *
     * @return boolean true if the substring is present in the other string, false otherwise
     */
    public static function contains(string $str, string $needle)
    {
        return mb_strpos($str, $needle) !== false;
    }


    /**
     * Returns a string with its placeholders replaced by context values
     * The values must be enclosed by curly braces
     * Example: Your age is {age}
     *
     * @param  string  $str  the string
     * @param  array  $values  the context values for the placeholders
     *
     * @return string the string with its placeholders replaced by context values
     */
    public static function interpolate(string $str, array $values)
    {
        foreach ($values as $key => $val) {
            if (!is_object($val)) {
                $str = str_replace('{' . $key . '}', strval($val), $str);
            }
        }

        return $str;
    }


    /**
     * Returns the string with the indicated substrings swaped
     * or false in case of errors.
     *
     * @param  string  $str  the string
     * @param  string  $first_str  the first string
     * @param  string  $second_str  the second string
     *
     * @return string the string with the indicated substrings swaped
     * or false in case of errors.
     */
    public static function swap(string $str, string $first_str, string $second_str)
    {
        return strtr($str, [
            $first_str  => $second_str,
            $second_str => $first_str
        ]);
    }


    /**
     * Converts a string with any encoding to UTF-8 and returns it
     * Keep in mind that the string encoding detection is not perfect
     *
     * @param  string  $str  the string
     *
     * @return string the string encoded in UTF-8
     */
    public static function toUtf8(string $str)
    {
        $encoding = mb_detect_encoding($str, mb_detect_order(), true);
        $encoded = iconv($encoding, 'UTF-8', $str);

        if (empty($encoded)) {
            return utf8_encode($str);
        }

        return $encoded;
    }


    /**
     * Returns true if a string starts with another string, false otherwise
     *
     * @param  string  $str  the string
     * @param  string  $needle  the substring
     *
     * @return boolean true if a string starts with another string, false otherwise
     */
    public static function startsWith(string $str, string $needle)
    {
        return mb_strpos($str, $needle) === 0;
    }


    /**
     * Returns true if a string ends with another string, false otherwise
     *
     * @param  string  $str  the string
     * @param  string  $needle  the substring
     *
     * @return boolean true if a string ends with another string, false otherwise
     */
    public static function endsWith(string $str, string $needle)
    {
        return Helper::endsWith($str, $needle);
    }


    /**
     * Returns a string with the indicated substring removed
     *
     * @param  string  $str  the string
     * @param  string  $needle  substring to remove
     *
     * @return string the string with the indicated substring removed
     */
    public static function remove(string $str, string $needle)
    {
        return str_replace($needle, '', $str);
    }


    /**
     * Returns everything after the specified substring,
     * or false if the substring is not in the string.
     *
     * @param  string  $str  the string
     * @param  string  $needle  substring
     *
     * @return string a string with everything after the specified substring,
     * or false if the substring is not in the string.
     */
    public static function after(string $str, string $needle)
    {
        if (!self::contains($str, $needle)) {
            return '';
        }

        return mb_substr($str, mb_strpos($str, $needle) + mb_strlen($needle));
    }


    /**
     * Returns everything before the specified substring,
     * or false if the substring is not in the string.
     *
     * @param  string  $str  the string
     * @param  string  $needle  the substring
     *
     * @return string a string with everything before the specified substring,
     * or false if the substring is not in the string.
     */
    public static function before(string $str, string $needle)
    {
        if (!self::contains($str, $needle)) {
            return '';
        }

        return mb_substr($str, 0, mb_strpos($str, $needle));
    }


    /**
     * Returns a truncated string with the specified length
     *
     * @param  string  $str  the string
     * @param  int  $limit  the number of characters to limit
     *
     * @return string a truncated string with the specified length
     */
    public static function limit(string $str, int $limit)
    {
        if (mb_strwidth($str, self::DEFAULT_ENCODING) <= $limit) {
            return $str;
        }

        return mb_strimwidth($str, 0, $limit, '', self::DEFAULT_ENCODING);
    }


    /**
     * Returns the given paths concatenated
     *
     * @param  mixed  ...$paths  the paths
     *
     * @return string the given paths concatenated
     */
    public static function concatPath(...$paths)
    {
        $final_path = [];

        foreach ($paths as $path) {
            if (is_array($path)) {
                $path = implode('/', $path);
            }

            $final_path[] = $path;
        }

        return mb_ereg_replace('/\/+/', '/', implode('/', $final_path));
    }


    /**
     * Returns all the given strings concatenated into one
     *
     * @param  mixed  ...$strings  the strings
     *
     * @return string all the given strings concatenated into one
     */
    public static function concat(...$strings)
    {
        $aux = '';

        foreach ($strings as $string) {
            if (is_string($string)) {
                $aux .= $string;
            }
        }

        return $aux;
    }


    /**
     * Returns the given value to a string
     *
     * @param  mixed  $var  the value
     *
     * @return string the given value as a string
     */
    public static function toString($var)
    {
        //Boolean
        if (is_bool($var)) {
            if ($var === true) {
                return 'true';
            }

            return 'false';
        }

        //Array
        if (is_array($var)) {
            $str = '';

            foreach ($var as $value) {
                $str .= $value;
            }

            return $str;
        }

        //Other
        return strval($var);
    }
}
