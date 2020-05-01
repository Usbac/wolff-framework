<?php

namespace Wolff\Exception;

final class InvalidLanguageException extends \Exception
{

    /**
     * Default constructor
     */
    public function __construct(string $msg, string $language, string $dir)
    {
        parent::__construct(sprintf($msg, $language, $dir));
    }
}
