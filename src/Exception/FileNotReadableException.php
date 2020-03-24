<?php

namespace Wolff\Exception;

final class FileNotReadableException extends \Exception
{

    const MSG = 'The file \'%s\' is not readable or does not exists';

    /**
     * Default constructor
     */
    public function __construct(string $file_path)
    {
        parent::__construct(sprintf(self::MSG, $file_path));
    }
}
