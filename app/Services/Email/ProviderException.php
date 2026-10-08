<?php

namespace App\Services\Email;

use RuntimeException;

class ProviderException extends RuntimeException
{
    public function __construct(string $message, public bool $retryable = false, public int $retryAfter = 30, public ?int $status = null)
    {
        parent::__construct($message);
    }
}
