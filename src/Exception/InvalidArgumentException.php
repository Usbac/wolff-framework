<?php

namespace Wolff\Exception;

final class InvalidArgumentException extends \Exception
{

    const MSG = '\'%s\' must be %s';

    /**
     * Constructor
     */
    public function __construct($name, $type)
    {
        parent::__construct(sprintf(self::MSG, $name, $type));
    }
}
