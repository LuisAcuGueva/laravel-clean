<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Client\Entities\Client;
use App\Domain\Client\Repositories\ClientRepositoryInterface;
use App\Domain\Shared\Pagination\PaginatedResult;

final class EloquentClientRepository implements ClientRepositoryInterface
{
    /**
     * @return PaginatedResult<Client>
     */
    public function paginate(int $page, int $perPage): PaginatedResult
    {
        $paginator = ClientModel::query()
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return new PaginatedResult(
            items: $paginator->getCollection()
                ->map(fn (ClientModel $model) => $this->toDomain($model))
                ->values()
                ->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id): ?Client
    {
        $model = ClientModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    /**
     * Includes soft-deleted rows, because the unique index on "email" does too.
     */
    public function findByEmail(string $email): ?Client
    {
        $model = ClientModel::withTrashed()->where('email', $email)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Client $client): Client
    {
        $isNew = $client->id() === null;

        $model = $isNew
            ? new ClientModel
            : ClientModel::findOrFail($client->id());

        $model->fill([
            'name' => $client->name(),
            'email' => $client->email(),
            'phone' => $client->phone(),
            'address' => $client->address(),
        ]);
        $model->save();

        if ($isNew) {
            $client->assignId($model->id);
        }

        return $client;
    }

    public function delete(Client $client): void
    {
        ClientModel::findOrFail($client->id())->delete();
    }

    private function toDomain(ClientModel $model): Client
    {
        return Client::reconstitute(
            id: $model->id,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            address: $model->address,
        );
    }
}
