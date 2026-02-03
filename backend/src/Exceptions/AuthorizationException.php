<?php

declare(strict_types=1);

namespace InfraMind\Exceptions;

/**
 * Authorization/permission failure exception.
 */
class AuthorizationException extends Exception
{
    public function __construct(string $message = 'Forbidden', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
