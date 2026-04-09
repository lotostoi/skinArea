<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class InvalidDealStateException extends RuntimeException
{
    public function __construct(string $message = 'Недопустимый переход статуса сделки')
    {
        parent::__construct($message);
    }
}
