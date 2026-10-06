<?php

namespace App\Application\Client\UseCases;

use App\Domain\Client\Exceptions\ClientNotFoundException;
use App\Domain\Client\Repositories\ClientRepositoryInterface;

final class DeleteClient
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    /**
     * @throws ClientNotFoundException
     */
    public function execute(int $id): void
    {
        $client = $this->clients->find($id) ?? throw ClientNotFoundException::withId($id);

        $this->clients->delete($client);
    }
}
