<?php

namespace App\Application\Client\UseCases;

use App\Application\Client\DTOs\ClientData;
use App\Domain\Client\Entities\Client;
use App\Domain\Client\Exceptions\DuplicateEmailException;
use App\Domain\Client\Repositories\ClientRepositoryInterface;

final class CreateClient
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    /**
     * @throws DuplicateEmailException
     */
    public function execute(ClientData $data): Client
    {
        $client = Client::create($data->name, $data->email, $data->phone, $data->address);

        if ($this->clients->findByEmail($client->email()) !== null) {
            throw DuplicateEmailException::forEmail($client->email());
        }

        return $this->clients->save($client);
    }
}
