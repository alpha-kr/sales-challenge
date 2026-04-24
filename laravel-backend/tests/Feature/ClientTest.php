<?php

namespace Tests\Feature;

use Database\Factories\ClientFactory;
use Database\Factories\SaleFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): static
    {
        return $this->actingAs(UserFactory::new()->create(), 'sanctum');
    }

    public function test_index_returns_list_of_clients(): void
    {
        ClientFactory::new()->count(3)->create();

        $response = $this->actingAsUser()->getJson('/api/clients');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['success', 'data' => [['id', 'name', 'tax_id']], 'message', 'meta']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/clients')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_store_creates_a_client(): void
    {
        $response = $this->actingAsUser()->postJson('/api/clients', [
            'name' => 'Acme Corp',
            'tax_id' => '12.345.678/0001-90',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Client created successfully.')
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'tax_id'], 'message', 'meta']);

        $this->assertDatabaseHas('clients', ['name' => 'Acme Corp']);
    }

    public function test_store_returns_422_for_duplicate_tax_id(): void
    {
        ClientFactory::new()->create(['tax_id' => '12.345.678/0001-90']);

        $response = $this->actingAsUser()->postJson('/api/clients', [
            'name' => 'Other Corp',
            'tax_id' => '12.345.678/0001-90',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_store_returns_422_for_missing_fields(): void
    {
        $response = $this->actingAsUser()->postJson('/api/clients', []);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    public function test_show_returns_client(): void
    {
        $client = ClientFactory::new()->create();

        $response = $this->actingAsUser()->getJson("/api/clients/{$client->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('message', 'Client retrieved successfully.');
    }

    public function test_show_returns_404_for_nonexistent_client(): void
    {
        $this->actingAsUser()->getJson('/api/clients/9999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }

    public function test_update_modifies_client(): void
    {
        $client = ClientFactory::new()->create();

        $response = $this->actingAsUser()->putJson("/api/clients/{$client->id}", [
            'name' => 'Updated Name',
            'tax_id' => $client->tax_id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('message', 'Client updated successfully.');
    }

    public function test_destroy_deletes_client(): void
    {
        $client = ClientFactory::new()->create();

        $this->actingAsUser()->deleteJson("/api/clients/{$client->id}")->assertNoContent();

        $this->assertModelMissing($client);
    }

    public function test_destroy_returns_409_when_client_has_sales(): void
    {
        $client = ClientFactory::new()->create();
        SaleFactory::new()->create(['client_id' => $client->id]);

        $this->actingAsUser()->deleteJson("/api/clients/{$client->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'HAS_ACTIVE_SALES');

        $this->assertModelExists($client);
    }
}
