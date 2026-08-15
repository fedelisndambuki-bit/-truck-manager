<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a status transition's guarded update affects zero rows —
 * meaning the request was already moved to a different state by someone
 * else (e.g. two SuperAdmins racing to approve/reject the same request).
 */
class AccessRequestStateConflictException extends Exception
{
    public function __construct(string $message = 'This request was just updated by someone else. Please refresh and try again.')
    {
        parent::__construct($message);
    }
}
