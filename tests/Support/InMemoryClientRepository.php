<?php

namespace Tests\Support;

use App\Domain\Client\Entities\Client;
use App\Domain\Client\Repositories\ClientRepositoryInterface;
use App\Domain\Shared\Pagination\PaginatedResult;

final class InMemoryClientRepository implements ClientRepositoryInterface
{
    /** @var array<int, Client> */
    private array $items = [];

    private int $nextId = 1;

    /**
     * Mirrors the Eloquent implementation: newest first (by id), then sliced.
     */
    public function paginate(int $page, int $perPage): PaginatedResult
    {
        $sorted = array_values($this->items);
        usort($sorted, fn (Client $a, Client $b) => $b->id() <=> $a->id());

        return new PaginatedResult(
            items: array_slice($sorted, ($page - 1) * $perPage, $perPage),
            total: count($sorted),
            perPage: $perPage,
            currentPage: $page,
        );
    }

    public function find(int $id): ?Client
    {
        return $this->items[$id] ?? null;
    }

    public function findByEmail(string $email): ?Client
    {
        foreach ($this->items as $client) {
            if ($client->email() === $email) {
                return $client;
            }
        }

        return null;
    }

    public function save(Client $client): Client
    {
        if ($client->id() === null) {
            $client->assignId($this->nextId++);
        }

        $this->items[$client->id()] = $client;

        return $client;
    }

    public function delete(Client $client): void
    {
        unset($this->items[$client->id()]);
    }
}
