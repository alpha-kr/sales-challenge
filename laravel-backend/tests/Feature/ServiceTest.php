<?php

namespace Tests\Feature;

use Database\Factories\ProductFactory;
use Database\Factories\SaleDetailFactory;
use Database\Factories\ServiceFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): static
    {
        return $this->actingAs(UserFactory::new()->create(), 'sanctum');
    }

    public function test_index_returns_list_of_services(): void
    {
        ServiceFactory::new()->count(2)->create();

        $response = $this->actingAsUser()->getJson('/api/services');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['success', 'data' => [['id', 'name', 'price', 'disabled_at']], 'message', 'meta']);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/services')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_store_creates_a_service(): void
    {
        $response = $this->actingAsUser()->postJson('/api/services', [
            'name' => 'Installation',
            'price' => 150.00,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Service created successfully.')
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'price', 'disabled_at'], 'message', 'meta']);

        $this->assertDatabaseHas('services', ['name' => 'Installation']);
    }

    public function test_store_creates_service_with_required_product(): void
    {
        $product = ProductFactory::new()->create();

        $response = $this->actingAsUser()->postJson('/api/services', [
            'name' => 'Installation',
            'price' => 150.00,
            'required_product_id' => $product->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.required_product.id', $product->id);
    }

    public function test_store_returns_422_for_missing_fields(): void
    {
        $response = $this->actingAsUser()->postJson('/api/services', []);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    public function test_show_returns_service(): void
    {
        $service = ServiceFactory::new()->create();

        $response = $this->actingAsUser()->getJson("/api/services/{$service->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $service->id)
            ->assertJsonPath('message', 'Service retrieved successfully.');
    }

    public function test_show_returns_404_for_nonexistent_service(): void
    {
        $this->actingAsUser()->getJson('/api/services/9999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }

    public function test_update_disables_service(): void
    {
        $service = ServiceFactory::new()->create();

        $response = $this->actingAsUser()->putJson("/api/services/{$service->id}", [
            'name' => $service->name,
            'price' => $service->price,
            'disabled_at' => now()->toISOString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Service updated successfully.');

        $this->assertNotNull($response->json('data.disabled_at'));
    }

    public function test_destroy_deletes_service(): void
    {
        $service = ServiceFactory::new()->create();

        $this->actingAsUser()->deleteJson("/api/services/{$service->id}")->assertNoContent();

        $this->assertModelMissing($service);
    }

    public function test_destroy_returns_409_when_service_has_sales(): void
    {
        $service = ServiceFactory::new()->create();
        SaleDetailFactory::new()->create(['service_id' => $service->id, 'product_id' => null]);

        $this->actingAsUser()->deleteJson("/api/services/{$service->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'HAS_ACTIVE_SALES');

        $this->assertModelExists($service);
    }
}
