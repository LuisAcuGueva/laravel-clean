<?php

namespace App\Domain\Client\Exceptions;

use RuntimeException;

final class DuplicateEmailException extends RuntimeException
{
    public static function forEmail(string $email): self
    {
        return new self("Ya existe un cliente con el email {$email}.");
    }
}
