<?php

namespace App\Domain\Client\Repositories;

use App\Domain\Client\Entities\Client;
use App\Domain\Shared\Pagination\PaginatedResult;

interface ClientRepositoryInterface
{
    /**
     * @return PaginatedResult<Client>
     */
    public function paginate(int $page, int $perPage): PaginatedResult;

    public function find(int $id): ?Client;

    public function findByEmail(string $email): ?Client;

    /**
     * Persist the client and return it with its identifier assigned.
     */
    public function save(Client $client): Client;

    public function delete(Client $client): void;
}
