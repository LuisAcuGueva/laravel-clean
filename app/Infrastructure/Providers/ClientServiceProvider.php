<?php

namespace App\Infrastructure\Providers;

use App\Domain\Client\Repositories\ClientRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\EloquentClientRepository;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    /**
     * Bind the domain contract to its infrastructure implementation.
     */
    public function register(): void
    {
        $this->app->bind(ClientRepositoryInterface::class, EloquentClientRepository::class);
    }
}
