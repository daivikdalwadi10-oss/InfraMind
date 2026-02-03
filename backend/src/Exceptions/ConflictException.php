<?php

declare(strict_types=1);

namespace InfraMind\Exceptions;

/**
 * Conflict/duplicate resource exception.
 */
class ConflictException extends Exception
{
    public function __construct(string $message = 'Conflict', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
