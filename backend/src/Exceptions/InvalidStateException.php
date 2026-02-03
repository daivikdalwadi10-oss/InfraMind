<?php

declare(strict_types=1);

namespace InfraMind\Exceptions;

/**
 * Invalid state transition exception.
 */
class InvalidStateException extends Exception
{
    public function __construct(string $message = 'Invalid state transition', int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
