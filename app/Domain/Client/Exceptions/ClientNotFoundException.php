<?php

namespace App\Domain\Client\Exceptions;

use RuntimeException;

final class ClientNotFoundException extends RuntimeException
{
    public static function withId(int $id): self
    {
        return new self("No se encontró el cliente con id {$id}.");
    }
}
