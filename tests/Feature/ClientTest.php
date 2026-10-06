<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\ClientModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(array $attributes = []): ClientModel
    {
        return ClientModel::create(array_merge([
            'name' => 'Cliente Demo',
            'email' => fake()->unique()->safeEmail(),
        ], $attributes));
    }

    public function test_index_lists_clients(): void
    {
        $client = $this->makeClient(['name' => 'Ana Pérez', 'email' => 'ana@example.com']);

        $this->get(route('clients.index'))
            ->assertOk()
            ->assertSee($client->name)
            ->assertSee($client->email);
    }

    public function test_index_paginates_ten_per_page(): void
    {
        $this->makeClient(['name' => 'Primero']);
        for ($i = 2; $i <= 12; $i++) {
            $this->makeClient(['name' => "Cliente {$i}"]);
        }

        $this->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Página 1 de 2')
            ->assertSee('12 en total')
            ->assertDontSee('Primero')
            ->assertSee(route('clients.index', ['page' => 2]));

        $this->get(route('clients.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Página 2 de 2')
            ->assertSee('Primero')
            ->assertDontSee(route('clients.index', ['page' => 3]));
    }

    public function test_create_form_is_displayed(): void
    {
        $this->get(route('clients.create'))->assertOk()->assertSee('Nuevo cliente');
    }

    public function test_client_can_be_stored(): void
    {
        $this->post(route('clients.store'), [
            'name' => 'Luis Acuña',
            'email' => 'luis@example.com',
            'phone' => '5555-1234',
            'address' => 'Ciudad de Guatemala',
        ])->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'name' => 'Luis Acuña',
            'email' => 'luis@example.com',
            'phone' => '5555-1234',
        ]);
    }

    public function test_store_requires_name_and_email(): void
    {
        $this->post(route('clients.store'), [])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $this->makeClient(['email' => 'dup@example.com']);

        $this->post(route('clients.store'), [
            'name' => 'Otro',
            'email' => 'DUP@example.com',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('clients', 1);
    }

    public function test_store_rejects_email_of_soft_deleted_client(): void
    {
        $this->makeClient(['email' => 'borrado@example.com'])->delete();

        $this->post(route('clients.store'), [
            'name' => 'Nuevo',
            'email' => 'borrado@example.com',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('clients', 1);
    }

    public function test_show_displays_client(): void
    {
        $client = $this->makeClient();

        $this->get(route('clients.show', $client->id))
            ->assertOk()
            ->assertSee($client->name);
    }

    public function test_show_returns_404_for_missing_client(): void
    {
        $this->get(route('clients.show', 999))->assertNotFound();
    }

    public function test_edit_form_is_displayed(): void
    {
        $client = $this->makeClient();

        $this->get(route('clients.edit', $client->id))
            ->assertOk()
            ->assertSee($client->email);
    }

    public function test_client_can_be_updated_keeping_its_own_email(): void
    {
        $client = $this->makeClient(['email' => 'same@example.com']);

        $this->put(route('clients.update', $client->id), [
            'name' => 'Nombre Nuevo',
            'email' => 'same@example.com',
        ])->assertRedirect(route('clients.show', $client->id));

        $this->assertSame('Nombre Nuevo', $client->fresh()->name);
    }

    public function test_update_rejects_email_used_by_another_client(): void
    {
        $this->makeClient(['email' => 'taken@example.com']);
        $client = $this->makeClient(['email' => 'mine@example.com']);

        $this->put(route('clients.update', $client->id), [
            'name' => $client->name,
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');

        $this->assertSame('mine@example.com', $client->fresh()->email);
    }

    public function test_destroy_soft_deletes_client(): void
    {
        $client = $this->makeClient();

        $this->delete(route('clients.destroy', $client->id))
            ->assertRedirect(route('clients.index'));

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }
}
