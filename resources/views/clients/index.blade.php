@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="card">
        <div class="actions" style="justify-content: space-between; margin-bottom: 1rem;">
            <h1 style="margin: 0;">Clientes</h1>
            <a class="btn" href="{{ route('clients.create') }}">Nuevo cliente</a>
        </div>

        @if ($clients->total === 0)
            <p class="muted">Todavía no hay clientes registrados.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($clients->items as $client)
                    <tr>
                        <td>{{ $client->name() }}</td>
                        <td>{{ $client->email() }}</td>
                        <td>{{ $client->phone() ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-secondary" href="{{ route('clients.show', $client->id()) }}">Ver</a>
                                <a class="btn" href="{{ route('clients.edit', $client->id()) }}">Editar</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <nav class="actions" style="justify-content: space-between; margin-top: 1rem;" aria-label="Paginación">
                <span class="muted">Página {{ $clients->currentPage }} de {{ $clients->lastPage() }} · {{ $clients->total }} en total</span>
                <div class="actions">
                    @if ($clients->hasPrevious())
                        <a class="btn btn-secondary" href="{{ route('clients.index', ['page' => $clients->currentPage - 1]) }}">Anterior</a>
                    @endif
                    @if ($clients->hasNext())
                        <a class="btn" href="{{ route('clients.index', ['page' => $clients->currentPage + 1]) }}">Siguiente</a>
                    @endif
                </div>
            </nav>
        @endif
    </div>
@endsection
