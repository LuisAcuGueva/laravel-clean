<?php

namespace App\Application\Client\UseCases;

use App\Domain\Client\Entities\Client;
use App\Domain\Client\Repositories\ClientRepositoryInterface;
use App\Domain\Shared\Pagination\PaginatedResult;

final class ListClients
{
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    /**
     * @return PaginatedResult<Client>
     */
    public function execute(int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): PaginatedResult
    {
        return $this->clients->paginate(max(1, $page), max(1, $perPage));
    }
}
