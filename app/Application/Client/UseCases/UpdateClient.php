<?php

namespace App\Application\Client\UseCases;

use App\Application\Client\DTOs\ClientData;
use App\Domain\Client\Entities\Client;
use App\Domain\Client\Exceptions\ClientNotFoundException;
use App\Domain\Client\Exceptions\DuplicateEmailException;
use App\Domain\Client\Repositories\ClientRepositoryInterface;

final class UpdateClient
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    /**
     * @throws ClientNotFoundException
     * @throws DuplicateEmailException
     */
    public function execute(int $id, ClientData $data): Client
    {
        $client = $this->clients->find($id) ?? throw ClientNotFoundException::withId($id);

        $client->update($data->name, $data->email, $data->phone, $data->address);

        $existing = $this->clients->findByEmail($client->email());

        if ($existing !== null && $existing->id() !== $client->id()) {
            throw DuplicateEmailException::forEmail($client->email());
        }

        return $this->clients->save($client);
    }
}
