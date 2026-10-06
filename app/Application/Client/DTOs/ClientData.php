<?php

namespace App\Application\Client\DTOs;

final readonly class ClientData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $address = null,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, address?: ?string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
        );
    }
}
