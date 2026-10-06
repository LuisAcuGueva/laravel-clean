<?php

namespace App\Presentation\Http\Controllers;

use App\Application\Client\DTOs\ClientData;
use App\Application\Client\UseCases\CreateClient;
use App\Application\Client\UseCases\DeleteClient;
use App\Application\Client\UseCases\GetClient;
use App\Application\Client\UseCases\ListClients;
use App\Application\Client\UseCases\UpdateClient;
use App\Domain\Client\Exceptions\ClientNotFoundException;
use App\Domain\Client\Exceptions\DuplicateEmailException;
use App\Http\Controllers\Controller;
use App\Presentation\Http\Requests\StoreClientRequest;
use App\Presentation\Http\Requests\UpdateClientRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private readonly ListClients $listClients,
        private readonly GetClient $getClient,
        private readonly CreateClient $createClient,
        private readonly UpdateClient $updateClient,
        private readonly DeleteClient $deleteClient,
    ) {}

    public function index(Request $request): View
    {
        return view('clients.index', [
            'clients' => $this->listClients->execute(page: $request->integer('page', 1)),
        ]);
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        try {
            $client = $this->createClient->execute(ClientData::fromArray($request->validated()));
        } catch (DuplicateEmailException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()
            ->route('clients.show', $client->id())
            ->with('status', 'Cliente creado correctamente.');
    }

    public function show(int $client): View
    {
        try {
            return view('clients.show', [
                'client' => $this->getClient->execute($client),
            ]);
        } catch (ClientNotFoundException) {
            abort(404);
        }
    }

    public function edit(int $client): View
    {
        try {
            return view('clients.edit', [
                'client' => $this->getClient->execute($client),
            ]);
        } catch (ClientNotFoundException) {
            abort(404);
        }
    }

    public function update(UpdateClientRequest $request, int $client): RedirectResponse
    {
        try {
            $updated = $this->updateClient->execute($client, ClientData::fromArray($request->validated()));
        } catch (ClientNotFoundException) {
            abort(404);
        } catch (DuplicateEmailException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()
            ->route('clients.show', $updated->id())
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(int $client): RedirectResponse
    {
        try {
            $this->deleteClient->execute($client);
        } catch (ClientNotFoundException) {
            abort(404);
        }

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente eliminado correctamente.');
    }
}
