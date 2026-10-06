<?php

namespace App\Application\Client\UseCases;

use App\Domain\Client\Entities\Client;
use App\Domain\Client\Exceptions\ClientNotFoundException;
use App\Domain\Client\Repositories\ClientRepositoryInterface;

final class GetClient
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    /**
     * @throws ClientNotFoundException
     */
    public function execute(int $id): Client
    {
        return $this->clients->find($id) ?? throw ClientNotFoundException::withId($id);
    }
}
