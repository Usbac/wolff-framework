<?php

namespace Wolff\Exception;

final class BadControllerCallException extends \Exception
{

    /**
     * Default constructor
     */
    public function __construct(string $msg, ...$args)
    {
        parent::__construct(sprintf($msg, ...$args));
    }
}
