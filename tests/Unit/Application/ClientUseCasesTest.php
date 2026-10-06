<?php

namespace Tests\Unit\Application;

use App\Application\Client\DTOs\ClientData;
use App\Application\Client\UseCases\CreateClient;
use App\Application\Client\UseCases\DeleteClient;
use App\Application\Client\UseCases\GetClient;
use App\Application\Client\UseCases\ListClients;
use App\Application\Client\UseCases\UpdateClient;
use App\Domain\Client\Exceptions\ClientNotFoundException;
use App\Domain\Client\Exceptions\DuplicateEmailException;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryClientRepository;

class ClientUseCasesTest extends TestCase
{
    private InMemoryClientRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryClientRepository;
    }

    public function test_create_client_assigns_id_and_normalizes_email(): void
    {
        $client = (new CreateClient($this->repository))->execute(
            new ClientData(name: '  Ana Pérez ', email: 'ANA@Example.com ', phone: '5555-0000'),
        );

        $this->assertSame(1, $client->id());
        $this->assertSame('Ana Pérez', $client->name());
        $this->assertSame('ana@example.com', $client->email());
        $this->assertSame('5555-0000', $client->phone());
    }

    public function test_create_client_rejects_duplicate_email(): void
    {
        $create = new CreateClient($this->repository);
        $create->execute(new ClientData(name: 'Uno', email: 'dup@example.com'));

        $this->expectException(DuplicateEmailException::class);

        $create->execute(new ClientData(name: 'Dos', email: 'DUP@example.com'));
    }

    public function test_update_client_can_keep_its_own_email(): void
    {
        $created = (new CreateClient($this->repository))->execute(
            new ClientData(name: 'Uno', email: 'uno@example.com'),
        );

        $updated = (new UpdateClient($this->repository))->execute(
            $created->id(),
            new ClientData(name: 'Uno Editado', email: 'uno@example.com', address: 'Zona 1'),
        );

        $this->assertSame('Uno Editado', $updated->name());
        $this->assertSame('Zona 1', $updated->address());
    }

    public function test_update_client_rejects_email_of_another_client(): void
    {
        $create = new CreateClient($this->repository);
        $create->execute(new ClientData(name: 'Otro', email: 'otro@example.com'));
        $mine = $create->execute(new ClientData(name: 'Mío', email: 'mio@example.com'));

        $this->expectException(DuplicateEmailException::class);

        (new UpdateClient($this->repository))->execute(
            $mine->id(),
            new ClientData(name: 'Mío', email: 'otro@example.com'),
        );
    }

    public function test_update_missing_client_throws(): void
    {
        $this->expectException(ClientNotFoundException::class);

        (new UpdateClient($this->repository))->execute(99, new ClientData(name: 'X', email: 'x@example.com'));
    }

    public function test_get_client_returns_it_or_throws(): void
    {
        $created = (new CreateClient($this->repository))->execute(
            new ClientData(name: 'Ana', email: 'ana@example.com'),
        );

        $this->assertSame($created->id(), (new GetClient($this->repository))->execute($created->id())->id());

        $this->expectException(ClientNotFoundException::class);

        (new GetClient($this->repository))->execute(999);
    }

    public function test_list_clients_returns_paginated_result_newest_first(): void
    {
        $create = new CreateClient($this->repository);
        for ($i = 1; $i <= 5; $i++) {
            $create->execute(new ClientData(name: "Cliente {$i}", email: "c{$i}@example.com"));
        }

        $page = (new ListClients($this->repository))->execute(page: 2, perPage: 2);

        $this->assertSame(5, $page->total);
        $this->assertSame(2, $page->perPage);
        $this->assertSame(2, $page->currentPage);
        $this->assertSame(3, $page->lastPage());
        $this->assertSame(['Cliente 3', 'Cliente 2'], array_map(fn ($c) => $c->name(), $page->items));
        $this->assertTrue($page->hasPrevious());
        $this->assertTrue($page->hasNext());
    }

    public function test_list_clients_defaults_to_first_page_and_clamps_invalid_values(): void
    {
        $create = new CreateClient($this->repository);
        $create->execute(new ClientData(name: 'Uno', email: 'uno@example.com'));

        $page = (new ListClients($this->repository))->execute(page: 0, perPage: 0);

        $this->assertSame(1, $page->currentPage);
        $this->assertSame(1, $page->perPage);
        $this->assertFalse($page->hasPrevious());
        $this->assertFalse($page->hasNext());
    }

    public function test_list_clients_with_no_rows_has_single_empty_page(): void
    {
        $page = (new ListClients($this->repository))->execute();

        $this->assertSame([], $page->items);
        $this->assertSame(0, $page->total);
        $this->assertSame(1, $page->lastPage());
    }

    public function test_delete_client_removes_it(): void
    {
        $created = (new CreateClient($this->repository))->execute(
            new ClientData(name: 'Ana', email: 'ana@example.com'),
        );

        (new DeleteClient($this->repository))->execute($created->id());

        $this->assertNull($this->repository->find($created->id()));
    }
}
